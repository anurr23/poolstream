<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\RelayService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoCheckoutExpiredTables extends Command
{
    protected $signature = 'tables:auto-checkout';

    protected $description = 'Auto-checkout tables whose session has expired';

    public function handle(): int
    {
        $expired = Transaction::with('table')
            ->where('status', 'active')
            ->whereNotNull('expected_end_time')
            ->where('expected_end_time', '<=', Carbon::now())
            ->get();

        if ($expired->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($expired as $transaction) {
            $table = $transaction->table;

            if (! $table) {
                continue;
            }

            // Complete the transaction
            $transaction->end_time = $transaction->expected_end_time;
            $transaction->total_cost = $transaction->billiard_cost + $transaction->fnb_cost;
            $transaction->status = 'completed';
            $transaction->save();

            // Deactivate the table and turn off light
            $table->status = 'inactive';
            $table->save();

            RelayService::setLight($table, false);

            $this->info("Auto-checkout: {$table->name} ({$transaction->customer_name})");
        }

        // Cancel bookings that were never started (no-show).
        $noShowBookings = Transaction::where('type', 'billiard')
            ->where('status', 'booked')
            ->whereNotNull('expected_end_time')
            ->where('expected_end_time', '<=', Carbon::now())
            ->get();

        foreach ($noShowBookings as $booking) {
            $booking->status = 'cancelled';
            $booking->save();

            $this->info("Auto-cancel booking: {$booking->customer_name}");
        }

        return self::SUCCESS;
    }
}
