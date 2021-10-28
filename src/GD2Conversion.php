<?php

namespace Zoolt\Image;

use GdImage;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use Zoolt\Image\Exceptions\CouldNotConvert;
use Zoolt\Image\Exceptions\FileNotFound;
use Zoolt\Image\Exceptions\InvalidManipulation;

final class GD2Conversion
{
    /** @var ?string */
    private $inputImage;

    /** @var ?string */
    private $imageData;

    /** @var GdImage */
    private $conversionResult = null;

    private $quality = 90;

    private $shouldOptimize = false;
    private $optimizeOptions = [];

    private $manipulations = null;
    private $background = null;

    public static function create(string $inputImage): self
    {
        return new self($inputImage, null);
    }

    public static function createFromString(?string $imageData): self
    {
        return new self(null, $imageData);
    }

    public function __construct(?string $inputImage, ?string $imageData)
    {
        $this->inputImage = $inputImage;
        $this->imageData = $imageData;
    }

    private function imageCreateFromMime($filePath)
    {
        $mime = mime_content_type($filePath);
        switch ($mime) {
            case 'image/png':
                return 'imagecreatefrompng';
            case 'image/bmp':
                return 'imagecreatefrombmp';
            case 'image/vnd.wap.wbmp':
                return 'imagecreatefromwbmp';
            case 'image/gif':
                return 'imagecreatefromgif';
            case 'image/webp':
                return 'imagecreatefromwebp';
            case 'image/xbm':
                return 'imagecreatefromxbm';
            case 'image/xpm':
                return 'imagecreatefromxpm';
            case 'image/jpeg':
            case 'image/jpg':
                return 'imagecreatefromjpeg';
        }
        throw FileNotFound::invalidType($filePath);
    }

    private function imageCreateFunction($extension)
    {
        switch ($extension) {
            case 'png':
                return 'imagecreatefrompng';
            case 'bmp':
                return 'imagecreatefrombmp';
            case 'wbmp':
                return 'imagecreatefromwbmp';
            case 'gif':
                return 'imagecreatefromgif';
            case 'webp':
                return 'imagecreatefromwebp';
            case 'xbm':
                return 'imagecreatefromxbm';
            case 'xpm':
                return 'imagecreatefromxpm';
            case 'jpeg':
            case 'jpg':
                return 'imagecreatefromjpeg';
            default:
                break;
        }
        throw FileNotFound::invalidType($extension);
    }

    private function should($manipulationName)
    {
        foreach ($this->manipulations as $manipulationGroup) {
            foreach ($manipulationGroup as $name => $argument) {
                if ($name == $manipulationName) {
                    return true;
                }
            }
        }
        return false;
    }

    public function background(array $backgroundColor)
    {
        $this->background = $backgroundColor;
        return $this;
    }

    public function performManipulations($manipulations)
    {
        $this->manipulations = $manipulations;
        $this->shouldOptimize = false;

        if ($this->inputImage) {
            if (!file_exists($this->inputImage)) {
                throw FileNotFound::nonExisting($this->inputImage);
            }
            $extension = strtolower(pathinfo($this->inputImage, PATHINFO_EXTENSION));
            if (!empty($extension)) {
                $f = $this->imageCreateFunction($extension);
            } else {
                $f = $this->imageCreateFromMime($this->inputImage);
            }
            $img = \call_user_func($f, $this->inputImage);
        } else {
            $f = null;
            $img = imagecreatefromstring($this->imageData);
        }

        // If we need scaling, do this first to speed up orientation
        if ($this->should('scale')) {
            // also scaling should be the first manipulation, see Image.php
            $item = array_shift($this->manipulations);
            foreach ($item as $name => $argument) {
                $this->manipulate($img, $name, $argument);
            }
        }

        if (!$this->should('orientation') && $f == 'imagecreatefromjpeg') {
            $this->autoOrientation($img);
        }

        foreach ($this->manipulations as $manipulationGroup) {
            //$watermarkPath = $this->extractWatermarkPath($manipulationGroup);
            foreach ($manipulationGroup as $name => $argument) {
                if ($name !== 'optimize') {
                    $this->manipulate($img, $name, $argument);
                } else {
                    $this->shouldOptimize = true;
                    $this->optimizeOptions = $argument;
                }
            }
        }

        $this->conversionResult = $img;

        return $this;
    }

