<?php

namespace dokuwiki\plugin\colorplus;

/**
 * Parsing and validation of the color specifications used in the tags
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  LukaW
 */
class ColorSpec
{
    /**
     * Split a color specification "first/second" or "first:second" into its parts
     *
     * The slash is the separator unless the specification contains a colon.
     *
     * @param string $spec
     * @return array [first color, second color], each null if empty or invalid
     */
    public static function split($spec)
    {
        $parts = (strpbrk($spec, ':') === false) ? explode('/', $spec) : explode(':', $spec);
        return [self::clean($parts[0]), self::clean($parts[1] ?? null)];
    }

    /**
     * Trimmed color value, or null if it is empty or invalid
     *
     * This is cut price validation, it only makes sure nothing harmful gets
     * into the HTML. Browsers ignore invalid colors anyway.
     *
     * @param string|null $color
     * @return string|null
     */
    public static function clean($color)
    {
        $color = trim((string) $color);
        if ($color === '' || strpbrk($color, '"\'<>&;\\') !== false) return null;
        return $color;
    }
}
