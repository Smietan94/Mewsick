<?php

namespace App\Twig\Runtime;

use App\Entity\Const\RouteName;
use Twig\Extension\RuntimeExtensionInterface;

class BasicStuffExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct()
    {
        // Inject dependencies if needed
    }

    /**
     * get route name from route name constants
     *
     * @param  string $routeName
     * @return string
     */
    public function getRouteName(string $routeName): string
    {
        $reflection = new \ReflectionClass(RouteName::class);

        return $reflection->getConstant(strtoupper($routeName));
    }
}
