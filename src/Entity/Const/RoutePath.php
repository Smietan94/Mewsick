<?php

declare(strict_types=1);

namespace App\Entity\Const;

class RoutePath
{
    public const BASE_SPOTIFY_API_CONTROLLER = '/';

    // spotify api controller paths
    public const START                   = '/';
    public const CREATE_SPOTIFY_PLAYLIST = '/catName';
    public const CHOOSE_YOUR_FIGHTER     = '/chooseYourFighter';
    public const CHOOSE_CAT_COLOR        = '/catColor';
    public const CHOOSE_CAT_HAT          = '/catHat';
    public const CHOOSE_CAT_BAG          = '/catBag';
    public const CHOOSE_CAT_HOBBY        = '/catHobby';
    public const CHOOSE_CAT_BACKGROUND   = '/catBackground';
    public const CAT_REVEAL              = '/catReveal';
    public const CATS_GALLERY            = '/catsGallery';
}