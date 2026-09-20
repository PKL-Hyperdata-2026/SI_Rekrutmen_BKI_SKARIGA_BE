<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('preview')->group(function () {
    Route::get('/reset-password', function () {
        return view('emails.reset-password');
    });

    Route::get('/job-vacancy-notification', function () {
        return view('emails.job-vacancy-notification', [
            'title' => 'Lowongan Baru: Frontend Engineer',
            'position' => 'Frontend Engineer',
            'companyName' => 'PT Hyperdata Solusi Teknologi',
            'userName' => 'Ahmad Fauzi',
            'bodyMessage' => 'PT Hyperdata Solusi Teknologi membuka lowongan baru untuk posisi Frontend Engineer. Kuota: 5 orang.',
            'quota' => 5,
            'actionUrl' => (string) config('app.frontend_url').'/student/lowongan',
        ]);
    });
});
