<?php

namespace App\AdminModule\Components\ImageUpload;

use App\AdminModule\Components\ImageUpload\Exceptions\BadFileFolderNameException;
use App\AdminModule\Components\ImageUpload\Exceptions\DirectoryNotEmptyException;
use Nette\Http\FileUpload;
use Nette\Utils\Image;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use Nette\Utils\ImageException;
use Nette\Utils\UnknownImageFileException;

class ImageModel
{
    /**
     * @param string $wwwDir kořenová složka projektu, definováno v config
     * @param string $imagesDir relativní složka pro obrázky článků, definováno v config
     */
    public function __construct(protected string $wwwDir,
                                protected string $imagesDir)
    {
    }

    /**
     * Získá images z daného directory. Celá cesta je $wwwDir/$imagesDir/$directoryName
     * Pokud folder neexistuje, vrací []
     * @param string $directoryName
     * @return ImageData[]
     * @throws BadFileFolderNameException Pokud $directoryName obsahuje nepovolené ".."
     */
    public function getImages(string $directoryName): array
    {
        $path = $this->getImagesDirectory($directoryName);
        if(!is_dir($path))
            return [];
        foreach (Finder::findFiles('*')->in($path)->sortByName() as $file){
            if(!Image::detectTypeFromFile($file))
                continue;
            $imageName = $file->getFilename();
            if($imageName === 'thumb.jpg')
                continue;
            $images[] = new ImageData($imageName, str_replace($this->wwwDir, "", $file));
        }
        return $images ?? [];
    }

    /**
     * Získá thumb.jpg z dané folder. Pokud neexistuje, vrací null
     * @param string $directoryName
     * @return ImageData|null
     * @throws BadFileFolderNameException Pokud $directoryName obsahuje nepovolené ".."
     */
    public function getThumb(string $directoryName): ?ImageData
    {
        $thumbPath = FileSystem::joinPaths($this->getImagesDirectory($directoryName), 'thumb.jpg');
        if(!file_exists($thumbPath))
            return null;
        return new ImageData('thumb.jpg', str_replace($this->wwwDir, "", $thumbPath));
    }

    /**
     * Smaže obrázek, pokud existuje a je obrázek
     * @param $directoryName
     * @param $imageName
     * @return void
     * @throws BadFileFolderNameException Pokud $directoryName nebo $imageName obsahuje nepovolené "..",
     * nebo soubor neexistuje, nebo není obrázek
     */
    public function deleteImage(string $directoryName, string $imageName): void
    {
        $path = $this->getImageFullPath($directoryName, $imageName);
        if(!file_exists($path))
            throw new BadFileFolderNameException("File '$path' does not exist.");
        if(!Image::detectTypeFromFile($path))
            throw new BadFileFolderNameException("File '$path' is not an image.");
        unlink($path);
    }
    /**
     * Smaže všechny obrázky v $directoryName.
     * Pokud $directoryName neexistuje, vyhodí výjimku
     * @param string $directoryName
     * @param bool $deleteFolder Jestli smazat i adresář
     * @return void
     * @throws DirectoryNotEmptyException Pokud $deleteFolder = true a neobsahuje pouze obrázky
     * @throws BadFileFolderNameException Pokud $directoryName obsahuje nepovolené "..", nebo directory neexistuje
     */
    public function deleteAllImages(string $directoryName, bool $deleteFolder = true): void
    {
        $path = $this->getImagesDirectory($directoryName);
        if(!is_dir($path))
            throw new BadFileFolderNameException("Directory '$path' does not exists");
        foreach (Finder::findFiles('*')->in($path) as $file) {
            if(!Image::detectTypeFromFile($file))
                continue;
            unlink($file);
        }
        if(!$deleteFolder)
            return;
        if(count(Finder::find('*')->in($path)->collect()))
            throw new DirectoryNotEmptyException(str_replace($this->wwwDir, "", $path));
        rmdir($path);
    }

    /**
     * Vytvoří miniaturu ze zadaného obrázku
     * @param string $directoryName
     * @param string $imageName
     * @return void
     * @throws ImageException Chyba při manipulaci s obrázkem
     * @throws UnknownImageFileException Pokud není obrázek známého typu
     * @throws BadFileFolderNameException Pokud $directoryName nebo $imageName obsahuje nepovolené "..", nebo soubor neexistuje
     */
    public function createThumb(string $directoryName, string $imageName): void
    {
        $path = $this->getImageFullPath($directoryName, $imageName);
        if(!file_exists($path))
            throw new BadFileFolderNameException("Image '$path' does not exists");
        $thumb = Image::fromFile($path);
        $thumb->resize(564, 452);
        $thumb->save(FileSystem::joinPaths($this->getImagesDirectory($directoryName), 'thumb.jpg'), 90, Image::JPEG);
    }

