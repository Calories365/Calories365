import axios from "@/api/axios";

export const uploadVoiceRecord = async (audioBlob) => {
    const formData = new FormData();
    const mime = audioBlob.type || ""; // ← важно
    const ext = mime.includes("mp4") ? "m4a" : "webm";
    formData.append("audio", audioBlob, `record.${ext}`);

    try {
        const response = await axios.post("/api/voice/upload", formData, {
            headers: {
                "Content-Type": "multipart/form-data",
            },
        });

        // Логируем ответ для отладки
        console.log("Ответ API uploadVoiceRecord:", response.data);

        return response.data;
    } catch (error) {
        console.error("Error uploading voice record:", error);
        throw error;
    }
};

export const saveVoiceProducts = async (products, mealType) => {
    // Логируем данные, отправляемые на сервер
    console.log("Отправляем продукты на сервер:", products);
    console.log("Тип приема пищи:", mealType);

    // Проверяем, что все продукты имеют значение веса
    products.forEach((product, index) => {
        if (!product.weight || product.weight <= 0) {
            console.warn(
                `Продукт #${index + 1} (${
                    product.name
                }) имеет некорректный вес: ${
                    product.weight
                }. Устанавливаем значение по умолчанию 100 грамм.`
            );
            product.weight = 100;
        } else {
            console.log(
                `Продукт #${index + 1} (${product.name}): ${
                    product.weight
                } грамм`
            );
        }
    });

    try {
        const response = await axios.post("/api/voice/save-products", {
            products,
            meal_type: mealType,
        });

        console.log("Ответ API saveVoiceProducts:", response.data);

        return response.data;
    } catch (error) {
        console.error("Error saving voice products:", error);
        if (error.response && error.response.data) {
            console.error("Server error details:", error.response.data);
        }
        throw error;
    }
};

export const generateProductData = async (productName) => {
    try {
        console.log(`Генерация данных для продукта: ${productName}`);

        const response = await axios.post("/api/voice/generate-product", {
            product_name: productName,
        });

        console.log("Ответ API generateProductData:", response.data);

        return response.data;
    } catch (error) {
        console.error("Error generating product data:", error);
        if (error.response && error.response.data) {
            console.error("Server error details:", error.response.data);
        }
        throw error;
    }
};

export const searchProduct = async (productName) => {
    try {
        console.log(`Поиск продукта: ${productName}`);

        const response = await axios.post("/api/voice/search-product", {
            product_name: productName,
        });

        console.log("Ответ API searchProduct:", response.data);

        return response.data;
    } catch (error) {
        console.error("Error searching product:", error);
        if (error.response && error.response.data) {
            console.error("Server error details:", error.response.data);
        }
        throw error;
    }
};

export default {
    uploadVoiceRecord,
    saveVoiceProducts,
    generateProductData,
    searchProduct,
};
