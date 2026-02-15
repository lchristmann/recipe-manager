<?php

use App\Http\Controllers\HomeRedirectController;
use App\Http\Controllers\RecipeImageController;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
   Route::get('/', HomeRedirectController::class)
       ->name('home');

    Route::livewire('/cookbooks', 'pages::cookbooks.index')
        ->name('cookbooks.index');

    Route::livewire('/cookbooks/{cookbook}', 'pages::cookbooks.show')
        ->can('view', 'cookbook')
        ->name('cookbooks.show');

    Route::livewire('/recipes/create', 'pages::recipes.create')
        ->name('recipes.create');

    Route::livewire('/recipes/{recipe}/edit', 'pages::recipes.edit')
        ->name('recipes.edit');

    Route::livewire('/recipes/{recipe}', 'pages::recipes.show')
        ->can('view', 'recipe')
        ->name('recipes.show');

    Route::livewire('/tags', 'pages::tags.index')
        ->can('viewAny', Tag::class)
        ->name('tags.index');

    Route::livewire('/users', 'pages::users.index')
        ->can('viewAny', User::class)
        ->name('users.index');

    Route::get('/recipe-images/{recipeImage}', RecipeImageController::class)
        ->name('recipe-images.show');

    Route::livewire('/planner', 'pages::planner.index')
        ->name('planner.index');
});

require __DIR__.'/settings.php';
