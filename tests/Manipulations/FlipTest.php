<?php

namespace Zoolt\Image\Test\Manipulations;

use Zoolt\Image\Image;
use Zoolt\Image\Manipulations;
use Zoolt\Image\Test\TestCase;

class FlipTest extends TestCase
{
    /** @test */
    public function it_can_flip_an_image()
    {
        $targetFile = $this->tempDir->path('conversion.jpg');

        Image::load($this->getTestJpg())->flip(Manipulations::FLIP_HORIZONTALLY)->save($targetFile);

        $this->assertFileExists($targetFile);
    }
}
