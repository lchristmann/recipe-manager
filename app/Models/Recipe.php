<?php

namespace App\Models;

use App\Enums\Enums\RecipeImageType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    /** @use HasFactory<\Database\Factories\RecipeFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'ingredients',
        'instructions',
        'position',
    ];

    public function cookbook(): BelongsTo
    {
        return $this->belongsTo(Cookbook::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(RecipeLink::class)
            ->orderBy('position');
    }

    public function images(): HasMany
    {
        return $this->hasMany(RecipeImage::class)
            ->orderBy('position');
    }

    public function photoImages(): HasMany
    {
        return $this->images()->where('type', RecipeImageType::PHOTO->value);
    }

    public function recipeImages(): HasMany
    {
        return $this->images()->where('type', RecipeImageType::RECIPE->value);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)
            ->orderBy('name');
    }

    public function plannedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'planned_recipes')
            ->withPivot(['date', 'type', 'position'])
            ->orderByPivot('position');
    }
}
