<?php

namespace Zoolt\Image\Test\Manipulations;

use Zoolt\Image\Image;
use Zoolt\Image\Test\TestCase;
use Zoolt\Image\Exceptions\InvalidManipulation;

class BlurTest extends TestCase
{
    /** @test */
    public function it_can_blur()
    {
        $targetFile = $this->tempDir->path('conversion.jpg');

        Image::load($this->getTestJpg())->blur(5)->save($targetFile);

        $this->assertFileExists($targetFile);
    }

    /** @test */
    public function it_will_throw_an_exception_when_passing_an_invalid_blur_value()
    {
        $this->expectException(InvalidManipulation::class);

        Image::load($this->getTestJpg())->blur(1000);
    }
}
