<?php

declare(strict_types=1);

namespace App\Enum;

enum CatColor: int
{
    case BLACK    = 0;
    case TUXEDO   = 1;
    case TRICOLOR = 2;
    case ORANGE   = 3;
    case TABBY    = 4;
    case WHITE    = 5;

    /**
     * toString
     *
     * @return string
     */
    public function toString(): string
    {
        return match($this) {
            self::TUXEDO   => 'tuxedo',
            self::TRICOLOR => 'tricolor',
            self::ORANGE   => 'orange',
            self::TABBY    => 'tabby',
            self::WHITE    => 'white',
            default        => 'black'
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
            self::TUXEDO   => 1,
            self::TRICOLOR => 2,
            self::ORANGE   => 3,
            self::TABBY    => 4,
            self::WHITE    => 5,
            default        => 0
        };
    }

    /**
     * toQuery
     *
     * @return string
     */
    public function toQuery(): string
    {
        return match($this) {
            self::TUXEDO   => 'hyperpop',
            self::TRICOLOR => 'neo folk',
            self::ORANGE   => 'stoner metal',
            self::TABBY    => 'alternative',
            self::WHITE    => '90s techno',
            default        => 'witch house'
        };
    }
}