    private function autoOrientation(&$img)
    {
        if ($this->inputImage) {
            $exif = exif_read_data($this->inputImage);
        } else {
            // see https://stackoverflow.com/questions/5465665/extract-exif-data-from-an-image-blob-binary-string-in-php
            $exif = exif_read_data("data://image/jpeg;base64," . base64_encode($this->imageData));
        }
        $rot = $exif['Orientation'] ?? $exif['COMPUTED']['Orientation'] ?? null;
        switch ($rot) {
            case 3: // 180 degrees
                $this->manipulate($img, 'orientation', 180);
                break;
            case 6: // 270 degrees
                $this->manipulate($img, 'orientation', 270);
                break;
            case 8: // 90 degrees
                $this->manipulate($img, 'orientation', 90);
                break;
            case 1:
            case null:
                break;
            default:
                throw InvalidManipulation::invalidParameter('exif rotation', $rot, [1, 3, 6, 8]);
                break;
        }
    }

    private function manipulate(&$imageHandle, $manipulationName, $arguments)
    {
        if (!method_exists($this, $manipulationName)) {
            throw CouldNotConvert::unknownManipulation($manipulationName);
        }
        $this->$manipulationName($imageHandle, $arguments);
    }

    private function quality(&$imageHandle, $value)
    {
        $this->quality = $value;
    }

    private function blur(&$imageHandle, $value)
    {
        // See https://stackoverflow.com/questions/42759135/php-best-way-to-blur-images

        // Scale the image between 25% and 75%
        $origWidth = imagesx($imageHandle);
        $scale = ((75 - ($value / 2)) / 100);
        $this->scale($imageHandle, [Manipulations::FIT_CONTAIN, max(10, round($origWidth * $scale)), null]);

        // Perform 1 to 11 rounds of blurring
        for ($i = 0; $i < round($value / 10) + 1; $i++) {
            $this->imageFilter($imageHandle, ['method' => IMG_FILTER_GAUSSIAN_BLUR]);
        }

        // Perform smooth of 101 - 1
        $this->imageFilter($imageHandle, ['method' => IMG_FILTER_SMOOTH, 'arg1' => 101 - $value]);

        // Scale back
        $this->scale($imageHandle, [Manipulations::FIT_CONTAIN, $origWidth, null]);

        // Smooth twice
        $this->imageFilter($imageHandle, ['method' => IMG_FILTER_SMOOTH, 'arg1' => 1]);
        $this->imageFilter($imageHandle, ['method' => IMG_FILTER_SMOOTH, 'arg1' => 1]);
    }

    private function crop(&$imageHandle, $arguments)
    {
        $defaults = [
            'width' => null,
            'height' => null,
            'gravity' => 'center',
        ];
        extract(array_merge($defaults, $arguments));
        if ($gravity == 'center') {
            $imageWidth = imagesx($imageHandle);
            $imageHeight = imagesy($imageHandle);
            if ($height > $imageHeight) {
                $height = $imageHeight;
            }
            if ($width > $imageWidth) {
                $width = $imageWidth;
            }
            $x = round(($imageWidth - $width) / 2);
            $y = round(($imageHeight - $height) / 2);
        } else {
            throw new InvalidManipulation('invalid gravity');
        }
        $imageHandle = imagecrop($imageHandle, [
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height,
        ]);
    }

