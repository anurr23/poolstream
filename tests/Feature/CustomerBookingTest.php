<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Table;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerBookingTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    private function schedule(int $addMinutes = 10): array
    {
        $start = now()->addMinutes($addMinutes)->startOfMinute();

        return [
            'date' => $start->toDateString(),
            'start_time' => $start->format('H:i'),
            'start' => $start,
        ];
    }

    public function test_customer_is_redirected_to_booking_page_after_login(): void
    {
        $customer = $this->customer();

        $response = $this->post('/login', [
            'username' => $customer->username,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('customer.home'));
        $this->assertAuthenticatedAs($customer);
    }

    public function test_customer_can_view_home_page(): void
    {
        $this->actingAs($this->customer())
            ->get('/customer')
            ->assertOk();
    }

    public function test_customer_can_view_booking_page(): void
    {
        $this->actingAs($this->customer())
            ->get('/customer/booking')
            ->assertOk();
    }

    public function test_customer_can_view_booking_history_page(): void
    {
        $this->actingAs($this->customer())
            ->get('/customer/history')
            ->assertOk();
    }

    public function test_customer_can_see_tables_and_their_bookings_for_a_date(): void
    {
        $customer = $this->customer();
        $table = Table::factory()->create(['name' => 'Meja 1']);
        $otherTable = Table::factory()->create(['name' => 'Meja 2']);
        $schedule = $this->schedule();

        Transaction::create([
            'type' => 'billiard',
            'table_id' => $table->id,
            'package_id' => Package::factory()->create()->id,
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'start_time' => $schedule['start'],
            'expected_end_time' => $schedule['start']->copy()->addHour(),
            'billiard_cost' => 25000,
            'fnb_cost' => 0,
            'total_cost' => 25000,
            'status' => 'booked',
        ]);

        $this->actingAs($customer)
            ->getJson('/customer/tables?date='.$schedule['date'])
            ->assertOk()
            ->assertJsonFragment(['id' => $table->id, 'name' => 'Meja 1', 'bookings_count' => 1])
            ->assertJsonFragment(['id' => $otherTable->id, 'name' => 'Meja 2', 'bookings_count' => 0]);

        $this->actingAs($customer)
            ->getJson('/customer/tables/'.$table->id.'?date='.$schedule['date'])
            ->assertOk()
            ->assertJsonFragment(['id' => $table->id, 'status' => 'booked']);
    }

    public function test_customer_can_create_a_booking(): void
    {
        $customer = $this->customer();
        $package = Package::factory()->create(['price' => 25000]);
        $table = Table::factory()->create();
        $schedule = $this->schedule();

        $response = $this->actingAs($customer)->post('/customer/bookings', [
            'date' => $schedule['date'],
            'start_time' => $schedule['start_time'],
            'duration_hours' => 2,
            'package_id' => $package->id,
            'table_id' => $table->id,
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect();

        $booking = Transaction::firstOrFail();

        $this->assertSame('booked', $booking->status);
        $this->assertSame('billiard', $booking->type);
        $this->assertSame($customer->id, $booking->user_id);
        $this->assertSame($table->id, $booking->table_id);
        $this->assertSame($customer->name, $booking->customer_name);
        $this->assertEquals(50000, $booking->billiard_cost);
        $this->assertEquals(50000, $booking->total_cost);
        $this->assertTrue($booking->expected_end_time->equalTo($schedule['start']->copy()->addHours(2)));
    }

    public function test_booked_table_is_not_available_for_the_same_slot(): void
    {
        $customer = $this->customer();
        $package = Package::factory()->create();
        $table = Table::factory()->create();
        $schedule = $this->schedule();

        Transaction::create([
            'type' => 'billiard',
            'table_id' => $table->id,
            'package_id' => $package->id,
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'start_time' => $schedule['start'],
            'expected_end_time' => $schedule['start']->copy()->addHour(),
            'billiard_cost' => $package->price,
            'fnb_cost' => 0,
            'total_cost' => $package->price,
            'status' => 'booked',
        ]);

        $this->actingAs($customer)
            ->getJson('/customer/availability?date='.$schedule['date'].'&start_time='.$schedule['start_time'].'&duration_hours=1')
            ->assertOk()
            ->assertJsonFragment(['id' => $table->id, 'available' => false]);
    }

    public function test_customer_cannot_double_book_the_same_table_and_slot(): void
    {
        $customer = $this->customer();
        $package = Package::factory()->create();
        $table = Table::factory()->create();
        $schedule = $this->schedule();

        Transaction::create([
            'type' => 'billiard',
            'table_id' => $table->id,
            'package_id' => $package->id,
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'start_time' => $schedule['start'],
            'expected_end_time' => $schedule['start']->copy()->addHour(),
            'billiard_cost' => $package->price,
            'fnb_cost' => 0,
            'total_cost' => $package->price,
            'status' => 'booked',
        ]);

        $this->actingAs($customer)
            ->post('/customer/bookings', [
                'date' => $schedule['date'],
                'start_time' => $schedule['start_time'],
                'duration_hours' => 1,
                'package_id' => $package->id,
                'table_id' => $table->id,
            ])
            ->assertSessionHasErrors('table_id');

        $this->assertSame(1, Transaction::count());
    }

    public function test_customer_can_cancel_own_booking_before_it_starts(): void
    {
        $customer = $this->customer();
        $booking = Transaction::factory()->create([
            'type' => 'billiard',
            'user_id' => $customer->id,
            'status' => 'booked',
            'start_time' => now()->addHours(2),
            'expected_end_time' => now()->addHours(3),
        ]);

        $this->actingAs($customer)
            ->delete('/customer/bookings/'.$booking->id)
            ->assertRedirect();

        $this->assertSame('cancelled', $booking->refresh()->status);
    }

    public function test_kasir_can_start_a_due_booking(): void
    {
        $kasir = User::factory()->create(['role' => 'kasir']);
        $booking = Transaction::factory()->create([
            'type' => 'billiard',
            'status' => 'booked',
            'start_time' => now()->addMinutes(10),
            'expected_end_time' => now()->addHours(1),
        ]);

        $this->actingAs($kasir)
            ->post('/tables/bookings/'.$booking->id.'/start')
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('active', $booking->refresh()->status);
        $this->assertSame('active', $booking->table->refresh()->status);
    }

    public function test_role_access_is_separated_between_customer_and_cashier(): void
    {
        $this->actingAs($this->customer())
            ->get('/dashboard')
            ->assertForbidden();

        $kasir = User::factory()->create(['role' => 'kasir']);

        $this->actingAs($kasir)
            ->get('/customer')
            ->assertOk();

        $this->actingAs($kasir)
            ->get('/master')
            ->assertForbidden();
    }

    public function test_customer_cannot_start_table_session(): void
    {
        $table = Table::factory()->create();

        $this->actingAs($this->customer())
            ->post('/tables/'.$table->id.'/start', [
                'package_id' => Package::factory()->create()->id,
                'duration_hours' => 1,
                'customer_name' => 'Test Customer',
            ])
            ->assertForbidden();

        $this->assertNotSame('active', $table->refresh()->status);
    }

    public function test_customer_cannot_start_their_own_booked_session(): void
    {
        $customer = $this->customer();
        $schedule = $this->schedule();
        $table = Table::factory()->create();

        $booking = Transaction::create([
            'type' => 'billiard',
            'table_id' => $table->id,
            'package_id' => Package::factory()->create()->id,
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'start_time' => $schedule['start'],
            'expected_end_time' => $schedule['start']->copy()->addHour(),
            'billiard_cost' => 25000,
            'fnb_cost' => 0,
            'total_cost' => 25000,
            'status' => 'booked',
        ]);

        $this->actingAs($customer)
            ->post('/tables/bookings/'.$booking->id.'/start')
            ->assertForbidden();

        $this->assertSame('booked', $booking->refresh()->status);
        $this->assertNotSame('active', $table->refresh()->status);
    }
}
