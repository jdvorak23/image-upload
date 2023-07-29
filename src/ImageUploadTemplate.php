<?php

namespace Jdvorak23\ImageUpload;

use Nette\Bridges\ApplicationLatte\Template;

class ImageUploadTemplate extends Template
{
    /** @var ImageData[] */
    public array $images;
    public ?ImageData $thumb;
}