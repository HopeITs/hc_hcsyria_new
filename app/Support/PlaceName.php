<?php

namespace App\Support;

/*
 * A place's name as the public site writes it. The records spell Idlib
 * "Idleb"; legacy's Place model rewrote it on every read, and the generated
 * Place model here cannot, so what reads a name asks for it through this.
 */
final class PlaceName
{
    public static function of(?string $name): string
    {
        return str_ireplace('Idleb', 'Idlib', (string) $name);
    }
}
