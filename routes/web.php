<?php

use App\Http\Controllers\CalendarController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
Route::post('/events', [CalendarController::class, 'store']);
Route::put('/events/{event}', [CalendarController::class, 'update']);
Route::delete('/events/{event}', [CalendarController::class, 'destroy']);
