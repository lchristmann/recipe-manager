<?php

namespace Database\Seeders;

use App\Constants\StorageConstants;
use App\Enums\RecipeImageType;
use App\Models\Comment;
use App\Models\Cookbook;
use App\Models\Recipe;
use App\Models\RecipeImage;
use App\Models\RecipeLink;
use App\Models\Tag;
use App\Models\TagColor;
use App\Models\User;
use App\Support\Image\UserImageProcessor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Delete and recreate the image upload folders, so previously seeded/uploaded files are wiped (but keep .gitignore files)
        foreach ([StorageConstants::PHOTO_IMAGES, StorageConstants::RECIPE_IMAGES, StorageConstants::USER_IMAGES] as $folder) {
            $gitignorePath = "{$folder}/.gitignore";
            $gitignoreContents = Storage::exists($gitignorePath) ? Storage::get($gitignorePath) : "*\n!.gitignore\n";

            Storage::deleteDirectory($folder);
            Storage::makeDirectory($folder);

            Storage::put($gitignorePath, $gitignoreContents);

            chmod(storage_path("app/private/{$folder}"), 0775);
        }

        $adminImagePath = database_path('seeders/files/sample-user-image-1.jpg'); // https://www.pexels.com/photo/man-wearing-blue-crew-neck-t-shirt-2379005/
        $admin = User::factory()
            ->admin()
            ->create([
                'name' => 'Admin',
                'email' => 'admin@admin.com',
                'password' => Hash::make('admin'),
                'image_path' => UserImageProcessor::processSeedImage($adminImagePath),
            ]);

        $userImagePath  = database_path('seeders/files/sample-user-image-2.jpg'); // https://www.pexels.com/photo/closeup-photo-of-woman-with-brown-coat-and-gray-top-733872/
        $user = User::factory()
            ->create([
                'name' => 'User',
                'email' => 'user@user.com',
                'image_path' => UserImageProcessor::processSeedImage($userImagePath),
            ]);

        User::factory()
            ->create([
                'name' => 'Test',
                'email' => 'test@test.com',
            ]);

        $tags = Tag::factory()->count(8)->create();
        $tags->each(function (Tag $tag) {
            TagColor::create([
                'tag_id' => $tag->id,
                'user_id' => null, // community scope
                'color' => fake()->randomElement(TagColor::COLORS),
            ]);
        });

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

        $users = User::all();

        $adminBooks
            ->merge($userBooks)
            ->each(function (Cookbook $cookbook) use ($tags, $users) {
                Recipe::factory()
                    ->count(40)
                    ->create([
                        'cookbook_id' => $cookbook->id,
                        'user_id' => $cookbook->user_id,
                    ])
                    ->each(function (Recipe $recipe) use ($tags, $users) {
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

                        // Comments and replies
                        $topLevelComments = Comment::factory()
                            ->count(rand(0, 3))
                            ->create([
                                'recipe_id' => $recipe->id,
                                'user_id' => $users->random()->id,
                            ]);
                        $topLevelComments->each(function (Comment $comment) use ($users, $recipe) {
                            Comment::factory()
                                ->count(rand(0, 3))
                                ->create([
                                    'recipe_id' => $recipe->id,
                                    'parent_id' => $comment->id,
                                    'user_id' => $users->random()->id,
                                ]);
                        });
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
