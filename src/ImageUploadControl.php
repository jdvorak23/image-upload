<?php

namespace Jdvorak23\ImageUpload;


use Jdvorak23\ImageUpload\Exceptions\BadFileFolderNameException;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Utils\ImageException;
use Nette\Utils\UnknownImageFileException;

/**
 * @property-read ImageUploadTemplate $template
 */
class ImageUploadControl extends Control
{
    const defaultTemplate = __DIR__ . '/imageUpload.latte';

    public string $templateFile;

    public function __construct(protected string     $directory,
                                protected ImageModel $imageManager,
                                ?string              $templateFile = null)
    {
        $this->templateFile = $templateFile ?? self::defaultTemplate;
    }

    public function render(): void
    {
        $this->preRender();
        $this->template->render();
    }
    public function renderToString(): string
    {
        $this->preRender();
        return $this->template->renderToString();
    }

    protected function preRender(): void
    {
        $this->template->setFile($this->templateFile);
        $this->template->images = $this->imageManager->getImages($this->directory);
        $this->template->thumb = $this->imageManager->getThumb($this->directory);
    }

    /**
     * Signál pro odstranění obrázku
     * @param string $imageName název obrázku
     */
    public function handleDeleteImage(string $imageName): void
    {
        try {
            $this->imageManager->deleteImage($this->directory, $imageName);
        } catch (BadFileFolderNameException)
        {}
        $this->handleEnd();
    }

    /**
     * Signál pro vytvoření miniatury
     * @param string $imageName název obrázku
     * @return void
     */
    public function handleCreateThumb(string $imageName): void
    {
        try {
            $this->imageManager->createThumb($this->directory, $imageName);
        } catch (BadFileFolderNameException|UnknownImageFileException|ImageException)
        {}
        $this->handleEnd();
    }

    protected function handleEnd(): void
    {
        if ($this->presenter->isAjax()) {
            $this->redrawControl();
        } else {
            $this->presenter->redirect('this');
        }
    }

    protected function createComponentImagesForm(): Form
    {
        $form = new Form();
        $form->addMultiUpload('images', 'Obrázky')
            ->setHtmlAttribute('accept', 'image/*')
            ->setRequired()
            ->addRule(Form::IMAGE, 'Formát jednoho nebo více obrázků není podporován.');
        $form->onSuccess[] = function (Form $form, $values)
        {
            try{
                $this->imageManager->saveImages($this->directory, $values->images);
            }catch (BadFileFolderNameException)
            {}
            $this->handleEnd();
        };
        return $form;
    }

}
