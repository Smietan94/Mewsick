<?php

declare(strict_types=1);

namespace App\Enum;

enum CatHat: int
{
    case FLOWER_CROWN = 0;
    case FEDORA       = 1;
    case BANDANA      = 2;
    case SHORT_BEANIE = 3;
    case SAFARI_HAT   = 4;
    case FIVE_PANEL   = 5;

    /**
     * toString
     *
     * @return string
     */
    public function toString(): string
    {
        return match($this) {
            self::FEDORA       => 'fedora',
            self::BANDANA      => 'bandana',
            self::SHORT_BEANIE => 'short beanie',
            self::SAFARI_HAT   => 'safari hat',
            self::FIVE_PANEL   => 'five panel',
            default            => 'flower crown'
        };
    }

    /**
     * toInt
     *
     * @return int
     */
    public function toInt(): int
    {
        return match($this) {
            self::FEDORA       => 1,
            self::BANDANA      => 2,
            self::SHORT_BEANIE => 3,
            self::SAFARI_HAT   => 4,
            self::FIVE_PANEL   => 5,
            default            => 0
        };
    }

    /**
     * toQuery
     *
     * @return int
     */
    public function toQuery(): string
    {
        return match($this) {
            self::FEDORA       => '30s blues',
            self::BANDANA      => 'gangsta rap',
            self::SHORT_BEANIE => 'midwest emo',
            self::SAFARI_HAT   => 'folk',
            self::FIVE_PANEL   => 'dreampop',
            default            => 'indie'
        };
    }
}