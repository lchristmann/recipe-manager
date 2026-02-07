<?php

namespace App\Models;

use App\Enums\PlannerMealType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlannedRecipe extends Model
{
    protected $fillable = [
        'user_id',
        'recipe_id',
        'date',
        'type',
        'position',
    ];

    protected $casts = [
        'date' => 'date',
        'type' => PlannerMealType::class,
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