    private function imagefilter(&$imageHandle, $arguments)
    {
        $defaults = [
            'iter' => 1,
            'arg1' => null,
            'arg2' => null,
            'arg3' => null,
            'arg4' => null,
        ];
        extract(array_merge($defaults, $arguments));
        if (!isset($method)) {
            throw CouldNotConvert::unknownManipulation('imagefilter method missing');
        }
        for ($i = 0; $i < $iter; $i++) {
            if ($arg4 !== null) {
                $ret = imagefilter($imageHandle, $method, $arg1, $arg2, $arg3, $arg4);
            } elseif ($arg3 !== null) {
                $ret = imagefilter($imageHandle, $method, $arg1, $arg2, $arg3);
            } elseif ($arg2 !== null) {
                $ret = imagefilter($imageHandle, $method, $arg1, $arg2);
            } elseif ($arg1 !== null) {
                $ret = imagefilter($imageHandle, $method, $arg1);
            } else {
                $ret = imagefilter($imageHandle, $method);
            }
            if (!$ret) {
                throw CouldNotConvert::unknownManipulation('imagefilter failed');
            }
        }
    }

    private function gamma(&$imageHandle, $value)
    {
        imagegammacorrect($imageHandle, 1.0, $value);
    }

    private function orientation(&$imageHandle, $value)
    {
        $ret = imagerotate($imageHandle, $value, 0);
        if ($ret === false) {
            throw CouldNotConvert::unknownManipulation('imagerotate failed');
        }
        $imageHandle = $ret;
    }

    private function flip(&$imageHandle, $mode)
    {
        $ret = imageflip($imageHandle, $mode);
        if (!$ret) {
            throw CouldNotConvert::unknownManipulation('flip');
        }
    }

    private function scale(&$imageHandle, $attributes)
    {
        $method = $attributes[0];
        $targetWidth = (float) $attributes[1];
        $targetHeight = (float) $attributes[2];

        $width = imagesx($imageHandle);
        $height = imagesy($imageHandle);
        $ratio = $width / $height;

        if ($targetHeight === null || $targetHeight <= 0) {
            $targetHeight = round($targetWidth / $ratio);
            $targetRatio = $ratio;
        } elseif ($targetWidth === null || $targetWidth <= 0) {
            $targetWidth = round($targetHeight * $ratio);
            $targetRatio = $ratio;
        } else {
            $targetRatio = $targetWidth / $targetHeight;
        }

        $paddingTop = $paddingBottom = $paddingLeft = $paddingRight = 0;
        switch ($method) {
            case Manipulations::FIT_CONTAIN:
                if ($ratio < $targetRatio) {
                    $targetWidth = round($targetHeight * $ratio);
                } else {
                    $targetHeight = round($targetWidth / $ratio);
                }
                break;
            case Manipulations::FIT_MAX:
                // Only scale down, never scale up
                if ($targetWidth < $width || $targetHeight < $height) {
                    if ($ratio < $targetRatio) {
                        $targetWidth = round($targetHeight * $ratio);
                    } else {
                        $targetHeight = round($targetWidth / $ratio);
                    }
                } else {
                    $targetWidth = $width;
                    $targetHeight = $height;
                }
                break;
            case Manipulations::FIT_FILL:
                if ($ratio < $targetRatio) {
                    $newWidth = round($targetHeight * $ratio);
                    $paddingLeft = $paddingRight = ($targetWidth - $newWidth) / 2;
                    $targetWidth = $newWidth;
                } else {
                    $newHeight = round($targetWidth / $ratio);
                    $paddingTop = $paddingBottom = ($targetHeight - $newHeight) / 2;
                    $targetHeight = $newHeight;
                }
                break;
            case Manipulations::FIT_STRETCH:
                // Don't change target-size, we need to copy-resize the image into the new format
                break;
            case Manipulations::FIT_CROP:
                if ($ratio > $targetRatio) {
                    $newWidth = round($targetHeight * $ratio);
                    $paddingLeft = $paddingRight = ($targetWidth - $newWidth) / 2;
                    $targetWidth = $newWidth;
                } elseif ($ratio > $targetRatio) {
                    $newHeight = round($targetWidth / $ratio);
                    $paddingTop = $paddingBottom = ($targetHeight - $newHeight) / 2;
                    $targetHeight = $newHeight;
                }
                break;
        }
        $fullWidth = round($targetWidth + $paddingLeft + $paddingRight);
        $fullHeight = round($targetHeight + $paddingTop + $paddingBottom);
        $newImg = imagecreatetruecolor($fullWidth, $fullHeight);
        if ($paddingTop > 0 || $paddingBottom > 0 || $paddingLeft > 0 || $paddingRight > 0) {
            if ($this->background['a'] > 0) {
                imagealphablending($newImg, false);
                imagesavealpha($newImg, true);
                $bg = imagecolorallocatealpha(
                    $newImg,
                    $this->background['r'],
                    $this->background['g'],
                    $this->background['b'],
                    $this->background['a']
                );
            } else {
                $bg = imagecolorallocate(
                    $newImg,
                    $this->background['r'],
                    $this->background['g'],
                    $this->background['b']
                );
            }
            imagefilledrectangle($newImg, 0, 0, $fullWidth, $fullHeight, $bg);
        }

        imagecopyresampled(
            $newImg,
            $imageHandle,
            $paddingLeft,
            $paddingTop,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $width,
            $height
        );
        imagedestroy($imageHandle);
        $imageHandle = $newImg;
    }

