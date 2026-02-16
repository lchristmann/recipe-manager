<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tag extends Model
{
    /** @use HasFactory<\Database\Factories\TagFactory> */
    use HasFactory;

    protected $fillable = ['name'];

    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class);
    }

    public function colors(): HasMany
    {
        return $this->hasMany(TagColor::class);
    }

    public function colorFor(?int $userId): string
    {
        return $this->colors
            ->firstWhere('user_id', $userId)
            ?->color ?? 'zinc';
    }
}