    /**
     * Uloží uploadované obrázky. Pokud soubor není obrázek, nebo je NOK uploadovaný, vynechá ho
     * @param string $directoryName
     * @param FileUpload[] $images
     * @return void
     * @throws BadFileFolderNameException Pokud jméno některého obrázku obsahuje nepovolené ".."
     */
    public function saveImages(string $directoryName, array $images): void
    {
        $directory = $this->getImagesDirectory($directoryName);
        // Vytvářím ručně (move by taky vytvořilo) abych měl vlastní práva
        if(!is_dir($directory))
            $this->createImagesFolder($directory);
        // Nahraje obrázky
        /** @var FileUpload $image*/
        foreach ($images as $image) {
            if($image->isOk() && $image->isImage()){
                $fileFullName = $this->findUniqueFileName($directory, $image->name);
                $image->move($fileFullName);
            }
        }
    }

    /**
     * Získá kompletní cestu k souboru obrázku
     * Výsledná cesta je $wwwDir/$imagesDir/$directoryName/$imageName
     * @param string $directoryName Název složky s obrázky. Nesmí obsahovat ".."
     * @param string $imageName Jméno souboru obrázku. Nesmí obsahovat ".."
     * @return string kompletní cesta souboru obrázku
     * @throws BadFileFolderNameException Pokud $directoryName nebo $imageName obsahuje nepovolené "..",
     * obrana proti Directory traversal attack
     */
    protected function getImageFullPath(string $directoryName, string $imageName): string
    {
        if($imageName === '')
            throw new BadFileFolderNameException('Parameter "$fileName" can not be empty string.');
        if($imageName === '')
            throw new BadFileFolderNameException('Parameter "$fileName" can not be empty string.');
        if(str_contains($imageName, '..'))
            throw new BadFileFolderNameException('Parameter $imageName can not contain ".."');
        return FileSystem::joinPaths($this->getImagesDirectory($directoryName), $imageName);
    }

    /**
     * Vrací absolutní cestu adresáře.
     * Výsledná cesta je $wwwDir/$imagesDir/$directoryName
     * @param string $directoryName Název konkrétního adresáře s obrázky. Nesmí obsahovat ".."
     * @return string Absolutní cesta ke složce
     * @throws BadFileFolderNameException Pokud $directoryName obsahuje nepovolené "..",
     * obrana proti Directory traversal attack
     */
    protected function getImagesDirectory(string $directoryName): string
    {
        if($directoryName === '')
            throw new BadFileFolderNameException('Parameter "$directoryName" can not be empty string.');
        if(str_contains($directoryName, '..'))
            throw new BadFileFolderNameException('Parameter "$directoryName" can not contain ".."');
        return FileSystem::joinPaths($this->wwwDir, $this->imagesDir, $directoryName);
    }

    /**
     * Najde jedinečný název souboru - přidá _1, _2 ... pokud stejný soubor existuje
     * @param string $directory Celá!!! path adresáře
     * @param string $fileName Název souboru
     * @return string Celý název souboru
     * @throws BadFileFolderNameException Pokud $fileName obsahuje nepovolené ".."
     */
    protected function findUniqueFileName(string $directory, string $fileName): string
    {
        if($fileName === '')
            throw new BadFileFolderNameException('Parameter "$fileName" can not be empty string.');
        if(str_contains($fileName, '..'))
            throw new BadFileFolderNameException('Parameter "$fileName" can not contain ".."');
        $path = FileSystem::joinPaths($directory, $fileName);
        if(!file_exists($path))
            return $path;

        $fileExt = $this->getFileExt($fileName);
        $fileNameWoExt = str_replace($fileExt, "", $fileName);
        $i = 1;
        while (file_exists($path)) {
            $path = FileSystem::joinPaths($directory, $fileNameWoExt . '_' . $i . $fileExt);
            $i++;
        }
        return $path;
    }

    /**
     * Získá příponu souboru
     * @param string $fileName Název bez cesty
     * @return string
     */
    protected function getFileExt(string $fileName): string
    {
        $extPos = mb_strrpos($fileName, ".");
        return $extPos === false ? "" : mb_substr($fileName, $extPos);
    }

    protected function createImagesFolder(string $path): void
    {
        //Aby php nastavilo v dalším řádku správně permissions (0777), tak se musí změnit defaultní umask.
        //umask() vrací předchozí umask (tj. umask před změnou)
        $originalUmask = umask(0);
        //Vytvoření adresáře
        FileSystem::createDir($path);
        //Tady se umask zase vrátí na předchozí hodnotu.
        umask($originalUmask);
    }
}
