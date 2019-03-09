<?php

namespace Zoolt\Image\Test\Manipulations;

use Zoolt\Image\Test\ImageBG;
use Zoolt\Image\Image;
use Zoolt\Image\Manipulations;
use Zoolt\Image\Test\TestCase;
use Zoolt\Image\Exceptions\InvalidManipulation;

class BackgroundTest extends TestCase
{
    /** @test */
    public function background_valid_hex3()
    {
        $targetFile = $this->tempDir->path('conversion.jpg');

        $bg = ImageBG::load($this->getTestJpg())
            ->background('0fa')
            ->getBackground();
        $this->assertEquals(['r' => 0, 'g' => 255, 'b' => 170, 'a' => 0], $bg);
    }

    /** @test */
    public function background_valid_hex6()
    {
        $targetFile = $this->tempDir->path('conversion.jpg');

        $bg = ImageBG::load($this->getTestJpg())
            ->background('0033AE')
            ->getBackground();
        $this->assertEquals(['r' => 0, 'g' => 51, 'b' => 174, 'a' => 0], $bg);
    }

    /** @test */
    public function background_valid_hex8()
    {
        $targetFile = $this->tempDir->path('conversion.jpg');

        $bg = ImageBG::load($this->getTestJpg())
            ->background('EE0033AE')
            ->getBackground();
        $this->assertEquals(['r' => 238, 'g' => 0, 'b' => 51, 'a' => 174], $bg);
    }

    /** @test */
    public function background_valid_css_name()
    {
        $targetFile = $this->tempDir->path('conversion.jpg');

        $bg = ImageBG::load($this->getTestJpg())
            ->background('lightslategray')
            ->getBackground();
        $this->assertEquals(['r' => 119, 'g' => 136, 'b' => 153, 'a' => 0], $bg);
    }

    /** @test */
    public function it_will_throw_an_exception_when_passing_an_invalid_fit()
    {
        $this->expectException(InvalidManipulation::class);

        Image::load($this->getTestJpg())->background('invalidcolor');
    }
}