    /**
     * Removes the watermark path from the manipulationGroup and returns it. This way it can be injected into the Glide
     * server as the `watermarks` path.
     *
     * @param $manipulationGroup
     *
     * @return null|string
     */
    private function extractWatermarkPath(&$manipulationGroup)
    {
        if (array_key_exists('watermark', $manipulationGroup)) {
            $watermarkPath = dirname($manipulationGroup['watermark']);

            $manipulationGroup['watermark'] = basename($manipulationGroup['watermark']);

            return $watermarkPath;
        }
    }

    public function save(string $outputFile, $format = null)
    {
        if ($format === null) {
            $format = strtolower(pathinfo($outputFile, PATHINFO_EXTENSION));
        }

        if ($format === 'jpg' || $format === 'jpeg') {
            $process = imagejpeg($this->conversionResult, $outputFile, $this->quality);
        } elseif ($format === 'png') {
            $process = imagepng($this->conversionResult, $outputFile, 9, PNG_ALL_FILTERS);
        } elseif ($format === 'gif') {
            $process = imagegif($this->conversionResult, $outputFile);
        } elseif ($format === 'bmp') {
            $process = imagebmp($this->conversionResult, $outputFile);
        } else {
            throw FileNotFound::nonExisting('outputrenderer for ' . $format);
        }
        imagedestroy($this->conversionResult);

        if ($this->shouldOptimize) {
            $this->performOptimization($outputFile, $this->optimizeOptions);
        }
    }

    private function imageWriteFunction($extension)
    {
        switch ($extension) {
            case 'png':
                return 'imagecreatefrompng';
            case 'bmp':
                return 'imagecreatefrombmp';
            case 'wbmp':
                return 'imagecreatefromwbmp';
            case 'gif':
                return 'imagecreatefromgif';
            case 'webp':
                return 'imagecreatefromwebp';
            case 'xbm':
                return 'imagecreatefromxbm';
            case 'xpm':
                return 'imagecreatefromxpm';
            case 'jpeg':
            case 'jpg':
            default:
                break;
        }
        return 'imagecreatefromjpeg';
    }

    protected function performOptimization($path, array $optimizerChainConfiguration)
    {
        $optimizerChain = OptimizerChainFactory::create();
        if (count($optimizerChainConfiguration)) {
            $optimizers = array_map(function (array $optimizerOptions, string $optimizerClassName) {
                return (new $optimizerClassName)->setOptions($optimizerOptions);
            }, $optimizerChainConfiguration, array_keys($optimizerChainConfiguration));
            $optimizerChain->setOptimizers($optimizers);
        }
        $optimizerChain->optimize($path);
    }
}
