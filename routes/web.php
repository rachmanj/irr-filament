<?php

use App\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return redirect('/admin');
});

// Custom direct import routes
Route::get('/import/ito', [ImportController::class, 'importForm'])->name('ito.import.form');
Route::post('/import/ito', [ImportController::class, 'import'])->name('ito.import');
Route::get('/import/ito/template', [ImportController::class, 'downloadTemplate'])->name('ito.download-template');
