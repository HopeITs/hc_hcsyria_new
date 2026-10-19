<?php

namespace App\Enums;

/*
 * Where a micro-project stands, from the three legacy columns that say it:
 * MPE_Published (0 waiting, 1 online, 2 cancelled), hidden, and MPE_Done
 * (1 funded, 2 cancelled). The legacy admin showed these as tabs; one project
 * can be in more than one tab (371 online projects are also marked cancelled),
 * so the list's tabs are filters and this is only the single word on the row.
 */
enum ProjectStatus: string
{
    case Online = 'online';
    case Pending = 'pending';
    case Hidden = 'hidden';
    case Cancelled = 'cancelled';

    public static function of(int|string $published, int|string $done, int|bool $hidden): self
    {
        return match (true) {
            (int) $published === 1 => self::Online,
            (int) $published === 2 || (int) $done === 2 => self::Cancelled,
            (bool) $hidden => self::Hidden,
            default => self::Pending,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Pending => 'Pending publish',
            self::Hidden => 'Hidden',
            self::Cancelled => 'Cancelled',
        };
    }

    /** The pill colour: good, warn, grey or crit (resources/css/admin). */
    public function tone(): string
    {
        return match ($this) {
            self::Online => 'good',
            self::Pending => 'warn',
            self::Hidden => 'grey',
            self::Cancelled => 'crit',
        };
    }
}
