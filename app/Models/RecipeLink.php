<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeLink extends Model
{
    /** @use HasFactory<\Database\Factories\RecipeLinkFactory> */
    use HasFactory;

    protected $fillable = [
        'url',
        'position',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    protected function domain(): Attribute
    {
        return Attribute::make(
            get: fn () => str_replace(
                'www.',
                '',
                parse_url($this->url, PHP_URL_HOST) ?? $this->url
            )
        );
    }
}
