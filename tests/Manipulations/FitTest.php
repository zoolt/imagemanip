<?php

namespace Zoolt\Image\Test\Manipulations;

use Zoolt\Image\Image;
use Zoolt\Image\Manipulations;
use Zoolt\Image\Test\TestCase;
use Zoolt\Image\Exceptions\InvalidManipulation;

class FitTest extends TestCase
{
    /** @test */
    public function it_can_fit_contain_shrink_an_image()
    {
        $targetFile = $this->tempDir->path('conversion.jpg');

        Image::load($this->getTestJpg())->fit(Manipulations::FIT_CONTAIN, 330, 280)->save($targetFile);

        $this->assertFileExists($targetFile);

        $i = Image::load($targetFile);
        $this->assertEquals([330, 272], [$i->getWidth(), $i->getHeight()]);
    }

    /** @test */
    public function it_can_fit_contain_shrink2_an_image()
    {
        $targetFile = $this->tempDir->path('conversion.jpg');

        Image::load($this->getTestJpg())->fit(Manipulations::FIT_CONTAIN, 330, 270)->save($targetFile);

        $this->assertFileExists($targetFile);

        $i = Image::load($targetFile);
        $this->assertEquals([328, 270], [$i->getWidth(), $i->getHeight()]);
    }

    /** @test */
    public function it_can_fit_contain_enlarge_an_image()
    {
        $targetFile = $this->tempDir->path('conversion.jpg');

        Image::load($this->getTestJpg())->fit(Manipulations::FIT_CONTAIN, 1000, 1000)->save($targetFile);

        $this->assertFileExists($targetFile);

        $i = Image::load($targetFile);
        $this->assertEquals([1000, 824], [$i->getWidth(), $i->getHeight()]);
    }

    /** @test */
    public function it_can_fit_max_shrink_an_image()
    {
        $targetFile = $this->tempDir->path('conversion.jpg');

        Image::load($this->getTestJpg())->fit(Manipulations::FIT_MAX, 330, 270)->save($targetFile);

        $this->assertFileExists($targetFile);

        $i = Image::load($targetFile);
        $this->assertEquals([328, 270], [$i->getWidth(), $i->getHeight()]);
    }

    /** @test */
    public function it_can_fit_max_no_enlarge_an_image()
    {
        $targetFile = $this->tempDir->path('conversion.jpg');

        Image::load($this->getTestJpg())->fit(Manipulations::FIT_MAX, 500, 300)->save($targetFile);

        $this->assertFileExists($targetFile);

        $i = Image::load($targetFile);
        $this->assertEquals([340, 280], [$i->getWidth(), $i->getHeight()]);
    }

    /** @test */
    public function it_can_fit_fill_shrink_an_image()
    {
        $targetFile = $this->tempDir->path('conversion.jpg');

        Image::load($this->getTestJpg())->fit(Manipulations::FIT_FILL, 200, 200)->save($targetFile);

        $this->assertFileExists($targetFile);

        $i = Image::load($targetFile);
        $this->assertEquals([200, 200], [$i->getWidth(), $i->getHeight()]);
    }

    /** @test */
    public function it_can_fit_fill_enlarge_an_image()
    {
        $targetFile = $this->tempDir->path('conversion.jpg');

        Image::load($this->getTestJpg())->fit(Manipulations::FIT_FILL, 600, 600)->save($targetFile);

        $this->assertFileExists($targetFile);

        $i = Image::load($targetFile);
        $this->assertEquals([600, 600], [$i->getWidth(), $i->getHeight()]);
    }

    /** @test */
    public function it_can_fit_fill_background_an_image()
    {
        $targetFile = $this->tempDir->path('conversion.jpg');

        Image::load($this->getTestJpg())
            ->fit(Manipulations::FIT_FILL, 400, 400)
            ->background('ff0000')
            ->save($targetFile);

        $this->assertFileExists($targetFile);

        $i = Image::load($targetFile);
        $this->assertEquals([400, 400], [$i->getWidth(), $i->getHeight()]);
    }

    /** @test */
    public function it_can_fit_stretch_fill_an_image()
    {
        $targetFile = $this->tempDir->path('conversion.jpg');

        Image::load($this->getTestJpg())->fit(Manipulations::FIT_STRETCH, 700, 600)->save($targetFile);

        $this->assertFileExists($targetFile);

        $i = Image::load($targetFile);
        $this->assertEquals([700, 600], [$i->getWidth(), $i->getHeight()]);
    }

    /** @test */
    public function it_can_fit_stretch_shrink_an_image()
    {
        $targetFile = $this->tempDir->path('conversion.jpg');

        Image::load($this->getTestJpg())->fit(Manipulations::FIT_STRETCH, 50, 50)->save($targetFile);

        $this->assertFileExists($targetFile);

        $i = Image::load($targetFile);
        $this->assertEquals([50, 50], [$i->getWidth(), $i->getHeight()]);
    }

    /** @test */
    public function it_can_fit_crop_an_image()
    {
        $targetFile = $this->tempDir->path('conversion.jpg');

        Image::load($this->getTestJpg())->fit(Manipulations::FIT_CROP, 400, 300)->save($targetFile);

        $this->assertFileExists($targetFile);

        $i = Image::load($targetFile);
        $this->assertEquals([400, 300], [$i->getWidth(), $i->getHeight()]);
    }

    /** @test */
    public function it_will_throw_an_exception_when_passing_an_invalid_fit()
    {
        $this->expectException(InvalidManipulation::class);

        Image::load($this->getTestJpg())->fit('blabla', 500, 300);
    }
}
