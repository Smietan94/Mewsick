<?php

namespace App\Twig\Runtime;

use App\Entity\Const\RouteName;
use App\Enum\CatColor;
use App\Enum\CatType;
use phpDocumentor\Reflection\PseudoTypes\LowercaseString;
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

    /**
     * gets cat type string value
     *
     * @param  int    $catType
     * @return string
     */
    public function getCatType(int $catType): string
    {
        return CatType::tryFrom($catType)->toString();
    }

    /**
     * gets cat type name and replaces to space with underscore to fit image dir path
     *
     * @param  int    $catType
     * @return string
     */
    public function getCatTypeDirPrefix(int $catType): string
    {
        $catType = $this->getCatType($catType);

        return str_replace(' ', '_', $catType);
    }

    /**
     * get cat element from reflection enum, subject must match enum name e.g. CatHat -> subject = hat
     *
     * @param  int    $value
     * @param  string $subject
     * @return string
     */
    public function getCatElementName(int $value, string $subject): string
    {
        $reflection = new \ReflectionEnum(sprintf('App\Enum\Cat%s', ucwords($subject)));

        return strtolower($reflection->getCases()[$value]->getName());
    }

    /**
     * retruns path to image dir
     *
     * @param  string $catType
     * @param  string $subject
     * @return string
     */
    public function getImageDirPath(string $catType, string $subject): string
    {
        return sprintf('images/cat/%s/%s/', $catType, $subject);
    }
}
