<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlannedRecipe extends Model
{
    protected $fillable = [
        'user_id',
        'recipe_id',
        'date',
        'position',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
