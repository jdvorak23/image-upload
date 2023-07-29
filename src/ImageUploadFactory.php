<?php

namespace App\AdminModule\Components\ImageUpload;

interface ImageUploadFactory
{
    public function create(string $directory, ?string $templateFile = null): ImageUploadControl;
}