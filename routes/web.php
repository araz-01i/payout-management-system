<?php

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PayoutController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    // Redirect all authenticated users to payouts
    return redirect()->route('payouts.index');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::resource('users', UserController::class);

    // Employees (admin only — permission:manage employees)
    Route::middleware('permission:manage employees')->resource('employees', EmployeeController::class);

    // Payouts (permission-gated per action)
    Route::middleware('permission:view payouts')->get('/payouts', [PayoutController::class, 'index'])->name('payouts.index');
    Route::middleware('permission:create payouts')->post('/payouts', [PayoutController::class, 'store'])->name('payouts.store');
    Route::middleware('permission:edit payouts')->put('/payouts/{payout}', [PayoutController::class, 'update'])->name('payouts.update');
    Route::middleware('permission:change payout status')->patch('/payouts/{payout}/status', [PayoutController::class, 'updateStatus'])->name('payouts.update-status');
    Route::middleware('permission:delete payouts')->delete('/payouts/{payout}', [PayoutController::class, 'destroy'])->name('payouts.destroy');
});

require __DIR__.'/settings.php';
