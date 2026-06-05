<?php

use Illuminate\Support\Facades\Route;

// SPA catch-all: semua route non-API diarahkan ke React (client-side routing).
Route::get('/{any?}', fn () => view('app'))
    ->where('any', '^(?!api(/|$)).*');
