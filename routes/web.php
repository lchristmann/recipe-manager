<?php

use App\Models\RecipeImage;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
   Route::get('/', function () {
       return view('dashboard');
   })->name('home');

    Route::livewire('/cookbooks', 'pages::cookbooks.index')
        ->name('cookbooks.index');

    Route::livewire('/cookbooks/{cookbook}', 'pages::cookbooks.show')
        ->can('view', 'cookbook')
        ->name('cookbooks.show');

    Route::livewire('/recipes/create', 'pages::recipes.create')
        ->name('recipes.create');

    Route::livewire('/recipes/{recipe}', 'pages::recipes.show')
        ->can('view', 'recipe')
        ->name('recipes.show');

    Route::livewire('/users', 'pages::users.index')
        ->can('viewAny', User::class)
        ->name('users.index');

    Route::get('/recipe-images/{recipeImage}', function (RecipeImage $recipeImage) {
        abort_unless(auth()->user()->can('view', $recipeImage->recipe), 403);

        return Storage::response($recipeImage->path);
    })->name('recipe-images.show');
});

require __DIR__.'/settings.php';
