<?php

declare(strict_types=1);

namespace App\Entity\Const;

class RoutePath
{
    public const BASE_SPOTIFY_API_CONTROLLER = '/';

    // spotify api controller paths
    public final const START                   = '/';
    public final const CREATE_SPOTIFY_PLAYLIST = '/catName';
    public final const CHOOSE_YOUR_FIGHTER     = '/chooseYourFighter';
    public final const CHOOSE_CAT_COLOR        = '/catColor';
    public final const CHOOSE_CAT_HAT          = '/catHat';
    public final const CHOOSE_CAT_BAG          = '/catBag';
    public final const CHOOSE_CAT_HOBBY        = '/catHobby';
    public final const CHOOSE_CAT_BACKGROUND   = '/catBackground';
    public final const CAT_REVEAL              = '/catReveal';
    public final const CATS_GALLERY            = '/catsGallery';
}