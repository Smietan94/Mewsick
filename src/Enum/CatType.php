<?php

declare(strict_types=1);

namespace App\Enum;

enum CatType: int
{
    case SMOL      = 0;
    case BIG_LONG  = 1;
    case BIG_POTAT = 2;
    case OLD       = 3;

    /**
     * toString
     *
     * @return string
     */
    public function toString(): string
    {
        return match($this) {
            self::BIG_LONG  => 'big long',
            self::BIG_POTAT => 'big potat',
            self::OLD       => 'old',
            default         => 'smol'
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
            self::BIG_LONG  => 1,
            self::BIG_POTAT => 2,
            self::OLD       => 3,
            default         => 0
        };
    }

    /**
     * toSampleLength
     *
     * @return int
     */
    public function toSampleLength(): int
    {
        return match($this) {
            self::BIG_LONG  => 6,
            self::BIG_POTAT => 5,
            self::OLD       => 4,
            default         => 3
        };
    }
}