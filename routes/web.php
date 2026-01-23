<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
   Route::get('/', function () {
       return view('dashboard');
   })->name('home');
});

require __DIR__.'/settings.php';
