<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

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
