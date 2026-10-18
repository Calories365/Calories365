// A phone photo weighs several megabytes: more than the server takes in one
// request, and more than the model needs to read a dish or a label. The photo
// goes as a JPEG no larger than MAX_SIDE on its longer side.
export const MAX_SIDE = 1600;

const QUALITY = 0.85;

/**
 * The size of a picture scaled down to fit maxSide; a smaller one stays as is.
 *
 * @returns {{width: number, height: number}}
 */
export const fitWithin = (width, height, maxSide = MAX_SIDE) => {
    const scale = Math.min(1, maxSide / Math.max(width, height));

    return {
        width: Math.round(width * scale),
        height: Math.round(height * scale),
    };
};

const decode = async (file) => {
    // createImageBitmap turns the picture as the camera's EXIF says.
    if (typeof createImageBitmap === "function") {
        return createImageBitmap(file, { imageOrientation: "from-image" });
    }

    const url = URL.createObjectURL(file);

    try {
        const image = new Image();
        image.src = url;
        await image.decode();

        return image;
    } finally {
        URL.revokeObjectURL(url);
    }
};

/**
 * The photo as a JPEG that fits MAX_SIDE; the file itself when the browser
 * cannot read it, and the server then says whether it takes it.
 *
 * @param {File} file
 * @returns {Promise<File>}
 */
export const resizeImage = async (file) => {
    let picture;

    try {
        picture = await decode(file);
    } catch (error) {
        return file;
    }

    const { width, height } = fitWithin(picture.width, picture.height);
    const canvas = document.createElement("canvas");
    canvas.width = width;
    canvas.height = height;
    canvas.getContext("2d").drawImage(picture, 0, 0, width, height);
    picture.close?.();

    const blob = await new Promise((resolve) =>
        canvas.toBlob(resolve, "image/jpeg", QUALITY)
    );

    return blob ? new File([blob], "photo.jpg", { type: "image/jpeg" }) : file;
};
