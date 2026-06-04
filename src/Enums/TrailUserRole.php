<?php

namespace Trail\Enums;

enum TrailUserRole: string
{
    case Support = 'support';
    case Developer = 'developer';
    case Admin = 'admin';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function canViewTechnicalContext(): bool
    {
        return in_array($this, [self::Developer, self::Admin], true);
    }
}
