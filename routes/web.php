<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PartyController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\RollSizeController;
use App\Http\Controllers\LoomNumberController;
use App\Http\Controllers\FabricColorController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\SummaryReportController;
use App\Http\Controllers\MonthlyDispatchTransferReportController;
use App\Http\Controllers\DailyDispatchTransferReportController;
use App\Http\Controllers\MonthlyProductionReportController;
use App\Http\Controllers\ItemMasterController;
use App\Http\Controllers\MRLEntryController;
use App\Http\Controllers\QuotationController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Cache Utility Routes
Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    Artisan::call('view:clear');
    Artisan::call('optimize:clear');
    return response()->json([
        'status' => 'success',
        'message' => 'Cache cleared successfully (application, route, config, and view cache).'
    ]);
})->name('cache.clear');

Route::get('/create-cache', function () {
    Artisan::call('config:cache');
    Artisan::call('route:cache');
    Artisan::call('view:cache');
    Artisan::call('optimize');
    return response()->json([
        'status' => 'success',
        'message' => 'Cache created/optimized successfully (config, route, and view cache).'
    ]);
})->name('cache.create');

// Aliases for convenience
Route::get('/cache/clear', fn() => redirect()->route('cache.clear'));
Route::get('/cache/create', fn() => redirect()->route('cache.create'));

// Migration Utility Route
Route::get('/run-migrate', function () {
    try {
        Artisan::call('migrate', ['--force' => true]);
        $output = Artisan::output();
        return response()->json([
            'status' => 'success',
            'message' => 'Migration executed successfully.',
            'output' => $output
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Migration failed: ' . $e->getMessage()
        ], 500);
    }
})->name('migrate.run');

