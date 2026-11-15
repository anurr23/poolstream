<?php

namespace App\Http\Controllers;

use App\Models\FnbItem;
use App\Models\Package;
use App\Models\Table;
use App\Models\Transaction;
use App\Services\RelayService;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TableController extends Controller
{
    public function index()
    {
        $tables = Table::with([
            'transactions' => function ($q) {
                $q->where('status', 'active')->with('items.fnbItem');
            },
            'recentTransactions',
        ])->get()->sortBy('name', SORT_NATURAL)->values();
        $packages = Package::all();
        $fnbItems = FnbItem::orderBy('name')->get();

        $upcomingBookings = Transaction::with(['table', 'package'])
            ->where('type', 'billiard')
            ->where('status', 'booked')
            ->where('expected_end_time', '>=', Carbon::now())
            ->orderBy('start_time')
            ->get();

        return Inertia::render('Dashboard', [
            'tables' => $tables,
            'packages' => $packages,
            'fnbItems' => $fnbItems,
            'upcomingBookings' => $upcomingBookings,
        ]);
    }

    // Master Data Methods
    public function masterIndex()
    {
        return Inertia::render('Master/Tables', [
            'tables' => Table::all()->sortBy('name', SORT_NATURAL)->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'relay_channel' => 'required|integer|min:1|max:16',
        ]);

        Table::create($validated);

        return back()->with('success', 'Meja berhasil ditambahkan.');
    }

    public function update(Request $request, Table $table)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'relay_channel' => 'required|integer|min:1|max:16',
        ]);

        $table->update($validated);

        return back()->with('success', 'Meja berhasil diupdate.');
    }

    public function destroy(Table $table)
    {
        $table->delete();

        return back()->with('success', 'Meja berhasil dihapus.');
    }

    public function start(Request $request, $id)
    {
        $request->validate([
            'customer_name' => 'required',
            'duration_hours' => 'required|numeric|min:0.5',
            'package_id' => 'required',
            'items' => 'nullable|array',
            'items.*.fnb_item_id' => 'required|exists:fnb_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $table = Table::find($id);
        $package = Package::find($request->package_id);

        if ($table && $package) {
            $startTime = Carbon::now();
            $endTime = Carbon::now()->addMinutes($request->duration_hours * 60);

            $hasConflict = Transaction::where('table_id', $table->id)
                ->where('type', 'billiard')
                ->whereIn('status', ['active', 'booked'])
                ->where('start_time', '<', $endTime)
                ->where(function ($query) use ($startTime) {
                    $query->whereNull('expected_end_time')
                        ->orWhere('expected_end_time', '>', $startTime);
                })
                ->exists();

            if ($table->status === 'active' || $hasConflict) {
                return back()->with('error', 'Meja sedang dipakai atau sudah memiliki booking pada waktu tersebut.');
            }

            $table->status = 'active';
            $table->save();

            // Create Transaction
            $expectedEndTime = Carbon::now()->addMinutes($request->duration_hours * 60);

            $transaction = Transaction::create([
                'type' => 'billiard',
                'table_id' => $table->id,
                'package_id' => $package->id,
                'user_id' => Auth::id(),
                'customer_name' => $request->customer_name,
                'start_time' => $startTime,
                'expected_end_time' => $expectedEndTime,
                'billiard_cost' => $package->price * $request->duration_hours,
                'fnb_cost' => 0,
                'total_cost' => $package->price * $request->duration_hours,
                'status' => 'active',
            ]);

            // Add F&B Items if provided
            if ($request->has('items') && is_array($request->items)) {
                foreach ($request->items as $item) {
                    $fnbItem = FnbItem::find($item['fnb_item_id']);
                    if (! $fnbItem) {
                        continue;
                    }

                    $transaction->items()->create([
                        'fnb_item_id' => $fnbItem->id,
                        'price' => $fnbItem->price,
                        'quantity' => $item['quantity'],
                        'subtotal' => $fnbItem->price * $item['quantity'],
                    ]);
                }
                TransactionService::recalculate($transaction);
            }

            // Turn ON relay & update light state
            RelayService::setLight($table, true);
        }

        return back();
    }

    public function updateSession(Request $request, $id, $transaction_id)
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id',
            'duration_hours' => 'required|numeric|min:0.5',
            'items' => 'nullable|array',
            'items.*.fnb_item_id' => 'required|exists:fnb_items,id',
            'items.*.quantity' => 'required|integer|min:0',
        ]);

        $transaction = Transaction::where('id', $transaction_id)
            ->where('table_id', $id)
            ->where('status', 'active')
            ->firstOrFail();

        // VALIDATION: Ensure new duration is not less than the elapsed time
        $elapsedMinutes = Carbon::now()->diffInMinutes(Carbon::parse($transaction->start_time));
        $elapsedHours = $elapsedMinutes / 60;

        if ($request->duration_hours < $elapsedHours) {
            return back()->withErrors([
                'duration_hours' => 'Durasi pesanan tidak boleh kurang dari waktu yang sudah berjalan ('.number_format($elapsedHours, 1).' jam).',
            ]);
        }

        $package = Package::find($request->package_id);

        if ($transaction && $package) {
            // Update package
            $transaction->package_id = $package->id;

            // Recalculate end time based on original start time
            $originalStartTime = Carbon::parse($transaction->start_time);
            $newExpectedEndTime = $originalStartTime->copy()->addMinutes($request->duration_hours * 60);

            $transaction->expected_end_time = $newExpectedEndTime;

            // Handle items update
            if ($request->has('items') && is_array($request->items)) {
                // Delete items that have quantity 0 or were removed
                $receivedItemIds = collect($request->items)->pluck('fnb_item_id')->toArray();
                $transaction->items()->whereNotIn('fnb_item_id', $receivedItemIds)->delete();
                $transaction->items()->where('quantity', '<=', 0)->delete();

                foreach ($request->items as $item) {
                    if ($item['quantity'] <= 0) {
                        continue;
                    }

                    $fnbItem = FnbItem::find($item['fnb_item_id']);
                    if (! $fnbItem) {
                        continue;
                    }

                    $transaction->items()->updateOrCreate(
                        ['fnb_item_id' => $fnbItem->id],
                        [
                            'price' => $fnbItem->price,
                            'quantity' => $item['quantity'],
                            'subtotal' => $fnbItem->price * $item['quantity'],
                        ]
                    );
                }
            } else {
                $transaction->items()->delete();
            }

            $transaction->billiard_cost = $package->price * $request->duration_hours;
            $transaction->save();
            TransactionService::recalculate($transaction);
        }

        return back()->with('success', 'Sesi berhasil diperbarui.');
    }

    public function startBooking(Transaction $transaction)
    {
        if ($transaction->type !== 'billiard' || $transaction->status !== 'booked') {
            return back()->with('error', 'Booking tidak valid atau sudah diproses.');
        }

        $table = $transaction->table;

        if (! $table) {
            return back()->with('error', 'Meja untuk booking ini tidak ditemukan.');
        }

        if ($transaction->expected_end_time && $transaction->expected_end_time->isPast()) {
            return back()->with('error', 'Jadwal booking sudah lewat.');
        }

        if ($transaction->start_time->gt(Carbon::now()->addMinutes(30))) {
            return back()->with('error', 'Booking belum bisa dimulai. Tunggu hingga 30 menit sebelum jadwal.');
        }

        if ($table->status === 'active') {
            return back()->with('error', 'Meja sedang dipakai sesi lain.');
        }

        $otherActiveSession = Transaction::where('table_id', $table->id)
            ->where('status', 'active')
            ->where('id', '!=', $transaction->id)
            ->exists();

        if ($otherActiveSession) {
            return back()->with('error', 'Meja sedang dipakai sesi lain.');
        }

        $table->status = 'active';
        $table->save();

        $transaction->status = 'active';
        $transaction->save();

        RelayService::setLight($table, true);

        return back()->with('success', "Booking {$transaction->customer_name} berhasil dimulai.");
    }

    public function stop(Request $request, $id)
    {
        $table = Table::find($id);
        if ($table) {
            $table->status = 'inactive';
            $table->save();

            // Complete Transaction
            $transaction = Transaction::where('table_id', $table->id)
                ->where('status', 'active')
                ->with('items.fnbItem')
                ->first();

            if ($transaction) {
                $transaction->end_time = Carbon::now();
                $transaction->status = 'completed';
                $transaction->save();
                TransactionService::recalculate($transaction);
            }

            // Turn OFF relay & update light state
            RelayService::setLight($table, false);
        }

        return back();
    }

    public function toggleLight($id)
    {
        $table = Table::findOrFail($id);
        $isOn = RelayService::toggle($table);

        $stateText = $isOn ? 'dinyalakan' : 'dimatikan';
        return back()->with('success', "Lampu {$table->name} berhasil {$stateText}.");
    }
}
