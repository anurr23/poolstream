<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\AiEvaluationController;
use App\Http\Controllers\CustomerBookingController;
use App\Http\Controllers\FnbItemController;
use App\Http\Controllers\FnbOrderController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\TransactionItemController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Endpoint dev-only: segarkan OPcache setelah file PHP berubah.
// Dipakai oleh scripts/watch-opcache.ps1 agar tidak perlu restart container.
Route::get('/__dev/opcache', function (Illuminate\Http\Request $request) {
    abort_unless(app()->environment(['local', 'testing']), 404);

    $token = (string) config('app.dev_opcache_token');
    abort_if($token === '', 404);
    abort_unless(hash_equals($token, (string) $request->query('token')), 403);

    $paths = $request->query('path', []);
    $paths = is_array($paths) ? $paths : [$paths];
    $paths = array_values(array_filter($paths, 'is_string'));

    if ($paths === []) {
        $ok = function_exists('opcache_reset') ? @opcache_reset() : false;

        return response()->json(['mode' => 'reset', 'ok' => $ok]);
    }

    $basePath = realpath(base_path());
    $files = [];

    foreach ($paths as $path) {
        $absolute = realpath(base_path($path));

        if (! $absolute || ! $basePath || ! str_starts_with($absolute, $basePath) || ! str_ends_with($absolute, '.php')) {
            continue;
        }

        $ok = function_exists('opcache_invalidate') ? @opcache_invalidate($absolute, true) : false;
        $files[] = ['path' => $path, 'ok' => (bool) $ok];
    }

    return response()->json(['mode' => 'invalidate', 'files' => $files]);
})->middleware('throttle:120,1')->name('dev.opcache');

// Landing page utama (publik). Klik menu diarahkan ke login bila belum ada session.
Route::get('/', function () {
    return Inertia::render('Landing');
})->name('landing');

Route::middleware(['auth'])->group(function () {
    Route::middleware('role:admin,kasir')->group(function () {
        Route::get('/dashboard', [TableController::class, 'index'])->name('dashboard');
        Route::post('/tables/{id}/start', [TableController::class, 'start'])->name('tables.start');
        Route::post('/tables/{id}/stop', [TableController::class, 'stop'])->name('tables.stop');
        Route::post('/tables/{id}/toggle-light', [TableController::class, 'toggleLight'])->name('tables.toggle-light');
        Route::post('/tables/bookings/{transaction}/start', [TableController::class, 'startBooking'])->name('tables.start-booking');
        Route::put('/tables/{id}/transactions/{transaction}', [TableController::class, 'updateSession'])->name('tables.updateSession');

        // F&B items on active transactions
        Route::post('/transactions/{transaction}/items', [TransactionItemController::class, 'store'])->name('transaction-items.store');
        Route::patch('/transaction-items/{item}/add', [TransactionItemController::class, 'updateQuantity'])->name('transaction-items.add');
        Route::patch('/transaction-items/{item}', [TransactionItemController::class, 'updateQuantity'])->name('transaction-items.update-quantity');
        Route::delete('/transaction-items/{item}', [TransactionItemController::class, 'destroy'])->name('transaction-items.destroy');

        // F&B standalone orders
        Route::get('/fnb-orders', [FnbOrderController::class, 'index'])->name('fnb-orders.index');
        Route::post('/fnb-orders', [FnbOrderController::class, 'store'])->name('fnb-orders.store');
        Route::post('/fnb-orders/{transaction}/checkout', [FnbOrderController::class, 'checkout'])->name('fnb-orders.checkout');
    });

    Route::middleware('role:admin')->group(function () {
        // Admin menu hub
        Route::get('/master', function () {
            return Inertia::render('Admin/Index');
        })->name('admin.index');

        Route::get('/master/tables', [TableController::class, 'masterIndex'])->name('tables.index');
        Route::post('/master/tables', [TableController::class, 'store'])->name('tables.store');
        Route::put('/master/tables/{table}', [TableController::class, 'update'])->name('tables.update');
        Route::delete('/master/tables/{table}', [TableController::class, 'destroy'])->name('tables.destroy');

        Route::get('/master/packages', [PackageController::class, 'index'])->name('packages.index');
        Route::post('/master/packages', [PackageController::class, 'store'])->name('packages.store');
        Route::put('/master/packages/{package}', [PackageController::class, 'update'])->name('packages.update');
        Route::delete('/master/packages/{package}', [PackageController::class, 'destroy'])->name('packages.destroy');

        Route::get('/master/fnb-items', [FnbItemController::class, 'index'])->name('fnb_items.index');
        Route::post('/master/fnb-items', [FnbItemController::class, 'store'])->name('fnb_items.store');
        Route::put('/master/fnb-items/{fnbItem}', [FnbItemController::class, 'update'])->name('fnb_items.update');
        Route::delete('/master/fnb-items/{fnbItem}', [FnbItemController::class, 'destroy'])->name('fnb_items.destroy');

        Route::get('/master/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/master/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/master/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/master/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/master/reports/fnb-sales', [ReportController::class, 'fnbSales'])->name('reports.fnb-sales');
        Route::get('/master/reports/table-transactions', [ReportController::class, 'tableTransactions'])->name('reports.table-transactions');
        Route::get('/master/reports/revenue', [ReportController::class, 'revenue'])->name('reports.revenue');
        Route::get('/master/reports/analytics', [ReportController::class, 'analytics'])->name('reports.analytics');

        // Evaluasi AI (analisa semua report oleh AI lokal)
        Route::get('/master/ai-evaluations', [AiEvaluationController::class, 'index'])->name('ai-evaluations.index');
        Route::post('/master/ai-evaluations', [AiEvaluationController::class, 'store'])->name('ai-evaluations.store');
        Route::get('/master/ai-evaluations/{evaluation}', [AiEvaluationController::class, 'show'])->name('ai-evaluations.show');
        Route::get('/master/ai-evaluations/{evaluation}/pdf', [AiEvaluationController::class, 'pdf'])->name('ai-evaluations.pdf');
        Route::delete('/master/ai-evaluations/{evaluation}', [AiEvaluationController::class, 'destroy'])->name('ai-evaluations.destroy');
    });
});

Route::middleware(['auth', 'role:admin,kasir,customer'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/verify', [EmailVerificationController::class, 'verify'])->middleware('throttle:10,1')->name('verification.verify');
    Route::post('/verify/resend', [EmailVerificationController::class, 'resend'])->middleware('throttle:3,1')->name('verification.resend');

    Route::middleware('customer.verified')->group(function () {
        Route::get('/', [CustomerBookingController::class, 'home'])->name('home');
        Route::get('/booking', [CustomerBookingController::class, 'index'])->name('booking.index');
        Route::get('/history', [CustomerBookingController::class, 'history'])->name('history');
        Route::get('/tables', [CustomerBookingController::class, 'tables'])->name('tables');
        Route::get('/tables/{table}', [CustomerBookingController::class, 'tableBookings'])->name('table-bookings');
        Route::get('/availability', [CustomerBookingController::class, 'availability'])->name('booking.availability');
        Route::post('/bookings', [CustomerBookingController::class, 'store'])->name('booking.store');
        Route::delete('/bookings/{transaction}', [CustomerBookingController::class, 'cancel'])->name('booking.cancel');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
