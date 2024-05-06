<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\BasicStuffExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class BasicStuffExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            // If your filter generates SAFE HTML, you should add a third
            // parameter: ['is_safe' => ['html']]
            // Reference: https://twig.symfony.com/doc/3.x/advanced.html#automatic-escaping
            new TwigFilter('filter_name', [BasicStuffExtensionRuntime::class, 'doSomething']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_route_name', [BasicStuffExtensionRuntime::class, 'getRouteName']),
            new TwigFunction('get_cat_type', [BasicStuffExtensionRuntime::class, 'getCatType']),
            new TwigFunction('get_cat_type_dir_prefix', [BasicStuffExtensionRuntime::class, 'getCatTypeDirPrefix']),
            new TwigFunction('get_cat_element_name', [BasicStuffExtensionRuntime::class, 'getCatElementName']),
            new TwigFunction('get_image_dir_path', [BasicStuffExtensionRuntime::class, 'getImageDirPath'])
        ];
    }
}
