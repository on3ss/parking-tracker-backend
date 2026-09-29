<?php

namespace App\Filament\Support;

/**
 * Responsive column presets for Filament sections.
 *
 * Pass one of these to Section::columns() so density is defined
 * once rather than copy-pasted per section.
 */
final class Grid
{
    /** @var array<string, int> */
    public const TWO = ['default' => 1, 'sm' => 2, 'lg' => 2];

    /** @var array<string, int> */
    public const THREE = ['default' => 1, 'sm' => 2, 'lg' => 3];

    /** @var array<string, int> */
    public const FOUR = ['default' => 1, 'sm' => 2, 'lg' => 4];

    private function __construct() {}
}
