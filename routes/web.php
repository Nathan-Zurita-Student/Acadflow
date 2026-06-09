<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/{any}', function () {
    return Inertia::render('App');
})->where('any', '.*');
