<?php
namespace Zoolt\Image;

class Manipulations
{
    const CROP_TOP_LEFT = 'crop-top-left';
    const CROP_TOP = 'crop-top';
    const CROP_TOP_RIGHT = 'crop-top-right';
    const CROP_LEFT = 'crop-left';
    const CROP_CENTER = 'crop-center';
    const CROP_RIGHT = 'crop-right';
    const CROP_BOTTOM_LEFT = 'crop-bottom-left';
    const CROP_BOTTOM = 'crop-bottom';
    const CROP_BOTTOM_RIGHT = 'crop-bottom-right';

    const ORIENTATION_AUTO = 'auto';
    const ORIENTATION_90 = 90;
    const ORIENTATION_180 = 180;
    const ORIENTATION_270 = 270;

    const FLIP_HORIZONTALLY = IMG_FLIP_HORIZONTAL;
    const FLIP_VERTICALLY = IMG_FLIP_VERTICAL;
    const FLIP_BOTH = IMG_FLIP_BOTH;

    const FIT_CONTAIN = 'contain';
    const FIT_MAX = 'max';
    const FIT_FILL = 'fill';
    const FIT_STRETCH = 'stretch';
    const FIT_CROP = 'crop';

    const BORDER_OVERLAY = 'overlay';
    const BORDER_SHRINK = 'shrink';
    const BORDER_EXPAND = 'expand';

    const FORMAT_JPG = 'jpg';
    const FORMAT_PJPG = 'pjpg';
    const FORMAT_PNG = 'png';
    const FORMAT_GIF = 'gif';
    const FORMAT_WEBP = 'webp';

    const FILTER_GREYSCALE = 'greyscale';
    const FILTER_SEPIA = 'sepia';

    const UNIT_PIXELS = 'px';
    const UNIT_PERCENT = '%';

    const POSITION_TOP_LEFT = 'top-left';
    const POSITION_TOP = 'top';
    const POSITION_TOP_RIGHT = 'top-right';
    const POSITION_LEFT = 'left';
    const POSITION_CENTER = 'center';
    const POSITION_RIGHT = 'right';
    const POSITION_BOTTOM_LEFT = 'bottom-left';
    const POSITION_BOTTOM = 'bottom';
    const POSITION_BOTTOM_RIGHT = 'bottom-right';
}
