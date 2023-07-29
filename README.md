# image-upload
Image Upload component for Nette
## Instalace

```
composer require jdvorak23/image-upload
```

Ukládá do složky `%wwwDir%/%imagesDir%/$directory`
- `%wwwDir%` je vytvořený Nette
- `%imagesDir%` parametr nastavíme v common.neon
- `$directory` parametr konstruktoru komponenty

`common.neon` (např):
```neon
parameters:
    imagesDir: 'images/articles/'
```

Zaregistruji v `services.neon`:
```neon
- Jdvorak23\ImageUpload\ImageUploadFactory
- Jdvorak23\ImageUpload\ImageModel(wwwDir: "%wwwDir%", imagesDir: "%imagesDir%")
```

Někdy je potřeba nastavit  práva zápisu (podle nastavení `%imagesDir%`):
```shell
chmod 777 articles
```

### javascript
Potřebuje javascript. Použít script ve složce `/assets`

index.js (main):
```javascript
import ImageUpload from "../imageUpload"; // Podle toho kam se zkopírovalo
window.ImageUpload = ImageUpload;
```

Pak v templatě kde máme komponentu:
```html
<script>
    const imageUpload = new window.ImageUpload();
</script>
```


### Vytvoření komponenty
```php
    // DI továrny - konstruktor, inject, ...
    private readonly ImageUploadFactory $imageUploadFactory
    // $this->directory velmi často: $this->articleId
    public function createComponentImageUpload(): ImageUploadControl
    {
        if(!$this->directory)
            throw new InvalidStateException("Directory is not set.");
        return $this->imageUploadFactory->create($this->directory);
    }
```
A v templatě:
```latte
{control imageUpload}
```