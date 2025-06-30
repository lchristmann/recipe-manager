<?php

namespace Database\Seeders;

use App\Models\Folder;
use App\Models\Label;
use App\Models\Rating;
use App\Models\Recipe;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Create an admin user without folders and recipes (he can CRUD on everything anyways)
     * Create a user with 2 public folders, 1 private and having 5 recipes in each of those folders
     * Create another user, that has no own folders, but 2 recipes in each of the other user's public folders
     */
    public function run(): void
    {
        // Clear images from disks before seeding
        Storage::disk('public')->deleteDirectory('images');
        Storage::disk('local')->deleteDirectory('images');

        // 1: Create users first
        User::factory()->admin()->create(['email' => 'admin@example.com']);
        $user = User::factory()->create(['email' => 'test@example.com']);
        $user2 = User::factory()->create([
            'email' => 'test2@example.com',
        ]);

        // 2: Create labels
        Label::factory()->count(2)->create(['user_id' => 1]);
        Label::factory()->count(6)->create(['user_id' => 2]);
        Label::factory()->count(2)->create(['user_id' => 3]);
        $labels = Label::pluck('id');

        // 3: Create 2 public folders with recipes (all belonging to user with id 2)
        $folders = Folder::factory()
            ->count(2)
            ->state(['visibility' => 'public', 'user_id' => $user->id])
            ->has(
                Recipe::factory()
                    ->count(5)
                    ->state(['user_id' => $user->id])
                    ->afterCreating(function (Recipe $recipe) use ($labels) {
                        $recipe->labels()->attach(
                            $labels->random(rand(3, 7))->toArray()
                        );
                    })
            )
            ->create();

        // 4: Create 1 private folder with recipes (all belonging to user with id 2)
        Folder::factory()
            ->state(['visibility' => 'private', 'user_id' => $user->id])
            ->has(
                Recipe::factory()
                    ->count(5)
                    ->state(['visibility' => 'private', 'user_id' => $user->id])
                    ->afterCreating(function (Recipe $recipe) use ($labels) {
                        $recipe->labels()->attach(
                            $labels->random(rand(3, 7))->toArray()
                        );
                    })
            )
            ->create();

        // 5: Have two recipes for user with id 3 in each public folder
        foreach ($folders->pluck('id')->toArray() as $folderId) {
            Recipe::factory()
                ->count(2)
                ->state(['user_id' => $user2->id, 'folder_id' => $folderId])
                ->afterCreating(function (Recipe $recipe) use ($labels) {
                    $recipe->labels()->attach(
                        $labels->random(rand(3, 7))->toArray()
                    );
                })
                ->create();
        }

        $usersIds = User::pluck('id');
        $recipes = Recipe::all();

        // 6: Create ratings for some of the recipes
        foreach ($recipes as $recipe) {
            // Pick up to two of the other users to comment
            $eligibleRaters = $usersIds->filter(fn ($id) => $id !== $recipe->user_id)->shuffle()->take(rand(0, 2));

            foreach ($eligibleRaters as $userId) {
                Rating::factory()->create([
                    'user_id' => $userId,
                    'recipe_id' => $recipe->id
                ]);
            }
        }

        // 7: Create comments for some of the recipes
        foreach ($recipes as $recipe) {
            // Between 0 and 3 comments per recipe
            $count = rand(0, 3);
            for ($i = 0; $i < $count; $i++) {
                Rating::factory()->create([
                    'user_id' => $usersIds->random(),
                    'recipe_id' => $recipe->id
                ]);
            }
        }
    }
}
