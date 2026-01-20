<?php

namespace Database\Seeders;

use App\Models\Recipe;
use App\Models\RecipeBook;
use App\Models\RecipeImage;
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
            RecipeBook::factory()->private()->create([
                'user_id' => $admin->id,
                'title' => 'Private Recipes',
            ]),
            RecipeBook::factory()->create([
                'user_id' => $admin->id,
                'title' => 'Public Recipes',
            ]),
        ]);

        $userBooks = collect([
            RecipeBook::factory()->community()->create([
                'user_id' => $user->id,
                'title' => 'Community Recipes',
            ]),
            RecipeBook::factory()->create([
                'user_id' => $user->id,
                'title' => 'Public Recipes',
            ]),
        ]);

        $adminBooks
            ->merge($userBooks)
            ->each(function (RecipeBook $book) use ($tags) {
                Recipe::factory()
                    ->count(6)
                    ->create([
                        'recipe_book_id' => $book->id,
                    ])
                    ->each(function (Recipe $recipe) use ($tags) {
                        // Attach tags
                        $recipe->tags()->attach(
                            $tags->random(rand(1, 4))->pluck('id')
                        );

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
    }
}
