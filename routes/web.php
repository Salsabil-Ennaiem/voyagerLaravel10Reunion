<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReunionController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\clController;


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

Route::get('/', function () {
    return view('welcome');
});


Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
  
});
Route::view('/calendrier', 'cl');
