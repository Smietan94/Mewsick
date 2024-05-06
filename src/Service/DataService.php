<?php

declare(strict_types=1);

namespace App\Service;

use UnitEnum;

class DataService
{
    /**
     * checks if provided data contains enum type tha returns its value
     *
     * @param  UnitEnum[] $data
     * @return int
     */
    public function getCatElementValue(array $data): int
    {
        $catElement = $data['elements'];

        if ($catElement instanceof UnitEnum) {
            return (int) $catElement->value;
        }

        return 0;
    }
}