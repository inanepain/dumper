<?php

/**
 * This file is part of the InaneTools package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * PHP version 8.1
 *
 * @author Philip Michael Raab <philip@inane.co.za>
 * @package Inane\String
 *
 * @license MIT
 * @license https://inane.co.za/license/MIT
 *
 * @copyright 2015-2019 Philip Michael Raab <philip@inane.co.za>
 */

declare(strict_types=1);

namespace Inane\Dumper;


/**
 * Highlight Themes
 *
 * @package Inane\Tools
 *
 * @version 0.2.0
 */
enum Theme {
    /**
     * Use current values
     */
    case CURRENT;
    /**
     * The colours straight out the box.
     */
    case DEFAULT;
    /**
     * Somebody's idea of what the default should be.
     */
    case PHP2;
    /**
     * An html styled colour theme.
     */
    case HTML;

    /**
     * Theme property values
     *
     * @return array
     */
    public function settings(): array {
        return match ($this) {
            static::CURRENT => [],
            static::PHP2 => [
                'highlight.comment' => '#008000',
                'highlight.default' => '#000000',
                'highlight.html'    => '#808080',
                'highlight.keyword' => '#0000BB; font-weight: bold',
                'highlight.string'  => '#DD0000'
            ],
            static::HTML => [
                'highlight.comment' => '#008000',
                'highlight.default' => '#CC0000',
                'highlight.html'    => '#000000',
                'highlight.keyword' => '#000000; font-weight: bold',
                'highlight.string'  => '#0000FF'
            ],
            default => [
                'highlight.comment' => '#FF8000',
                'highlight.default' => '#0000BB',
                'highlight.html'    => '#000000',
                'highlight.keyword' => '#007700',
                'highlight.string'  => '#DD0000'
            ],
        };
    }

    /**
     * Apply $this theme
     *
     * @return void
     */
    public function apply(): void {
        static::applyTheme($this);
    }

    /**
     * Apply Theme $theme
     *
     * @param Theme $theme
     *
     * @return void
     */
    public static function applyTheme(Theme $theme): void {
        foreach ($theme->settings() as $key => $val) ini_set($key, $val);
    }
}
