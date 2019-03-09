<?php

namespace Zoolt\Image\Exceptions;

use Exception;

class FileNotFound extends Exception
{
    public static function invalidType(string $filename)
    {
        return new self("the file `{$filename}` has an invalid type");
    }

    public static function nonExisting(string $filename)
    {
        return new self("the file `{$filename}` does not exist");
    }
}
