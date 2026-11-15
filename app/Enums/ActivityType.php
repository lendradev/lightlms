<?php

namespace App\Enums;

enum ActivityType: string
{
    case Reading = 'reading';
    case Video = 'video';
    case Quiz = 'quiz';
    case Assignment = 'assignment';
    case Forum = 'forum';

    public function label(): string
    {
        return match ($this) {
            self::Reading => 'Reading',
            self::Video => 'Video',
            self::Quiz => 'Quiz',
            self::Assignment => 'Assignment',
            self::Forum => 'Forum',
        };
    }
}
