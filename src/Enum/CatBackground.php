<?php

declare(strict_types=1);

namespace App\Enum;

enum CatBackground: int
{
    case SPRING = 0;
    case SUMMER = 1;
    case AUTUMN = 2;
    case WINTER = 3;
    case SKULLS = 4;
    case HEARTS = 5;

    /**
     * toString
     *
     * @return string
     */
    public function toString(): string
    {
        return match($this) {
            self::SUMMER => 'summer',
            self::AUTUMN => 'autumn',
            self::WINTER => 'winter',
            self::SKULLS => 'skulls',
            self::HEARTS => 'hearts',
            default      => 'spring'
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
            self::SUMMER => 1,
            self::AUTUMN => 2,
            self::WINTER => 3,
            self::SKULLS => 4,
            self::HEARTS => 5,
            default      => 0
        };
    }
}