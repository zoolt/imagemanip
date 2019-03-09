<?php

namespace Zoolt\Image\Test\Manipulations;

use Zoolt\Image\Image;
use Zoolt\Image\Test\TestCase;

class GreyscaleTest extends TestCase
{
    /** @test */
    public function it_can_make_an_image_greyscale()
    {
        $targetFile = $this->tempDir->path('conversion.jpg');

        Image::load($this->getTestJpg())->greyscale()->save($targetFile);

        $this->assertFileExists($targetFile);
    }
}
