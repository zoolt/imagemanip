<?php
require dirname(__DIR__) . '/vendor/autoload.php';

\Zoolt\Image\Image::load('DSC_0003.JPG')
    ->fit(\Zoolt\Image\Manipulations::FIT_CROP, 500, 500)
    ->optimize()
    ->save('img03_fitcrop.jpg');

\Zoolt\Image\Image::load('DSC_0003.JPG')
    ->width(500)
    ->sepia()
    ->optimize()
    ->save('img03_sepia.jpg');

for ($i = 0; $i < 11; $i++) {
    echo 'Blur ' . ($i * 10) . PHP_EOL;
    \Zoolt\Image\Image::load('DSC_0003.JPG')
        ->blur($i * 10)
        ->background('003361')
        ->fit(\Zoolt\Image\Manipulations::FIT_FILL, 500, 500)
        ->optimize()
        ->save('img03_blur_'.round($i*10).'.jpg');
}

\Zoolt\Image\Image::load('DSC_0036.JPG')
    ->blur(100)
    ->width(1000)
    ->optimize()
    ->save('img36.jpg');

\Zoolt\Image\Image::load('DSC_0063.JPG')
    ->flip(\Zoolt\Image\Manipulations::FLIP_HORIZONTALLY)
    ->width(1000)
    ->optimize()
    ->save('img63.jpg');
