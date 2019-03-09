<?php
namespace Zoolt\Image;

use Spatie\ImageOptimizer\OptimizerChainFactory;

/**
 * Resize image
 */
class Image
{
    const RES_BOUNDARY = 0;
    const RES_BOUNDARY_ADAPTIVE_BG = 4;
    const RES_CROP = 1;
    const RES_SCALE_HEIGHT = 2;
    const RES_SCALE_WIDTH = 3;

    const JPEG_HIGH_QUALITY = 78;
    const JPEG_DEFAULT_QUALITY = 72;
    const JPEG_LOW_QUALITY = 20;

    public $inputFile;
    public $new_width = 0;
    public $new_height = 0;
    public $ratio = true;
    public $new_image_name;
    public $save_folder;
    public $method = 5;
    public $cache = 1;
    public $bgColor = array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0);
    private $imageSaveFunc;
    private $imageCreateFunc;

    private $manipulationChain = [];
    private $jpgQuality = 90;
    private $targetWidth = null;
    private $targetHeight = null;

    public function __construct($filename, $outputDir = null)
    {
        $this->inputFile = $filename;
        $this->save_folder = rtrim($outputDir, '/') . '/';
    }

    /**
     * Load image and return new instance
     * 
     * @param string $filename Full or relative path to file
     * @return Image instance
     */
    public static function load($filename)
    {
        return new self($filename);
    }

    /**
     * Optimize the image
     *
     * @return $this
     */
    public function optimize($options = null)
    {
        $this->manipulationChain[] = [
            'optimize' => $options,
        ];
        return $this;
    }

    /**
     * Set JPEG quality
     * 
     * @param int $quality JPEG quality between 0 and 100
     * @return $this
     */
    public function quality(int $quality)
    {
        $this->jpgQuality = max(0, min(100, $quality));
        return $this;
    }

    /**
     * Set the containing width (image is contained within this width) keeping aspect ratio
     * 
     * @param int $width Max width of the image
     * @return $this
     */
    public function width(int $width)
    {
        $this->width = $width;
        return $this;
    }

    /**
     * Save the file in the destination provided (without destination it overides the input-file).
     * This also fires the complete chain
     *
     * @param string $destination Destination save-path
     * @return void
     */
    public function save($destination = '')
    {
        if (empty($destination)) {
            $destination = $this->inputFile;
        }
        // TODO fire chain
    }

    private function getFunctions($extension)
    {
        switch ($extension) {
            case 'png':
                $this->imageCreateFunc = 'ImageCreateFromPNG';
                break;
            case 'bmp':
                $this->imageCreateFunc = 'ImageCreateFromBMP';
                break;
            case 'gif':
                $this->imageCreateFunc = 'ImageCreateFromGIF';
                break;
            case 'jpeg':
            case 'jpg':
            default:
                $this->imageCreateFunc = 'ImageCreateFromJPEG';
        }
        $this->extension = 'jpg';
        $this->imageSaveFunc = 'ImageJPEG';
        $this->mime = 'image/jpeg';
    }

    /**
     * Resize the image
     *
     */
    public function resize($fileName)
    {
        $marginLeft = 0;
        $marginTop = 0;

        $paddingLeft = 0;
        $paddingRight = 0;
        $paddingTop = 0;
        $paddingBottom = 0;

        if (!is_dir($this->save_folder)) {
            @mkdir($this->save_folder, 0755, true);
        }

        $path = pathinfo($this->inputFile);
        $this->getFunctions(strtolower($path['extension']));

        $new_name = $fileName . '.' . $this->extension;

        $save_path = $this->save_folder . $new_name;

        // Check if the file exists or is an URL
        if (strpos($this->inputFile, '://') === false && !file_exists($this->inputFile)) {
            if (strpos($_SERVER['SCRIPT_FILENAME'], '/media') !== false) {
                $this->inputFile = dirname(__FILE__) . "/../skin/frontend/" . SKIN . "/images/placeholder.png";
            } else {
                $this->inputFile = dirname(__FILE__) . "/templates/front/default/images/placeholder.png";
            }
            if (!file_exists($this->inputFile)) {
                exit("File " . $this->inputFile . " does not exist.");
            }
        }

        try {
            $info = getimagesize($this->inputFile);
        } catch (\Exception $exception) {
            header('HTTP/1.0 404 Not Found');
            die('File not found');
        }

        if (empty($info)) {
            header('HTTP/1.0 404 Not Found');
            die("The file " . $this->inputFile . " is invalid.");
        }

        $width = $info[0];
        $height = $info[1];

        if ($this->new_width == 0) {
            $this->new_width = $width;
        }

        if ($this->new_height == 0) {
            $this->new_height = $height;
        }

        $x = 0;
        $y = 0;

        $alignX = 'center';
        $alignY = 'center';

        switch (intval($this->method)) {
            case 0:
            case 4:
                $factorOld = (float) $width / (float) $height;
                $factor = (float) $this->new_width / (float) $this->new_height;
                if ($factorOld < $factor) {
                    //the original image is wider then the
                    //rescaled image so scale by height
                    $heightNew = $this->new_height;
                    $widthNew = $this->new_height * $factorOld;
                } else {
                    //the original image is taller then the
                    //rescaled image so scale by width to fit in
                    $widthNew = $this->new_width;
                    $heightNew = $this->new_width / $factorOld;
                }
                //alignment
                switch ($alignX) {
                    case 'center':
                        $x = ($this->new_width - $widthNew) / 2;
                        break;
                    case 'left':
                        $x = 0;
                        break;
                    case 'right':
                        $x = ($this->new_width - $widthNew);
                        break;
                }
                switch ($alignY) {
                    case 'center':
                        $y = ($this->new_height - $heightNew) / 2;
                        break;
                    case 'top':
                        $y = 0;
                        break;
                    case 'bottom':
                        $y = ($this->new_height - $heightNew);
                        break;
                }
                //$this->new_height = $height;
                //$this->new_width = $width;
                break;
            case 1:
                // Fill out
                $factorOld = (float) $width / (float) $height;
                $factor = (float) $this->new_width / (float) $this->new_height;
                //check if the image is a portrait depending on the thumb dimensions or a landscape
                if ($factor >= $factorOld) {
                    //portrait style image
                    $widthNew = (float) $this->new_width;
                    $heightNew = $widthNew / $factorOld;
                    $x = 0;
                    $y = -($heightNew - $this->new_height) / 2;
                }
                if ($factor < $factorOld) {
                    //landscape style image
                    $heightNew = (float) $this->new_height;
                    $widthNew = $heightNew * $factorOld;
                    $x = -($widthNew - $this->new_width) / 2;
                    $y = 0;
                }
                break;
            case 2:
                // Keep width, adjust height
                $factor = (float) $this->new_width / (float) $width;

                //check if the image is a portrait depending on the thumb dimensions or a landscape

                $widthNew = (float) $this->new_width;
                $this->new_height = $heightNew = $height * $factor;
                $x = 0;
                $y = 0;
                break;
            case 3:
                // Keep width, adjust height
                $factor = (float) $this->new_height / (float) $height;

                //check if the image is a portrait depending on the thumb dimensions or a landscape

                $heightNew = (float) $this->new_height;
                $this->new_width = $widthNew = $width * $factor;
                $x = 0;
                $y = 0;
                break;
            case 5:
                // Detect edges
                $findEdges = true;
                break;
            default:
                $widthNew = $this->new_width;
                $heightNew = $this->new_height;
                if ($this->ratio) {
                    // if preserving the ratio, only new width or new height
                    // is used in the computation. if both
                    // are set, use width
                    if (isset($this->new_width)) {
                        $factor = (float) $this->new_width / (float) $width;
                        $heightNew = $factor * $height;
                    } else if (isset($this->new_height)) {
                        $factor = (float) $this->new_height / (float) $height;
                        $widthNew = $factor * $width;
                    } else {
                        exit;
                    }
                }
                break;
        }

        // New Image
        $image_c = ImageCreateTrueColor($this->new_width, $this->new_height);
        $transparent = true;
        if ($this->bgColor['a'] > 0 || $transparent) {
            imagealphablending($image_c, false);
            imagesavealpha($image_c, true);
        }

        $new_image = \call_user_func($this->imageCreateFunc, $this->inputFile);

        if (!$transparent || intval($this->method) == 5) {
            // create background
            if (intval($this->method) == 4) {
                // Addaptive gradient
                $c1 = imagecolorat($new_image, 1, 1);
                $c2 = imagecolorat($new_image, imagesx($new_image) - 2, 1);
                $c3 = imagecolorat($new_image, 1, imagesy($new_image) - 2);
                $c4 = imagecolorat($new_image, imagesx($new_image) - 2, imagesy($new_image) - 2);
                imagegradient($image_c, 0, 0, $this->new_width, $this->new_height, $c1, $c2, $c3, $c4);
            } elseif (intval($this->method) == 5) {
                // Flat background
                if ($this->bgColor['a'] > 0) {
                    $bg_color = imagecolorallocatealpha($image_c, $this->bgColor['r'], $this->bgColor['g'], $this->bgColor['b'], $this->bgColor['a']);
                } else {
                    $bg_color = imagecolorallocate($image_c, $this->bgColor['r'], $this->bgColor['g'], $this->bgColor['b']);
                }

                imagefilledrectangle($image_c, 0, 0, $this->new_width, $this->new_height, $bg_color);

                // From edges

                $marginRight = $width;
                $marginLeft = $width;

                $marginBottom = 0;

                $background = imagecolorat($new_image, 1, 1);
                $backgroundRgb = array(($background >> 16) & 0xff, (($background >> 8)) & 0xff, $background & 0xff);

                $colorTop = 0;
                $colorTopSet = false;

                $colorLeft = 0;
                $colorLeftSet = false;

                $colorBottom = 0;
                $colorBottomSet = false;

                $colorRight = 0;
                $colorRightSet = false;

                $tolerance = 10;

                //echo $background.'<br />';
                for ($y = 1; $y < $height; $y++) {
                    for ($x = 1; $x < $width; $x++) {
                        $colorLeftTop = imagecolorat($new_image, $x, $y);
                        $colorLeftTopRgb = array(($colorLeftTop >> 16) & 0xff, (($colorLeftTop >> 8)) & 0xff, $colorLeftTop & 0xff);

                        // echo $colorLeftTop . '<br />';
                        $colorRightBottom = imagecolorat($new_image, $width - $x - 1, $height - $y - 1);
                        $colorRightBottomRgb = array(($colorRightBottom >> 16) & 0xff, (($colorRightBottom >> 8)) & 0xff, $colorRightBottom & 0xff);
                        // Detect edge top left
                        if (($colorLeftTopRgb[0] < $backgroundRgb[0] - $tolerance ||
                                $colorLeftTopRgb[0] > $backgroundRgb[0] + $tolerance)
                            &&
                            ($colorLeftTopRgb[1] < $backgroundRgb[1] - $tolerance ||
                                $colorLeftTopRgb[1] > $backgroundRgb[1] + $tolerance)
                            &&
                            ($colorLeftTopRgb[2] < $backgroundRgb[2] - $tolerance ||
                                $colorLeftTopRgb[2] > $backgroundRgb[2] + $tolerance)
                        ) {
                            if (!$colorTopSet) {
                                $marginTop = $y;
                                $colorTopSet = true;
                            }

                            if ($x < $marginLeft) {
                                $marginLeft = $x;
                            }
                        }
                        // Detect edge bottom  right
                        if (($colorRightBottomRgb[0] < $backgroundRgb[0] - $tolerance ||
                                $colorRightBottomRgb[0] > $backgroundRgb[0] + $tolerance)
                            &&
                            ($colorRightBottomRgb[1] < $backgroundRgb[1] - $tolerance ||
                                $colorRightBottomRgb[1] > $backgroundRgb[1] + $tolerance)
                            &&
                            ($colorRightBottomRgb[2] < $backgroundRgb[2] - $tolerance ||
                                $colorRightBottomRgb[2] > $backgroundRgb[2] + $tolerance)
                        ) {
                            if (!$colorBottomSet) {
                                $marginBottom = $y - 1;
                                $colorBottomSet = true;
                            }

                            if ($x < $marginRight) {
                                $marginRight = $x - 1;
                            }
                        }
                    }
                }

                // New width and height
                $width = $width - $marginLeft - $marginRight;
                $height = $height - $marginTop - $marginBottom;

                // Add padding
                if ($this->new_width > 290 && $this->new_height > 150) {
                    $paddingLeft = 5;
                    $paddingRight = 5;
                    $paddingTop = 5;
                    $paddingBottom = 5;
                }
                if ($this->new_width > 400 && $this->new_height > 250) {
                    $paddingLeft = 15;
                    $paddingRight = 15;
                    $paddingTop = 15;
                    $paddingBottom = 15;
                }

                $factorOld = (float) $width / (float) $height;
                $factor = (float) $this->new_width / (float) $this->new_height;
                if ($factorOld < $factor) {
                    //the original image is wider then the
                    //rescaled image so scale by height
                    $heightNew = $this->new_height;
                    $widthNew = $this->new_height * $factorOld;
                } else {
                    //the original image is taller then the
                    //rescaled image so scale by width to fit in
                    $widthNew = $this->new_width;
                    $heightNew = $this->new_width / $factorOld;
                }
                //alignment
                switch ($alignX) {
                    case 'center':
                        $x = ($this->new_width - $widthNew) / 2;
                        break;
                    case 'left':
                        $x = 0;
                        break;
                    case 'right':
                        $x = ($this->new_width - $widthNew);
                        break;
                }
                switch ($alignY) {
                    case 'center':
                        $y = ($this->new_height - $heightNew) / 2;
                        break;
                    case 'top':
                        $y = 0;
                        break;
                    case 'bottom':
                        $y = ($this->new_height - $heightNew);
                        break;
                }

                if ($transparent) {
                    $transparentColor = imagecolorallocatealpha($image_c, 255, 255, 255, 127);
                    imagefilledrectangle($image_c, 0, 0, $this->new_width, $this->new_height, $transparentColor);
                }
            } else {
                // Flat background
                if ($this->bgColor['a'] > 0) {
                    $bg_color = imagecolorallocatealpha($image_c, $this->bgColor['r'], $this->bgColor['g'], $this->bgColor['b'], $this->bgColor['a']);
                } else {
                    $bg_color = imagecolorallocate($image_c, $this->bgColor['r'], $this->bgColor['g'], $this->bgColor['b']);
                }

                imagefilledrectangle($image_c, 0, 0, $this->new_width, $this->new_height, $bg_color);
            }
        } else {
            $transparentColor = imagecolorallocatealpha($image_c, 255, 255, 255, 127);
            imagefilledrectangle($image_c, 0, 0, $this->new_width, $this->new_height, $transparentColor);
        }

        ImageCopyResampled($image_c, $new_image, $x + $paddingLeft, $y + $paddingTop, $marginLeft, $marginTop, $widthNew - $paddingRight - $paddingLeft, $heightNew - $paddingBottom - $paddingTop, $width, $height);

        if ($this->extension === 'jpg') {
            $defaultQuality = self::JPEG_LOW_QUALITY;
            $quality = $defaultQuality;
            if (1 === $this->quality) {
                $quality = self::JPEG_HIGH_QUALITY;
            }
            $process = call_user_func($this->imageSaveFunc, $image_c, $save_path, $quality);
        } elseif ($this->extension === 'png') {
            $process = call_user_func($this->imageSaveFunc, $image_c, $save_path, 9, PNG_ALL_FILTERS);
        } else {
            // Gif/bmp
            $process = call_user_func($this->imageSaveFunc, $image_c, $save_path);
        }

        $optimizer = OptimizerChainFactory::create();
        try {
            $size = filesize($save_path);
            $optimizer->optimize($save_path);
        } catch (\Exception $exception) {
            $this->log('Failed optimizing ' . basename($cachePath) . ', error: ' . $exception->getMessage());
        }
        $this->log('Optimized ' . basename($save_path) . ' from ' . $size . ' to ' . filesize($save_path) . ' bytes');

        @chmod($save_path, 0666);
        header("Content-Type: " . $this->mime);
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($save_path)) . ' GMT');
        readfile($save_path);
        imagedestroy($image_c);
        imagedestroy($new_image);

        if (!isset($process)) {
            $process = null;
        }

        return array(
            'result' => $process,
            'new_file_path' => $save_path,
        );
    }

    protected function log(string $message)
    {
        file_put_contents(
            dirname(__DIR__) . '/log/images-optimize.log',
            date('Y-m-d H:i:s') . ': ' . $message . "\n",
            FILE_APPEND
        );
    }

    /**
     * Get safe filename
     *
     * @param string $filename
     * @return string
     */
    public function getSafeFilename($filename)
    {
        $string = urldecode($filename);
        $string = trim($string);
        $string = strtolower($string);
        $string = trim(preg_replace("/[^ A-Za-z0-9_]/", " ", $string));
        $string = str_replace(" ", '_', $string);
        $string = preg_replace("/[ _]+/", "_", $string);
        return $string;
    }
}