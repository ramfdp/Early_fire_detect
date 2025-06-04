<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TemperatureController;
use App\Http\Controllers\DropdownController;
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

Route::get('/', [TemperatureController::class, 'dashboard'])->name('dashboard');
Route::get('/generate-random-data', [TemperatureController::class, 'generateRandomData'])->name('generate.random.data');
Route::post('/update-temperature', [TemperatureController::class, 'updateTemperature'])->name('update.temperature');
Route::get('/dropdown', [DropdownController::class, 'index'])->name('history');
Route::get('/history', [TemperatureController::class, 'history'])->name('history');


Route::get('/dashboard-v2', function () {
    return view('dashboard-v2');
})->middleware(['auth', 'verified'])->name('dashboard-v2');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/api/temperatures', [TemperatureController::class, 'getLatestTemperatures']);
require __DIR__.'/auth.php';
