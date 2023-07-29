<?php

namespace Jdvorak23\ImageUpload;

interface ImageUploadFactory
{
    public function create(string $directory, ?string $templateFile = null): ImageUploadControl;
}