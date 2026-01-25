<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
   Route::get('/', function () {
       return view('dashboard');
   })->name('home');

    Route::livewire('/cookbooks', 'pages::recipe-books.index')
        ->name('cookbooks.index');

    Route::livewire('/cookbooks/{cookbook}', 'pages::recipe-books.show')
        ->can('view', 'cookbook')
        ->name('cookbooks.show');

    Route::livewire('/users', 'pages::users.index')
        ->can('viewAny', User::class)
        ->name('users.index');
});

require __DIR__.'/settings.php';
