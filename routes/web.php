<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventController;

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

Route::get('/reunion', function () {
    return view('welcome');
});
Route::view('/calendrier', 'calendar');
Route::view('/Untitled', 'Untitled');

// Custom Routes
use App\Http\Controllers\ReunionController;
use App\Http\Controllers\OrganisationController;

Route::middleware(['auth'])->group(function () {
    Route::get('/reunions/list', [ReunionController::class, 'list'])->name('reunions.list');
    Route::get('/organisations/data', [ReunionController::class, 'organisations'])->name('organisations.data'); // Renamed from list to avoid conflict
    Route::get('/reunion-options', [ReunionController::class, 'getOptions'])->name('reunions.options');
    Route::post('/reunions', [ReunionController::class, 'store'])->name('reunions.store');
    Route::get('/notifications', [ReunionController::class, 'getNotifications'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [ReunionController::class, 'markNotificationAsRead'])->name('notifications.read');

    // Organisation Management
    Route::get('/organisations', [OrganisationController::class, 'index'])->name('organisations.list');
    Route::get('/organisations/my', [OrganisationController::class, 'myOrganisation'])->name('organisations.my');
    Route::get('/organisations/{organisation}', [OrganisationController::class, 'show'])->name('organisations.show');
    Route::post('/organisations/{organisation}', [OrganisationController::class, 'update'])->name('organisations.update');
});

Route::post('/logout', function () {
    Auth::logout();
    return redirect('/admin/login');
})->name('logout');



Route::group(['prefix' => 'admin'], function () {
    Route::post('login', [\App\Http\Controllers\CustomVoyagerAuthController::class, 'login'])->name('voyager.login');
    Voyager::routes();
});
