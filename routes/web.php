<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TemperatureController;
use App\Http\Controllers\MainController;

Route::get('/', function () {
    return redirect('/dashboard/v2');
});

Route::get('/dashboard/v2', [TemperatureController::class, 'dashboard'])->name('dashboard-v2');

Route::get('/generate-random-data', [TemperatureController::class, 'generateRandomData'])->name('generate.random.data');
Route::post('/update-temperature', [TemperatureController::class, 'update'])->name('update.temperature');

Route::get('/login/v2', [MainController::class, 'loginV2'])->name('login-v2');
Route::get('/helper/css', [MainController::class, 'helperCSS'])->name('helper-css');
