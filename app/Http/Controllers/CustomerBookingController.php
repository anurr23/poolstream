<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Table;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CustomerBookingController extends Controller
{
    public function home()
    {
        $user = Auth::user();

        $nextBooking = Transaction::with(['table', 'package'])
            ->where('user_id', $user->id)
            ->where('type', 'billiard')
            ->whereIn('status', ['booked', 'active'])
            ->where(function ($query) {
                $query->where('status', 'active')
                    ->orWhere('expected_end_time', '>=', Carbon::now());
            })
            ->orderBy('start_time')
            ->first();

        $activeTableIds = Transaction::where('type', 'billiard')
            ->where('status', 'active')
            ->where('expected_end_time', '>', Carbon::now())
            ->pluck('table_id');

        return Inertia::render('Customer/Home', [
            'packages' => Package::orderBy('price')->get(),
            'nextBooking' => $nextBooking,
            'totalTables' => Table::count(),
            'availableNow' => Table::whereNotIn('id', $activeTableIds)->count(),
        ]);
    }

    public function index()
    {
        return Inertia::render('Customer/Booking', [
            'packages' => Package::orderBy('price')->get(),
            'today' => Carbon::today()->toDateString(),
            'maxDate' => Carbon::today()->addDays(30)->toDateString(),
        ]);
    }

    public function history()
    {
        $bookings = Transaction::with(['table', 'package'])
            ->where('user_id', Auth::id())
            ->where('type', 'billiard')
            ->orderByDesc('start_time')
            ->limit(100)
            ->get();

        return Inertia::render('Customer/History', [
            'bookings' => $bookings,
        ]);
    }

    public function tables(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $day = Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay();
        $dayEnd = $day->copy()->endOfDay();

        $bookingsByTable = Transaction::where('type', 'billiard')
            ->whereIn('status', ['booked', 'active', 'completed'])
            ->where('start_time', '<', $dayEnd)
            ->where(function ($query) use ($day) {
                $query->where('expected_end_time', '>', $day)
                    ->orWhere(function ($query) use ($day) {
                        $query->whereNull('expected_end_time')
                            ->where('start_time', '>=', $day);
                    });
            })
            ->orderBy('start_time')
            ->get()
            ->groupBy('table_id');

        $tables = Table::all()
            ->sortBy('name', SORT_NATURAL)
            ->values()
            ->map(function (Table $table) use ($bookingsByTable) {
                $bookings = $bookingsByTable->get($table->id, collect());
                $activeOrBooked = $bookings->whereIn('status', ['booked', 'active']);
                $next = $activeOrBooked->sortBy('start_time')->first();

                return [
                    'id' => $table->id,
                    'name' => $table->name,
                    'bookings_count' => $activeOrBooked->count(),
                    'total_bookings' => $bookings->count(),
                    'is_active' => $bookings->contains('status', 'active'),
                    'next_booking' => $next?->start_time?->toIso8601String(),
                ];
            });

        return response()->json([
            'date' => $validated['date'],
            'tables' => $tables,
        ]);
    }

    public function tableBookings(Request $request, Table $table)
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $day = Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay();
        $dayEnd = $day->copy()->endOfDay();

        $bookings = Transaction::with('package')
            ->where('table_id', $table->id)
            ->where('type', 'billiard')
            ->whereIn('status', ['booked', 'active', 'completed'])
            ->where('start_time', '<', $dayEnd)
            ->where(function ($query) use ($day) {
                $query->where('expected_end_time', '>', $day)
                    ->orWhere(function ($query) use ($day) {
                        $query->whereNull('expected_end_time')
                            ->where('start_time', '>=', $day);
                    });
            })
            ->orderBy('start_time')
            ->get()
            ->map(fn (Transaction $booking) => [
                'id' => $booking->id,
                'start_time' => $booking->start_time->toIso8601String(),
                'expected_end_time' => $booking->expected_end_time?->toIso8601String(),
                'end_time' => $booking->end_time?->toIso8601String(),
                'status' => $booking->status,
                'is_own' => $booking->user_id === Auth::id(),
                'package_name' => $booking->package?->name,
            ]);

        return response()->json([
            'table' => [
                'id' => $table->id,
                'name' => $table->name,
            ],
            'bookings' => $bookings,
        ]);
    }

    public function availability(Request $request)
    {
        $validated = $request->validate($this->scheduleRules());

        $start = $this->makeStart($validated['date'], $validated['start_time']);

        if ($start->lt(Carbon::now())) {
            return response()->json([
                'tables' => [],
                'message' => 'Waktu mulai sudah lewat. Pilih jam lain.',
            ]);
        }

        $end = $start->copy()->addMinutes((int) round($validated['duration_hours'] * 60));
        $busyTableIds = $this->busyTableIds($start, $end);

        $tables = Table::all()
            ->sortBy('name', SORT_NATURAL)
            ->values()
            ->map(fn (Table $table) => [
                'id' => $table->id,
                'name' => $table->name,
                'relay_channel' => $table->relay_channel,
                'available' => ! $busyTableIds->contains($table->id),
            ]);

        return response()->json([
            'tables' => $tables,
            'start' => $start->toIso8601String(),
            'end' => $end->toIso8601String(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(array_merge($this->scheduleRules(), [
            'package_id' => ['required', 'exists:packages,id'],
            'table_id' => ['required', 'exists:tables,id'],
        ]));

        $start = $this->makeStart($validated['date'], $validated['start_time']);

        if ($start->lt(Carbon::now())) {
            throw ValidationException::withMessages([
                'start_time' => 'Waktu mulai sudah lewat. Pilih jam lain.',
            ]);
        }

        $end = $start->copy()->addMinutes((int) round($validated['duration_hours'] * 60));

        if ($this->busyTableIds($start, $end)->contains($validated['table_id'])) {
            throw ValidationException::withMessages([
                'table_id' => 'Meja ini sudah dibooking pada jam tersebut. Silakan pilih meja lain.',
            ]);
        }

        $package = Package::findOrFail($validated['package_id']);
        $billiardCost = $package->price * $validated['duration_hours'];

        Transaction::create([
            'type' => 'billiard',
            'table_id' => $validated['table_id'],
            'package_id' => $package->id,
            'user_id' => Auth::id(),
            'customer_name' => Auth::user()->name,
            'start_time' => $start,
            'expected_end_time' => $end,
            'billiard_cost' => $billiardCost,
            'fnb_cost' => 0,
            'total_cost' => $billiardCost,
            'status' => 'booked',
        ]);

        return redirect()->route('customer.history')
            ->with('success', 'Booking berhasil dibuat. Silakan datang sesuai jadwal.');
    }

    public function cancel(Transaction $transaction)
    {
        if ($transaction->user_id !== Auth::id() || $transaction->type !== 'billiard') {
            abort(403);
        }

        if ($transaction->status !== 'booked') {
            return back()->with('error', 'Booking ini tidak bisa dibatalkan.');
        }

        if ($transaction->start_time->isPast()) {
            return back()->with('error', 'Booking yang sudah dimulai tidak bisa dibatalkan.');
        }

        $transaction->status = 'cancelled';
        $transaction->save();

        return back()->with('success', 'Booking berhasil dibatalkan.');
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function scheduleRules(): array
    {
        return [
            'date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.Carbon::today()->toDateString(),
                'before_or_equal:'.Carbon::today()->addDays(30)->toDateString(),
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'duration_hours' => ['required', 'numeric', 'min:0.5', 'max:12'],
        ];
    }

    private function makeStart(string $date, string $time): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i', "{$date} {$time}");
    }

    private function busyTableIds(Carbon $start, Carbon $end)
    {
        return Transaction::query()
            ->where('type', 'billiard')
            ->whereIn('status', ['active', 'booked'])
            ->where('start_time', '<', $end)
            ->where(function ($query) use ($start) {
                $query->whereNull('expected_end_time')
                    ->orWhere('expected_end_time', '>', $start);
            })
            ->pluck('table_id');
    }
}