Route::get('/migrate', fn() => redirect()->route('migrate.run'));

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard', [
            'partiesCount' => \App\Models\Party::count(),
            'suppliersCount' => \App\Models\Supplier::count(),
            'rollSizesCount' => \App\Models\RollSize::count(),
            'loomsCount' => \App\Models\LoomNumber::count(),
            'colorsCount' => \App\Models\FabricColor::count(),
        ]);
    })->name('dashboard');

    // Profile Settings
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');

    // Store Prefix
    Route::prefix('store')->name('store.')->group(function () {
        // Item Master
        Route::get('/itemmaster', [ItemMasterController::class, 'index'])->name('itemmaster.index');
        Route::get('/itemmaster/data', [ItemMasterController::class, 'data'])->name('itemmaster.data');
        Route::get('/itemmaster/create', [ItemMasterController::class, 'create'])->name('itemmaster.create');
        Route::post('/itemmaster', [ItemMasterController::class, 'store'])->name('itemmaster.store');
        Route::get('/itemmaster/{id}/edit', [ItemMasterController::class, 'edit'])->name('itemmaster.edit');
        Route::put('/itemmaster/{id}', [ItemMasterController::class, 'update'])->name('itemmaster.update');
        Route::delete('/itemmaster/{id}', [ItemMasterController::class, 'destroy'])->name('itemmaster.destroy');

        // MRL Entry
        Route::get('/mrlentry', [MRLEntryController::class, 'index'])->name('mrlentry.index');
        Route::get('/mrlentry/data', [MRLEntryController::class, 'data'])->name('mrlentry.data');
        Route::get('/mrlentry/create', [MRLEntryController::class, 'create'])->name('mrlentry.create');
        Route::post('/mrlentry', [MRLEntryController::class, 'store'])->name('mrlentry.store');
        Route::get('/mrlentry/{id}/edit', [MRLEntryController::class, 'edit'])->name('mrlentry.edit');
        Route::put('/mrlentry/{id}', [MRLEntryController::class, 'update'])->name('mrlentry.update');
        Route::delete('/mrlentry/{id}', [MRLEntryController::class, 'destroy'])->name('mrlentry.destroy');

        // Quotation
        Route::get('/quotation', [QuotationController::class, 'index'])->name('quotation.index');
        Route::get('/quotation/data', [QuotationController::class, 'data'])->name('quotation.data');
        Route::get('/quotation/create', [QuotationController::class, 'create'])->name('quotation.create');
        Route::get('/quotation/fetch-mrl', [QuotationController::class, 'fetchMrlItems'])->name('quotation.fetchMrl');
        Route::post('/quotation', [QuotationController::class, 'store'])->name('quotation.store');
        Route::get('/quotation/{id}/edit', [QuotationController::class, 'edit'])->name('quotation.edit');
        Route::put('/quotation/{id}', [QuotationController::class, 'update'])->name('quotation.update');
        Route::delete('/quotation/{id}', [QuotationController::class, 'destroy'])->name('quotation.destroy');
        Route::get('/quotation/{id}/print', [QuotationController::class, 'print'])->name('quotation.print');
    });

    // Reports Prefix
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/summary', [SummaryReportController::class, 'index'])->name('summary.index');
        Route::get('/monthly-production', [MonthlyProductionReportController::class, 'index'])->name('monthly_production.index');
        Route::get('/monthly-dispatch-transfer', [MonthlyDispatchTransferReportController::class, 'index'])->name('monthly_dispatch_transfer.index');
        Route::get('/daily-dispatch-transfer', [DailyDispatchTransferReportController::class, 'index'])->name('daily_dispatch_transfer.index');
    });
    // Alias route for backward compatibility
    Route::get('/monthly-dispatch-transfer', [MonthlyDispatchTransferReportController::class, 'index'])->name('monthly_dispatch_transfer.index');

    // Inventories Prefix
    Route::prefix('inventories')->name('inventories.')->group(function () {
        // Production (table: intransaction, TransactionType = 1)
        Route::get('/production', [ProductionController::class, 'index'])->name('production.index');
        Route::get('/production/data', [ProductionController::class, 'data'])->name('production.data');
        Route::get('/production/options', [ProductionController::class, 'getOptions'])->name('production.options');
        Route::get('/production/create', [ProductionController::class, 'create'])->name('production.create');
        Route::post('/production', [ProductionController::class, 'store'])->name('production.store');
        Route::get('/production/{id}/edit', [ProductionController::class, 'edit'])->name('production.edit');
        Route::put('/production/{id}', [ProductionController::class, 'update'])->name('production.update');
        Route::delete('/production/{id}', [ProductionController::class, 'destroy'])->name('production.destroy');

        // Purchase (table: intransaction, TransactionType = 2)
        Route::get('/purchase', [PurchaseController::class, 'index'])->name('purchase.index');
        Route::get('/purchase/data', [PurchaseController::class, 'data'])->name('purchase.data');
        Route::get('/purchase/create', [PurchaseController::class, 'create'])->name('purchase.create');
        Route::post('/purchase', [PurchaseController::class, 'store'])->name('purchase.store');
        Route::get('/purchase/{id}/edit', [PurchaseController::class, 'edit'])->name('purchase.edit');
        Route::put('/purchase/{id}', [PurchaseController::class, 'update'])->name('purchase.update');
        Route::delete('/purchase/{id}', [PurchaseController::class, 'destroy'])->name('purchase.destroy');

        // Transfer (table: intransfer)
        Route::get('/transfer', [TransferController::class, 'index'])->name('transfer.index');
        Route::get('/transfer/data', [TransferController::class, 'data'])->name('transfer.data');
        Route::get('/transfer/get-rolls', [TransferController::class, 'getRolls'])->name('transfer.getRolls');
        Route::get('/transfer/create', [TransferController::class, 'create'])->name('transfer.create');
        Route::post('/transfer', [TransferController::class, 'store'])->name('transfer.store');
        Route::get('/transfer/{id}/edit', [TransferController::class, 'edit'])->name('transfer.edit');
        Route::put('/transfer/{id}', [TransferController::class, 'update'])->name('transfer.update');
        Route::delete('/transfer/{id}', [TransferController::class, 'destroy'])->name('transfer.destroy');

        // Dispatch (table: indispatch)
        Route::get('/dispatch', [DispatchController::class, 'index'])->name('dispatch.index');
        Route::get('/dispatch/data', [DispatchController::class, 'data'])->name('dispatch.data');
        Route::get('/dispatch/options', [DispatchController::class, 'getOptions'])->name('dispatch.options');
        Route::get('/dispatch/create', [DispatchController::class, 'create'])->name('dispatch.create');
        Route::post('/dispatch', [DispatchController::class, 'store'])->name('dispatch.store');
        Route::get('/dispatch/{id}/edit', [DispatchController::class, 'edit'])->name('dispatch.edit');
        Route::put('/dispatch/{id}', [DispatchController::class, 'update'])->name('dispatch.update');
        Route::delete('/dispatch/{id}', [DispatchController::class, 'destroy'])->name('dispatch.destroy');
    });

    // Masters Prefix
    Route::prefix('masters')->name('masters.')->group(function () {
        // Party
        Route::get('/party', [PartyController::class, 'index'])->name('party.index');
        Route::get('/party/data', [PartyController::class, 'data'])->name('party.data');
        Route::get('/party/create', [PartyController::class, 'create'])->name('party.create');
        Route::post('/party', [PartyController::class, 'store'])->name('party.store');
        Route::get('/party/{id}/edit', [PartyController::class, 'edit'])->name('party.edit');
        Route::put('/party/{id}', [PartyController::class, 'update'])->name('party.update');
        Route::delete('/party/{id}', [PartyController::class, 'destroy'])->name('party.destroy');

        // Supplier
        Route::get('/supplier', [SupplierController::class, 'index'])->name('supplier.index');
        Route::get('/supplier/data', [SupplierController::class, 'data'])->name('supplier.data');
        Route::get('/supplier/create', [SupplierController::class, 'create'])->name('supplier.create');
        Route::post('/supplier', [SupplierController::class, 'store'])->name('supplier.store');
        Route::get('/supplier/{id}/edit', [SupplierController::class, 'edit'])->name('supplier.edit');
        Route::put('/supplier/{id}', [SupplierController::class, 'update'])->name('supplier.update');
        Route::delete('/supplier/{id}', [SupplierController::class, 'destroy'])->name('supplier.destroy');

        // RollSize
        Route::get('/rollsize', [RollSizeController::class, 'index'])->name('rollsize.index');
        Route::get('/rollsize/data', [RollSizeController::class, 'data'])->name('rollsize.data');
        Route::get('/rollsize/create', [RollSizeController::class, 'create'])->name('rollsize.create');
        Route::post('/rollsize', [RollSizeController::class, 'store'])->name('rollsize.store');
        Route::get('/rollsize/{id}/edit', [RollSizeController::class, 'edit'])->name('rollsize.edit');
        Route::put('/rollsize/{id}', [RollSizeController::class, 'update'])->name('rollsize.update');
        Route::delete('/rollsize/{id}', [RollSizeController::class, 'destroy'])->name('rollsize.destroy');

        // LoomNumber
        Route::get('/loomnumber', [LoomNumberController::class, 'index'])->name('loomnumber.index');
        Route::get('/loomnumber/data', [LoomNumberController::class, 'data'])->name('loomnumber.data');
        Route::get('/loomnumber/create', [LoomNumberController::class, 'create'])->name('loomnumber.create');
        Route::post('/loomnumber', [LoomNumberController::class, 'store'])->name('loomnumber.store');
        Route::get('/loomnumber/{id}/edit', [LoomNumberController::class, 'edit'])->name('loomnumber.edit');
        Route::put('/loomnumber/{id}', [LoomNumberController::class, 'update'])->name('loomnumber.update');
        Route::delete('/loomnumber/{id}', [LoomNumberController::class, 'destroy'])->name('loomnumber.destroy');

        // FabricColor
        Route::get('/fabriccolor', [FabricColorController::class, 'index'])->name('fabriccolor.index');
        Route::get('/fabriccolor/data', [FabricColorController::class, 'data'])->name('fabriccolor.data');
        Route::get('/fabriccolor/create', [FabricColorController::class, 'create'])->name('fabriccolor.create');
        Route::post('/fabriccolor', [FabricColorController::class, 'store'])->name('fabriccolor.store');
        Route::get('/fabriccolor/{id}/edit', [FabricColorController::class, 'edit'])->name('fabriccolor.edit');
        Route::put('/fabriccolor/{id}', [FabricColorController::class, 'update'])->name('fabriccolor.update');
        Route::delete('/fabriccolor/{id}', [FabricColorController::class, 'destroy'])->name('fabriccolor.destroy');
    });
});
