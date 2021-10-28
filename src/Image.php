<?php

namespace Zoolt\Image;

use Zoolt\Image\Exceptions\InvalidManipulation;

class Image
{
    /** @var ?string */
    public $inputFile;
    /** @var ?string */
    public $imageData;
    private $manipulationChain = [];
    private $targetWidth = null;
    private $targetHeight = null;
    private $fitMethod = Manipulations::FIT_CONTAIN;
    private $temporaryDirectory = null;
    private $outputFormat = null;
    protected $background = array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0);

    public function __construct($filename, $imageData)
    {
        $this->inputFile = $filename;
        $this->imageData = $imageData;
    }

    /**
     * Load image and return new instance
     *
     * @param string $filename Full or relative path to file
     * @return Image instance
     */
    public static function load($filename)
    {
        return new static($filename, null);
    }

    /**
     * Load image and return new instance
     *
     * @param string $imageData image data as string
     * @return Image instance
     */
    public static function loadString($imageData)
    {
        return new static(null, $imageData);
    }

    public function background($color)
    {
        $color = $this->cssColorToHex(ltrim($color, '#'));
        if ($color === false) {
            throw InvalidManipulation::invalidParameter(__FUNCTION__, $color, ['css color']);
        }
        $parts = str_split($color, 2);
        if (count($parts) == 3) {
            $this->background['r'] = hexdec($parts[0]);
            $this->background['g'] = hexdec($parts[1]);
            $this->background['b'] = hexdec($parts[2]);
        } else {
            $this->background['r'] = hexdec($parts[0]);
            $this->background['g'] = hexdec($parts[1]);
            $this->background['b'] = hexdec($parts[2]);
            $this->background['a'] = hexdec($parts[3]);
        }
        return $this;
    }

    /**
     * Optimize the image
     *
     * @return $this
     */
    public function optimize($options = [])
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
        $this->manipulationChain[] = [
            'quality' => $quality,
        ];
        return $this;
    }

    public function pixelate($amount)
    {
        if ($amount < 0 || $amount > 100) {
            throw InvalidManipulation::valueNotInRange(__FUNCTION__, $amount, 0, 100);
        }
        $this->manipulationChain[] = [
            'imagefilter' => [
                'method' => IMG_FILTER_PIXELATE,
                'arg1' => $amount,
            ],
        ];
        return $this;
    }

    public function blur($amount)
    {
        if ($amount < 0 || $amount > 100) {
            throw InvalidManipulation::valueNotInRange(__FUNCTION__, $amount, 0, 100);
        }

        $this->manipulationChain[] = [
            'blur' => $amount,
        ];

        return $this;
    }

    public function brightness($amount)
    {
        if ($amount < -100 || $amount > 100) {
            throw InvalidManipulation::valueNotInRange(__FUNCTION__, $amount, -100, 100);
        }
        $this->manipulationChain[] = [
            'imagefilter' => [
                'method' => IMG_FILTER_BRIGHTNESS,
                'arg1' => round($amount * 2.55), // filter accepts -255 - 255
            ],
        ];
        return $this;
    }

    public function contrast($amount)
    {
        if ($amount < -100 || $amount > 100) {
            throw InvalidManipulation::valueNotInRange(__FUNCTION__, $amount, -100, 100);
        }
        $this->manipulationChain[] = [
            'imagefilter' => [
                'method' => IMG_FILTER_CONTRAST,
                'arg1' => $amount,
            ],
        ];
        return $this;
    }

    public function greyscale()
    {
        $this->manipulationChain[] = [
            'imagefilter' => [
                'method' => IMG_FILTER_GRAYSCALE,
            ],
        ];
        return $this;
    }

    public function gamma($value)
    {
        if ($value < 0.1 || $value > 9.99) {
            throw InvalidManipulation::valueNotInRange(__FUNCTION__, $value, 0.1, 9.99);
        }
        $this->manipulationChain[] = [
            'gamma' => $value,
        ];
        return $this;
    }

    public function orientation($value)
    {
        if ($value === 'auto' || intval($value) <= 0 || intval($value) > 360) {
            throw InvalidManipulation::valueNotInRange(__FUNCTION__, $value, 0, 360);
        }
        $this->manipulationChain[] = [
            'orientation' => $value,
        ];
        return $this;
    }

    public function flip($mode)
    {
        $validModes = [Manipulations::FLIP_HORIZONTALLY, Manipulations::FLIP_VERTICALLY, Manipulations::FLIP_BOTH];
        if (!in_array($mode, $validModes)) {
            throw InvalidManipulation::invalidParameter(__FUNCTION__, $mode, $validModes);
        }
        $this->manipulationChain[] = [
            'flip' => $mode,
        ];
        return $this;
    }

    public function fit($method, $width, $height)
    {
        $methods = [
            Manipulations::FIT_CONTAIN,
            Manipulations::FIT_MAX,
            Manipulations::FIT_FILL,
            Manipulations::FIT_STRETCH,
            Manipulations::FIT_CROP,
        ];
        if (!in_array($method, $methods)) {
            throw InvalidManipulation::invalidParameter(__FUNCTION__, $method, $methods);
        }

        $this->fitMethod = $method;
        $this->height($height);
        $this->width($width);
        return $this;
    }

    public function sepia()
    {
        // See http://php.net/manual/en/function.imagefilter.php#119025
        $this->manipulationChain[] = [
            'imagefilter' => [
                'method' => IMG_FILTER_GRAYSCALE,
            ],
        ];
        $this->manipulationChain[] = [
            'imagefilter' => [
                'method' => IMG_FILTER_BRIGHTNESS,
                'arg1' => -30,
            ],
        ];
        $this->manipulationChain[] = [
            'imagefilter' => [
                'method' => IMG_FILTER_COLORIZE,
                'arg1' => 90,
                'arg2' => 55,
                'arg3' => 30,
            ],
        ];
        return $this;
    }

    public function watermark()
    {
        return $this;
    }
    public function watermarkOpacity()
    {
        return $this;
    }
    public function watermarkFit()
    {
        return $this;
    }
    public function watermarkPosition()
    {
        return $this;
    }
    public function watermarkPadding()
    {
        return $this;
    }
    public function sharpen()
    {
        return $this;
    }
    public function manualCrop()
    {
        return $this;
    }
    public function focalCrop()
    {
        return $this;
    }
    /**
     * Crop from the center of the image
     */
    public function crop($width, $height)
    {
        $this->manipulationChain[] = [
            'crop' => [
                'width' => $width,
                'height' => $height,
                'gravity' => 'center',
            ],
        ];
        return $this;
    }
    public function border()
    {
        return $this;
    }

    public function watermarkWidth()
    {
        return $this;
    }
    public function watermarkHeight()
    {
        return $this;
    }
    public function devicePixelRatio()
    {
        return $this;
    }

    public function format($format)
    {
        $this->outputFormat = $format;
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
        if ($width < 0) {
            throw InvalidManipulation::valueNotInRange(__FUNCTION__, $width, 0, 'inf');
        }
        $this->targetWidth = $width;
        return $this;
    }

    /**
     * Set the containing height (image is contained within this height) keeping aspect ratio
     *
     * @param int $height Max height of the image
     * @return $this
     */
    public function height(int $height)
    {
        if ($height < 0) {
            throw InvalidManipulation::valueNotInRange(__FUNCTION__, $height, 0, 'inf');
        }
        $this->targetHeight = $height;
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

        if ($this->targetWidth !== null || $this->targetHeight !== null) {
            // Scale image at first to speed up proces
            array_unshift($this->manipulationChain, [
                'scale' => [
                    $this->fitMethod,
                    $this->targetWidth,
                    $this->targetHeight,
                ]
            ]);
        }

        if ($this->inputFile !== null) {
            $converter = GD2Conversion::create($this->inputFile);
        } else {
            $converter = GD2Conversion::createFromString($this->imageData);
        }
        $converter
            ->background($this->background)
            ->performManipulations($this->manipulationChain)
            ->save($destination, $this->outputFormat);
    }

    /**
     * Retrieve the width of the input-file
     * @return int width of the image
     */
    public function getWidth()
    {
        $info = getimagesize($this->inputFile);
        return $info[0];
    }

    /**
     * Retrieve the width of the input-file
     * @return int height of the image
     */
    public function getHeight()
    {
        $info = getimagesize($this->inputFile);
        return $info[1];
    }

    private function cssColorToHex($color)
    {
        // See https://stackoverflow.com/a/5925612
        $colors = [
            'aliceblue' => 'F0F8FF',
            'antiquewhite' => 'FAEBD7',
            'aqua' => '00FFFF',
            'aquamarine' => '7FFFD4',
            'azure' => 'F0FFFF',
            'beige' => 'F5F5DC',
            'bisque' => 'FFE4C4',
            'black' => '000000',
            'blanchedalmond ' => 'FFEBCD',
            'blue' => '0000FF',
            'blueviolet' => '8A2BE2',
            'brown' => 'A52A2A',
            'burlywood' => 'DEB887',
            'cadetblue' => '5F9EA0',
            'chartreuse' => '7FFF00',
            'chocolate' => 'D2691E',
            'coral' => 'FF7F50',
            'cornflowerblue' => '6495ED',
            'cornsilk' => 'FFF8DC',
            'crimson' => 'DC143C',
            'cyan' => '00FFFF',
            'darkblue' => '00008B',
            'darkcyan' => '008B8B',
            'darkgoldenrod' => 'B8860B',
            'darkgray' => 'A9A9A9',
            'darkgreen' => '006400',
            'darkgrey' => 'A9A9A9',
            'darkkhaki' => 'BDB76B',
            'darkmagenta' => '8B008B',
            'darkolivegreen' => '556B2F',
            'darkorange' => 'FF8C00',
            'darkorchid' => '9932CC',
            'darkred' => '8B0000',
            'darksalmon' => 'E9967A',
            'darkseagreen' => '8FBC8F',
            'darkslateblue' => '483D8B',
            'darkslategray' => '2F4F4F',
            'darkslategrey' => '2F4F4F',
            'darkturquoise' => '00CED1',
            'darkviolet' => '9400D3',
            'deeppink' => 'FF1493',
            'deepskyblue' => '00BFFF',
            'dimgray' => '696969',
            'dimgrey' => '696969',
            'dodgerblue' => '1E90FF',
            'firebrick' => 'B22222',
            'floralwhite' => 'FFFAF0',
            'forestgreen' => '228B22',
            'fuchsia' => 'FF00FF',
            'gainsboro' => 'DCDCDC',
            'ghostwhite' => 'F8F8FF',
            'gold' => 'FFD700',
            'goldenrod' => 'DAA520',
            'gray' => '808080',
            'green' => '008000',
            'greenyellow' => 'ADFF2F',
            'grey' => '808080',
            'honeydew' => 'F0FFF0',
            'hotpink' => 'FF69B4',
            'indianred' => 'CD5C5C',
            'indigo' => '4B0082',
            'ivory' => 'FFFFF0',
            'khaki' => 'F0E68C',
            'lavender' => 'E6E6FA',
            'lavenderblush' => 'FFF0F5',
            'lawngreen' => '7CFC00',
            'lemonchiffon' => 'FFFACD',
            'lightblue' => 'ADD8E6',
            'lightcoral' => 'F08080',
            'lightcyan' => 'E0FFFF',
            'lightgoldenrodyellow' => 'FAFAD2',
            'lightgray' => 'D3D3D3',
            'lightgreen' => '90EE90',
            'lightgrey' => 'D3D3D3',
            'lightpink' => 'FFB6C1',
            'lightsalmon' => 'FFA07A',
            'lightseagreen' => '20B2AA',
            'lightskyblue' => '87CEFA',
            'lightslategray' => '778899',
            'lightslategrey' => '778899',
            'lightsteelblue' => 'B0C4DE',
            'lightyellow' => 'FFFFE0',
            'lime' => '00FF00',
            'limegreen' => '32CD32',
            'linen' => 'FAF0E6',
            'magenta' => 'FF00FF',
            'maroon' => '800000',
            'mediumaquamarine' => '66CDAA',
            'mediumblue' => '0000CD',
            'mediumorchid' => 'BA55D3',
            'mediumpurple' => '9370D0',
            'mediumseagreen' => '3CB371',
            'mediumslateblue' => '7B68EE',
            'mediumspringgreen' => '00FA9A',
            'mediumturquoise' => '48D1CC',
            'mediumvioletred' => 'C71585',
            'midnightblue' => '191970',
            'mintcream' => 'F5FFFA',
            'mistyrose' => 'FFE4E1',
            'moccasin' => 'FFE4B5',
            'navajowhite' => 'FFDEAD',
            'navy' => '000080',
            'oldlace' => 'FDF5E6',
            'olive' => '808000',
            'olivedrab' => '6B8E23',
            'orange' => 'FFA500',
            'orangered' => 'FF4500',
            'orchid' => 'DA70D6',
            'palegoldenrod' => 'EEE8AA',
            'palegreen' => '98FB98',
            'paleturquoise' => 'AFEEEE',
            'palevioletred' => 'DB7093',
            'papayawhip' => 'FFEFD5',
            'peachpuff' => 'FFDAB9',
            'peru' => 'CD853F',
            'pink' => 'FFC0CB',
            'plum' => 'DDA0DD',
            'powderblue' => 'B0E0E6',
            'purple' => '800080',
            'red' => 'FF0000',
            'rosybrown' => 'BC8F8F',
            'royalblue' => '4169E1',
            'saddlebrown' => '8B4513',
            'salmon' => 'FA8072',
            'sandybrown' => 'F4A460',
            'seagreen' => '2E8B57',
            'seashell' => 'FFF5EE',
            'sienna' => 'A0522D',
            'silver' => 'C0C0C0',
            'skyblue' => '87CEEB',
            'slateblue' => '6A5ACD',
            'slategray' => '708090',
            'slategrey' => '708090',
            'snow' => 'FFFAFA',
            'springgreen' => '00FF7F',
            'steelblue' => '4682B4',
            'tan' => 'D2B48C',
            'teal' => '008080',
            'thistle' => 'D8BFD8',
            'tomato' => 'FF6347',
            'turquoise' => '40E0D0',
            'violet' => 'EE82EE',
            'wheat' => 'F5DEB3',
            'white' => 'FFFFFF',
            'whitesmoke' => 'F5F5F5',
            'yellow' => 'FFFF00',
            'yellowgreen' => '9ACD32'
        ];
        if (isset($colors[$color])) {
            return $colors[$color];
        }
        if (in_array(strlen($color), [3, 6, 8]) && ctype_xdigit($color)) {
            if (strlen($color) == 3) {
                $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
            }
            return $color;
        }
        return false;
    }
}
