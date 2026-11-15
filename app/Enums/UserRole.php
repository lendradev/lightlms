<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case Instructor = 'instructor';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Student',
            self::Instructor => 'Instructor',
            self::Admin => 'Administrator',
        };
    }

    public function canTeach(): bool
    {
        return $this === self::Instructor || $this === self::Admin;
    }

    public function canAdminister(): bool
    {
        return $this === self::Admin;
    }
}
