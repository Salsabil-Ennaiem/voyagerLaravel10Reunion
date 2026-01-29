<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReunionController;
use App\Http\Controllers\OrganisationController;
use Illuminate\Support\Facades\Auth;

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
    if (Auth::check()) {
        return redirect('/reunion');
    }
    return redirect('/admin/login');
});

Route::get('/logout', function () {
    Auth::logout();
    return redirect('/admin/login');
})->name('logout');

Route::group(['prefix' => 'admin'], function () {
    Route::post('login', [\App\Http\Controllers\CustomVoyagerAuthController::class, 'login'])->name('voyager.login');
    Voyager::routes();
});


Route::middleware(['auth'])->group(function () {
    Route::get('/reunion', function () {
        return view('welcome');
    })->name('reunion');

    Route::controller(ReunionController::class)->group(function () {
        Route::get('/reunions/list', 'list')->name('reunions.list');
        Route::get('/reunions/export', 'export')->name('reunions.export');
        Route::get('/reunion-options', 'getOptions')->name('reunions.options');
        Route::post('/reunions', 'store')->middleware('throttle:60,1')->name('reunions.store');
        Route::get('/reunions/{reunion}/edit', 'edit')->name('reunions.edit');
        Route::put('/reunions/{reunion}', 'update')->name('reunions.update');
        Route::delete('/reunions/{reunion}', 'destroy')->name('reunions.destroy');
        Route::get('/notifications',  'getNotifications')->name('notifications.index');
        Route::post('/notifications/{id}/read','markNotificationAsRead')->name('notifications.read');
    });

    Route::controller(OrganisationController::class)->group(function () {
        Route::get('/organisations', 'index')->name('organisations.list');
        Route::get('/organisations/my', 'myOrganisation')->name('organisations.my');
        Route::post('/organisations/switch', 'switch')->name('organisations.switch');
        Route::get('/organisations/{organisation}', 'show')->name('organisations.show');
        Route::post('/organisations/{organisation}', 'update')->name('organisations.update');
        Route::post('/organisations/{organisation}/members', 'addMember')->name('organisations.members.add');
        Route::post('/organisations/{organisation}/members/{user}', 'updateMember')->name('organisations.members.update');
        Route::delete('/organisations/{organisation}/members/{user}', 'removeMember')->name('organisations.members.remove');
    });
});



