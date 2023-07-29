// Shadow dependency - Bootstrap 5, Font Awesome
// External
import defaults from "defaults";
import naja from "naja";
// Internal
import clipboard from "../../../../../app/js/internal/clipboard";
import confirmModal from "../../../../../app/js/internal/confirmModal";

const defaultOptions = {
    fileUploadId:  "imageUploadControlFileUpload", // V template controlu ImageUpload, mimo snippet
    // Default $id formuláře na upload. Vytváří se zde dynamicky.
    // Může být potřeba změnit, pokud není komponenta přímo v presenteru, ale v další komponentě
    imagesFormId: "frm-imageUpload-imagesForm",
    imagesFormUploadIdPart: "images", // Pouze část, výsledné id = imagesFormId + "-" + imagesFormUploadIdPart
    imagesFormUploadName: "images[]",
    // Div co obsahuje obrázky
    imagesElementId: "imageUploadControlImagesElement",
    // Odkaz na kopírování obrázku
    imageCopyAnchorClass: "copy-image",
    imageCopyAnchorDataSrc: "data-src",
    imageCopyAnchorDataAlt: "data-alt",
}

class ImageUpload
{
    fileUpload;
    imagesForm;
    imagesFormUploadId;
    constructor(options = {}) {
        this.options = defaults(options, defaultOptions);
        this.fileUpload = document.getElementById(this.options.fileUploadId);
        if( !(this.fileUpload instanceof HTMLInputElement) || this.fileUpload.type !== "file")
            throw new Error(`Error in ImageUpload. Option 'fileUploadId' is not a valid selector for file upload input element.`);
        confirmModal.initialize(); // TODO maybe edit
        let doValue = this.options.imagesFormId;
        if(doValue.startsWith('frm-'))
            doValue = doValue.substring(4);
        doValue += '-submit';
        const template = document.createElement('template');
        template.innerHTML = `<form action="${window.location.href}" method="post" enctype="multipart/form-data" 
                                    id="${this.options.imagesFormId}" novalidate="novalidate" class="ajax" data-naja-history="off">
                                <input type="hidden" name="_do" value="${doValue}">
                                <!--[if IE]><input type=IEbug disabled style="display:none"><![endif]-->
                              </form>`
        this.imagesForm = template.content.firstElementChild;
        document.body.append(this.imagesForm);
        naja.uiHandler.bindUI(this.imagesForm);
        this.imagesFormUploadId = this.options.imagesFormId + "-" + this.options.imagesFormUploadIdPart;
        this.fileUpload.addEventListener("change", () => {
            this._uploadImages();
        });
        this._setCopyButtons();
    }
    _uploadImages(){
        const formFileUpload = this.fileUpload.cloneNode();
        formFileUpload.setAttribute("id", this.imagesFormUploadId);
        formFileUpload.setAttribute("name", this.options.imagesFormUploadName);
        this.imagesForm.appendChild(formFileUpload);
        const submitButton = document.createElement("INPUT");
        submitButton.type = "submit";
        this.imagesForm.appendChild(submitButton);
        submitButton.click();
        submitButton.remove();
        formFileUpload.remove();
        this._setCopyButtons();
    }
    _setCopyButtons(){
        const imagesElement = document.getElementById(this.options.imagesElementId);
        if( !(imagesElement instanceof Element) )
            throw new Error(`Error in ImageUpload. Option 'imagesElementId' is not a valid selector for Element with images.`);
        for(let copyImageButton of [... imagesElement.querySelectorAll("." + this.options.imageCopyAnchorClass)]){
            const src = copyImageButton.getAttribute(this.options.imageCopyAnchorDataSrc) || "";
            const alt = copyImageButton.getAttribute(this.options.imageCopyAnchorDataAlt) || "";
            copyImageButton.addEventListener("click", ()=> {
                clipboard.copyImageHTML(src, alt, "img-fluid");
            });
        }
    }
}

export default ImageUpload;

