<?php

namespace Tests\Feature;

use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableLightTest extends TestCase
{
    use RefreshDatabase;

    public function test_kasir_can_toggle_table_light_independently(): void
    {
        $kasir = User::factory()->create(['role' => 'kasir']);
        $table = Table::factory()->create([
            'status' => 'inactive',
            'is_light_on' => false,
        ]);

        $response = $this->actingAs($kasir)->post(route('tables.toggle-light', $table->id));

        $response->assertSessionHas('success');
        $this->assertTrue($table->fresh()->is_light_on);
        $this->assertEquals('inactive', $table->fresh()->status);

        // Toggle back off
        $response = $this->actingAs($kasir)->post(route('tables.toggle-light', $table->id));
        $response->assertSessionHas('success');
        $this->assertFalse($table->fresh()->is_light_on);
    }
}
