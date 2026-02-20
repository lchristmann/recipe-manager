<?php

namespace App\Models;

use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentNotification extends Model
{
    protected $fillable = [
        'user_id',
        'triggered_by',
        'comment_id',
        'type',
        'read',
    ];

    protected $casts = [
        'type' => NotificationType::class,
        'read' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }
}
