<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cookbook extends Model
{
    /** @use HasFactory<\Database\Factories\CookbookFactory> */
    use HasFactory;

    protected $casts = [
        'community' => 'boolean',
        'private' => 'boolean',
    ];

    protected $fillable = [
        'title',
        'subtitle',
        'community',
        'private',
        'position',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class)
            ->orderBy('position');
    }
}
