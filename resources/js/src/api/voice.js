import axios from "@/api/axios";

export const uploadVoiceRecord = async (audioBlob) => {
    const formData = new FormData();
    const mime = audioBlob.type || "";
    const ext = mime.includes("mp4") ? "m4a" : "webm";
    formData.append("audio", audioBlob, `record.${ext}`);

    try {
        const response = await axios.post("/api/voice/upload", formData, {
            headers: {
                "Content-Type": "multipart/form-data",
            },
        });

        return response;
    } catch (error) {
        console.error("Error uploading voice record:", error);
        throw error;
    }
};

export const saveVoiceProducts = async (products, mealType) => {
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
        }
    });

    try {
        const response = await axios.post("/api/voice/save-products", {
            products,
            meal_type: mealType,
        });

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
        const response = await axios.post("/api/voice/generate-product", {
            product_name: productName,
        });

        return response;
    } catch (error) {
        console.error("Error generating product data:", error);
        if (error.response && error.response.data) {
            console.error("Server error details:", error.response.data);
        }
        throw error;
    }
};

export const getGenerateProductStatus = async (rid) => {
    try {
        const response = await axios.get("/api/voice/generate-product/status", {
            params: { rid },
        });
        return response;
    } catch (error) {
        console.error("Error get generate product status:", error);
        throw error;
    }
};

export const getVoiceStatus = async (rid) => {
    try {
        const response = await axios.get("/api/voice/status", {
            params: { rid },
        });
        return response;
    } catch (error) {
        console.error("Error get voice status:", error);
        throw error;
    }
};

export const searchProduct = async (productName) => {
    try {
        const response = await axios.post("/api/voice/search-product", {
            product_name: productName,
        });

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
    getGenerateProductStatus,
    getVoiceStatus,
    searchProduct,
};
