<?php

namespace Zoolt\Image\Test\Manipulations;

use Zoolt\Image\Image;
use Zoolt\Image\Manipulations;
use Zoolt\Image\Test\TestCase;
use Zoolt\Image\Exceptions\InvalidManipulation;

class OrientationTest extends TestCase
{
    /** @test */
    public function it_can_set_the_orientation()
    {
        $targetFile = $this->tempDir->path('conversion.jpg');

        Image::load($this->getTestJpg())->orientation(Manipulations::ORIENTATION_90)->save($targetFile);

        $this->assertFileExists($targetFile);
    }

    /** @test */
    public function it_will_throw_an_exception_when_passing_an_invalid_orientation()
    {
        $this->expectException(InvalidManipulation::class);

        Image::load($this->getTestJpg())->orientation('blabla');
    }
}
