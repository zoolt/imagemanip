<?php

namespace Zoolt\Image\Test\Manipulations;

use Zoolt\Image\Image;
use Zoolt\Image\Manipulations;
use Zoolt\Image\Test\TestCase;
use Zoolt\Image\Exceptions\InvalidManipulation;

class CropTest extends TestCase
{
    /** @test */
    public function it_can_crop()
    {
        $targetFile = $this->tempDir->path('conversion.jpg');

        Image::load($this->getTestJpg())->crop(100, 500)->save($targetFile);

        $this->assertFileExists($targetFile);
    }

    /** @test */
    public function it_will_throw_an_exception_when_passing_a_negative_width()
    {
        $this->expectException(InvalidManipulation::class);

        Image::load($this->getTestJpg())->crop(-10, 10);
    }

    /** @test */
    public function it_will_throw_an_exception_when_passing_a_negative_height()
    {
        $this->expectException(InvalidManipulation::class);

        Image::load($this->getTestJpg())->crop(10, -10);
    }
}
