<?php

namespace Zoolt\Image\Test\Manipulations;

use Zoolt\Image\Image;
use Zoolt\Image\Test\TestCase;
use Spatie\ImageOptimizer\Optimizers\Jpegoptim;

class OptimizeTest extends TestCase
{
    /** @test */
    public function it_can_optimize_an_image()
    {
        $targetFile = $this->tempDir->path('optimized.jpg');

        Image::load($this->getTestFile('test.jpg'))
            ->optimize()
            ->save($targetFile);

        $this->assertFileExists($targetFile);
    }

    /** @test */
    public function it_can_optimize_an_image_with_the_given_optimization_options()
    {
        $targetFile = $this->tempDir->path('optimized.jpg');

        Image::load($this->getTestFile('test.jpg'))
            ->optimize([Jpegoptim::class => [
                '--all-progressive',
            ]])
            ->save($targetFile);

        $this->assertFileExists($targetFile);
    }
}
