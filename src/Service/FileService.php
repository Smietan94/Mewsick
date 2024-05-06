<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\CatType;
use InvalidArgumentException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\Request;

class FileService
{
    private FilesystemOperator $storage;

    public function __construct(
        FilesystemOperator $defaultStorage,
    ) {
        $this->storage = $defaultStorage;
    }

    public function combineLayers(Request $request)
    {
        $session = $request->getSession();

        $catType = $session->get('CAT_TYPE');
        $catType = CatType::tryFrom($catType)->toString();
        $catType = str_replace(' ', '_', $catType);

        $baseImgPath = sprintf('images/cat/%s.png', $catType);

        $base = @imagecreatefrompng($baseImgPath);


    }

}