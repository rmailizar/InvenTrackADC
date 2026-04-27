<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\GoogleSheetController;
use App\Http\Controllers\ItemLookupController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\StockRequestController;

// Public Routes (no auth)
Route::get('/', [StockRequestController::class, 'publicIndex'])->name('public.stock-request');
Route::post('/request-stock', [StockRequestController::class, 'store'])->name('public.stock-request.store');

// Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware('auth')->group(function () {

    // Dashboard - admin & manager only
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('userAkses:admin,manajer');

    // Daily approval from dashboard - admin only
    Route::post('/dashboard/approve-date', [DashboardController::class, 'approveByDate'])->name('dashboard.approveByDate')->middleware('userAkses:admin');
    Route::post('/dashboard/reject-date', [DashboardController::class, 'rejectByDate'])->name('dashboard.rejectByDate')->middleware('userAkses:admin');

    // Dashboard AJAX API endpoints
    Route::get('/dashboard/api/search-items', [DashboardController::class, 'searchItems'])->name('dashboard.searchItems')->middleware('userAkses:admin,manajer');
    Route::get('/dashboard/api/chart-data', [DashboardController::class, 'chartData'])->name('dashboard.chartData')->middleware('userAkses:admin,manajer');
    Route::get('/dashboard/api/category-by-year', [DashboardController::class, 'categoryByYear'])->name('dashboard.categoryByYear')->middleware('userAkses:admin,manajer');
    Route::get('/dashboard/api/monthly-data', [DashboardController::class, 'monthlyData'])
        ->name('dashboard.monthlyData');
    // Items - admin only
    // Lookups routes must come BEFORE resource to avoid matching items/{item}
    Route::get('/items/lookups', [ItemLookupController::class, 'index'])->name('items.lookups.index')->middleware('userAkses:admin');
    Route::post('/items/lookup/replace', [ItemLookupController::class, 'replace'])->name('items.lookup.replace')->middleware('userAkses:admin');
    Route::resource('items', ItemController::class)->middleware('userAkses:admin');
    Route::get('/items/{item}/edit-data', [ItemController::class, 'edit'])->name('items.editData')->middleware('userAkses:admin');

    // Transactions - admin & staff
    Route::resource('transactions', TransactionController::class)->only(['index', 'create', 'store'])->middleware('userAkses:admin,staf');
    Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit')->middleware('userAkses:admin');
    Route::get('/transactions/{transaction}/edit-data', [TransactionController::class, 'edit'])->name('transactions.editData')->middleware('userAkses:admin');
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update')->middleware('userAkses:admin');
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy')->middleware('userAkses:admin');

    // AJAX: Get item details
    Route::get('/api/items/{id}', [TransactionController::class, 'getItemDetails'])->name('api.items.show');

    // Users - admin can CRUD
    Route::resource('users', UserController::class)->middleware('userAkses:admin');
    Route::get('/users/{user}/edit-data', [UserController::class, 'edit'])->name('users.editData')->middleware('userAkses:admin');

    // User approval - manager only
    Route::post('users/{id}/approve-account', [UserController::class, 'approveUser'])->name('users.approveAccount')->middleware('userAkses:manajer');
    Route::post('users/{id}/reject-account', [UserController::class, 'rejectUser'])->name('users.rejectAccount')->middleware('userAkses:manajer');

    // Pending users list for manager
    Route::get('/pending-users', [UserController::class, 'index'])->name('pendingUsers.index')->middleware('userAkses:manajer');

    // Stock recap - admin & manager
    Route::get('/stock', [StockController::class, 'index'])->name('stock.index')->middleware('userAkses:admin,manajer');

    // Stock requests - admin & staff
    Route::get('/stock-requests', [StockRequestController::class, 'adminIndex'])->name('stock-requests.index')->middleware('userAkses:admin,staf');
    Route::post('/stock-requests/{stockRequest}/approve', [StockRequestController::class, 'approve'])->name('stock-requests.approve')->middleware('userAkses:admin');
    Route::post('/stock-requests/{stockRequest}/reject', [StockRequestController::class, 'reject'])->name('stock-requests.reject')->middleware('userAkses:admin');
    Route::post('/stock-requests/{stockRequest}/complete', [StockRequestController::class, 'complete'])->name('stock-requests.complete')->middleware('userAkses:admin,staf');
    Route::post('/stock-requests/{stockRequest}/cancel', [StockRequestController::class, 'cancel'])->name('stock-requests.cancel')->middleware('userAkses:admin,staf');

    // Reports - admin & manager
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index')->middleware('userAkses:admin,manajer');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export')->middleware('userAkses:admin,manajer');

    // Import data from Excel - admin only
    Route::get('/import', [ImportController::class, 'index'])->name('import.index')->middleware('userAkses:admin');
    Route::post('/import', [ImportController::class, 'import'])->name('import.process')->middleware('userAkses:admin');
    Route::get('/import/template/{type}', [ImportController::class, 'downloadTemplate'])->name('import.template')->middleware('userAkses:admin');

    // Google Sheets sync - admin only
    Route::post('/sync/sheets', [GoogleSheetController::class, 'fullSync'])->name('sync.sheets')->middleware('userAkses:admin');
});
