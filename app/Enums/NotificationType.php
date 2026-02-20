<?php

namespace App\Enums;

enum NotificationType: string
{
    case COMMENT = 'comment';
    case REPLY = 'reply';
}
