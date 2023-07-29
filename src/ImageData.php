<?php

namespace App\AdminModule\Components\ImageUpload;

class ImageData
{
    /**
     * @param string $name
     * @param string $path its URL path relative to domain, e.g.: /images/articles/52/my_img.jpg
     */
    public function __construct(public readonly string $name,
                                public readonly string $path)
    {
    }
}