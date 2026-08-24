<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('preview')->group(function () {
    Route::get('/reset-password', function () {
        return view('emails.reset-password');
    });
});
