<?php

namespace Database\Seeders;

use App\Enums\Enums\RecipeImageType;
use App\Models\Recipe;
use App\Models\Cookbook;
use App\Models\RecipeImage;
use App\Models\RecipeLink;
use App\Models\Tag;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()
            ->admin()
            ->create([
                'name' => 'Admin',
                'email' => 'admin@admin.com',
                'password' => Hash::make('admin'),
            ]);

        $user = User::factory()
            ->create([
                'name' => 'User',
                'email' => 'user@user.com',
            ]);

        User::factory()
            ->create([
                'name' => 'Test',
                'email' => 'test@test.com',
            ]);

        $tags = Tag::factory()->count(8)->create();

        $adminBooks = collect([
            Cookbook::factory()->private()->create([
                'user_id' => $admin->id,
                'title' => 'Private Recipes',
            ]),
            Cookbook::factory()->create([
                'user_id' => $admin->id,
                'title' => 'Public Recipes',
            ]),
        ]);

        $userBooks = collect([
            Cookbook::factory()->community()->create([
                'user_id' => $user->id,
                'title' => 'Community Recipes',
            ]),
            Cookbook::factory()->create([
                'user_id' => $user->id,
                'title' => 'Public Recipes',
            ]),
        ]);

        $adminBooks
            ->merge($userBooks)
            ->each(function (Cookbook $cookbook) use ($tags) {
                Recipe::factory()
                    ->count(60)
                    ->create([
                        'cookbook_id' => $cookbook->id,
                    ])
                    ->each(function (Recipe $recipe) use ($tags) {
                        // Attach tags
                        $recipe->tags()->attach(
                            $tags->random(rand(1, 4))->pluck('id')
                        );

                        $linkCount = rand(0, 2);
                        if ($linkCount > 0) {
                            RecipeLink::factory()
                                ->count($linkCount)
                                ->create(['recipe_id' => $recipe->id,]);
                        }

                        // Recipe images (0–2)
                        $recipeImageCount = rand(0, 2);
                        if ($recipeImageCount > 0) {
                            RecipeImage::factory()
                                ->count($recipeImageCount)
                                ->recipe()
                                ->create(['recipe_id' => $recipe->id]);
                        }

                        // Photo images (0–2)
                        $photoImageCount = rand(0, 2);
                        if ($photoImageCount > 0) {
                            RecipeImage::factory()
                                ->count($photoImageCount)
                                ->photo()
                                ->create(['recipe_id' => $recipe->id]);
                        }
                    });
            });

        // ------------ Normalize (0-based, without gaps) positions ------------

        // Community cookbooks
        Cookbook::query()
            ->where('community', true)
            ->orderBy('id')
            ->get()
            ->values()
            ->each(fn ($book, $index) =>
                $book->update(['position' => $index])
            );

        // Personal cookbooks
        User::all()->each(function (User $user) {
            Cookbook::query()
                ->where('community', false)
                ->where('user_id', $user->id)
                ->orderBy('id')
                ->get()
                ->values()
                ->each(fn ($book, $index) =>
                    $book->update(['position' => $index])
                );
        });

        // Recipes within cookbooks
        Cookbook::query()
            ->with('recipes')
            ->get()
            ->each(function (Cookbook $cookbook) {
                $cookbook->recipes
                    ->sortBy('id')
                    ->values()
                    ->each(fn (Recipe $recipe, $index) =>
                        $recipe->update(['position' => $index])
                    );
            });

        // Recipe images per type within recipes
        Recipe::query()
            ->with('images')
            ->get()
            ->each(function (Recipe $recipe) {
                collect([
                    RecipeImageType::PHOTO,
                    RecipeImageType::RECIPE,
                ])->each(function (RecipeImageType $type) use ($recipe) {
                    $recipe->images
                        ->where('type', $type)
                        ->sortBy('id')
                        ->values()
                        ->each(fn ($image, $index) =>
                            $image->update(['position' => $index,])
                        );
                });
            });

        // Recipe links per recipe
        Recipe::query()
            ->with('links')
            ->get()
            ->each(function (Recipe $recipe) {
                $recipe->links
                    ->sortBy('id')
                    ->values()
                    ->each(fn ($link, $index) =>
                        $link->update(['position' => $index])
                    );
            });
    }
}
