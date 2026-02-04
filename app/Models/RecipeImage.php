<?php

namespace App\Models;

use App\Enums\RecipeImageType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeImage extends Model
{
    /** @use HasFactory<\Database\Factories\RecipeImageFactory> */
    use HasFactory;

    protected $fillable = [
        'path',
        'type',
        'position',
    ];

    protected $casts = [
        'type' => RecipeImageType::class,
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
