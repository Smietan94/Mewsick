<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\CatColor;
use App\Enum\CatHat;
use App\Enum\CatType;
use App\Twig\Runtime\BasicStuffExtensionRuntime;
use ErrorException;
use GdImage;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class FileService
{
    private FilesystemOperator $storage;

    public function __construct(
        FilesystemOperator $defaultStorage,
        private BasicStuffExtensionRuntime $basicStuffProcessor
    ) {
        $this->storage = $defaultStorage;
    }

    public function combineLayers(Request $request)
    {
        $x = $y = 2000;

        $session = $request->getSession();

        $catName   = $session->get('CAT_NAME'); // potem usunąć
        $imageName = $this->generateCatImageName($catName);

        $catType       = $this->getElementNameFromEnum($session->get('CAT_TYPE'), 'type');
        $catColor      = $this->getElementNameFromEnum($session->get('CAT_COLOR'), 'color');
        $catHat        = $this->getElementNameFromEnum($session->get('CAT_HAT'), 'hat');
        $catBackground = $this->getElementNameFromEnum($session->get('CAT_BACKGROUND'), 'background');

        $basePath = sprintf('images/cat/%s', $catType);

        // this block of code will be replaced with background image
        // $output     = imagecreatetruecolor($x/4, $y/4);
        // $background = imagecolorallocate($output, 215, 192, 208);
        // imagefilledrectangle($output, 0, 0, $x-1, $y-1, $background);
        $output = $this->getImage('images/cat', 'background', $catBackground);
        imagealphablending($output, true);
        imagesavealpha($output, true);

        $color  = $this->getImage($basePath, 'color', $catColor);
        $hat    = $this->getImage($basePath, 'hat/img_to_process', $catHat);

        $this->mergeImages($output, $color);
        $this->mergeImages($output, $hat);

        imagejpeg($output, sprintf('images/cats/playlist_cover/%s.jpg', $imageName), 15);
        imagejpeg($output, sprintf('images/cats/playlist_cover_download/%s.jpg', $imageName), 100);

        $session->set('CAT_PHOTO_NAME', $imageName);

        // dd($hat);
    }

    /**
     * generates file name to avoid repetitions it using date format with milicesonds
     *
     * @param  string $catName
     * @return string
     */
    public function generateCatImageName(string $catName): string
    {
        $catName         = str_replace(' ', '_', $catName);
        $date            = (new \DateTime())->format('dmYHisu');
        $randomNumber    = mt_rand(0, 99999);
        $formattedNumber = sprintf('%05d', $randomNumber);

        return sprintf('%s_%s%s', $catName, $date, $formattedNumber);
    }

    /**
     * creates png img path then returns gdImage
     *
     * @param  string $basePath
     * @param  string $subjectPath subject path eg. 'color' or 'hat/img_to_process'
     * @param  string $fileName simply enum string value matching filename
     * @return GdImage
     */
    public function getImage(string $basePath, string $subjectPath, string $fileName): GdImage
    {
        $imgPath = sprintf('%s/%s/%s.png', $basePath, $subjectPath, $fileName);

        return @imagecreatefrompng($imgPath); // suppressed errors while reading png due to iCCP warning
    }

    /**
     * merges two pngs into one
     *
     * @param  GdImage $base
     * @param  GdImage $topLayer
     * @return void
     */
    public function mergeImages(GdImage $base, GdImage $topLayer): void
    {
        $x = $y = 2000;

        imagecopy($base, $topLayer, 0, 0, 0, 0, $x, $y);
    }

    /**
     * using twig runtime to get element name from enum
     * get cat element from reflection enum, subject must match enum name e.g. CatHat -> subject = hat else use fully cualified name
     *
     * @param  int    $elementType
     * @param  string $subject
     * @return string
     */
    public function getElementNameFromEnum(int $elementType, string $subject): string
    {
        $elementName = $this->basicStuffProcessor->getCatElementName($elementType, $subject);

        return str_replace(' ', '_', $elementName);
    }

    /**
     * removes cover photo from storage after updating spotify playlist cover
     *
     * @param  string $fileName
     * @return void
     */
    public function removeCoverPhoto(string $fileName)
    {
        $filePath = sprintf('/cats/playlist_cover/%s.jpg', $fileName);

        $this->storage->delete($filePath);
    }

}