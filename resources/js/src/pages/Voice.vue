<script>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faMicrophone, faMicrophoneSlash, faSpinner, faTrash, faMagic, faSave, faCheckCircle, faExclamationTriangle, faSearch } from '@fortawesome/free-solid-svg-icons';
import { mapState, mapActions } from 'vuex';
import { actionTypes } from '@/store/modules/voice';
import { saveVoiceProducts, generateProductData, searchProduct } from '@/api/voice';
import i18n from '@/i18n';
import CaloriesSuccessNotification from '@/Components/CaloriesSuccessNotification.vue';
import CaloriesErrorNotification from '@/Components/CaloriesErrorNotification.vue';

export default {
    name: "Voice",
    components: {
        FontAwesomeIcon,
        CaloriesSuccessNotification,
        CaloriesErrorNotification
    },
    data() {
        return {
            transcription: '',
            products: [],
            successMessage: '',
            showSuccessMessage: false,
            errorMessage: '',
            showErrorMessage: false,
            mediaRecorder: null,
            audioChunks: [],
            stream: null,
            browserSupportsRecording: false,
            originalProducts: {} // Хранит оригинальные значения продуктов для сравнения
        }
    },
    computed: {
        ...mapState({
            isRecording: state => state.voice.isRecording,
            isProcessing: state => state.voice.isProcessing,
            audioBlob: state => state.voice.audioBlob,
            isUploading: state => state.voice.isUploading
        })
    },
    created() {
        // Проверка поддержки MediaDevices API
        this.checkBrowserSupport();
    },
    methods: {
        ...mapActions({
            voiceStartRecording: actionTypes.startRecording,
            voiceStopRecording: actionTypes.stopRecording,
            voiceUploadRecording: actionTypes.uploadRecording,
            voiceResetRecording: actionTypes.resetRecording
        }),
        showSuccess(message) {
            this.successMessage = message;
            this.showSuccessMessage = true;

            setTimeout(() => {
                this.showSuccessMessage = false;
            }, 3000);
        },
        showError(message) {
            this.errorMessage = message;
            this.showErrorMessage = true;

            setTimeout(() => {
                this.showErrorMessage = false;
            }, 3000);
        },
        faSpinner() {
            return faSpinner
        },
        faMicrophoneSlash() {
            return faMicrophoneSlash
        },
        faMicrophone() {
            return faMicrophone
        },
        faTrash() {
            return faTrash
        },
        faMagic() {
            return faMagic
        },
        faSave() {
            return faSave
        },
        faCheckCircle() {
            return faCheckCircle
        },
        faExclamationTriangle() {
            return faExclamationTriangle
        },
        faSearch() {
            return faSearch
        },
        async toggleRecording() {
            if (!this.browserSupportsRecording) {
                this.showError(this.$t('Voice.browserWarning'));
                return;
            }

            if (this.isRecording) {
                await this.stopRecording();
            } else {
                await this.startRecording();
            }
        },
        async startRecording() {
            try {
                if (!this.browserSupportsRecording) {
                    throw new Error(this.$t('Voice.browserWarning'));
                }

                // Используем современный API, если доступен
                if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                    this.stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                }
                // Используем старый API, если современный недоступен
                else if (navigator.getUserMedia) {
                    this.stream = await new Promise((resolve, reject) => {
                        navigator.getUserMedia({ audio: true }, resolve, reject);
                    });
                } else {
                    throw new Error(this.$t('Voice.errors.micPermission'));
                }
                const preferedTypes = [
                    'audio/mp4',                     // iOS Safari
                    'audio/webm;codecs=opus',        // Chrome, Firefox
                    'audio/webm',
                ];
                const mimeType = preferedTypes.find(t => MediaRecorder.isTypeSupported(t));
                if (!mimeType) {
                    this.browserSupportsRecording = false;
                    return;
                }

                // Создаем MediaRecorder
                this.mediaRecorder = new MediaRecorder(this.stream, { mimeType });
                this.chosenMimeType = mimeType;
                this.audioChunks = [];

                // Обработчик события dataavailable
                this.mediaRecorder.ondataavailable = (event) => {
                    if (event.data.size > 0) {
                        this.audioChunks.push(event.data);
                    }
                };

                // Обработчик события stop
                this.mediaRecorder.onstop = async () => {
                    // Создаем Blob из записанных данных
                    const audioBlob = new Blob(this.audioChunks, { type: this.chosenMimeType });

                    // Сохраняем Blob в Vuex и устанавливаем состояние обработки
                    this.$store.commit('voice/RECORDING_COMPLETE', audioBlob);
                    this.$store.commit('voice/RECORDING_PROCESS'); // Показываем индикатор загрузки

                    // Загружаем запись на сервер
                    try {
                        const response = await this.voiceUploadRecording();
                        const message = response.message;

                        if(message === 'please_buy_premium'){
                            this.showError( this.$t('Voice.please_buy_premium'));
                        }

                        // Для демонстрации UI добавим несколько продуктов после обработки
                        if (response && response.transcription) {
                            this.transcription = response.transcription;
                        } else {
                            this.transcription = '';
                        }

                        // Добавляем продукты из ответа или демо-продукты
                        if (response && response.products && response.products.length > 0) {
                            this.products = response.products.map(item => {
                                return {
                                    name: item.product_translation?.name || "Неизвестный продукт",
                                    calories: item.product?.calories || 0,
                                    protein: item.product?.proteins || 0,
                                    fats: item.product?.fats || 0,
                                    carbs: item.product?.carbohydrates || 0,
                                    weight: item.quantity || 0,
                                    isGenerated: item.is_generated || false,
                                    product_id: item.product?.id || null,
                                    isModified: false,
                                    nameModified: false,
                                    needsSearch: false,
                                    originalName: item.product_translation?.name || "Неизвестный продукт",
                                    searchedBefore: false
                                };
                            });

                            // Сохраняем оригинальные значения для отслеживания изменений
                            this.saveOriginalValues();

                            // Выводим в консоль найденные продукты
                            console.log("Найденные продукты:", this.products);
                        } else {
                            // Добавляем демо-продукты если нужно
                            this.products = [];
                        }

                        // Завершаем обработку после получения и обработки данных
                        this.$store.commit('voice/RECORDING_COMPLETE', audioBlob);
                    } catch (error) {
                        console.error('Ошибка при отправке записи:', error);
                        this.showError('Не удалось отправить аудиозапись');
                        // Завершаем обработку при ошибке
                        this.$store.commit('voice/RECORDING_COMPLETE', audioBlob);
                    }
                };

                // Начинаем запись
                this.mediaRecorder.start();
                this.voiceStartRecording();
            } catch (error) {
                console.error('Ошибка при запуске записи:', error);
                let errorMessage = this.$t('Voice.errors.micPermission');

                if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                    errorMessage = this.$t('Voice.errors.micPermission');
                } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
                    errorMessage = this.$t('Voice.errors.micNotFound');
                } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
                    errorMessage = this.$t('Voice.errors.micInUse');
                } else if (error.name === 'OverconstrainedError' || error.name === 'ConstraintNotSatisfiedError') {
                    errorMessage = this.$t('Voice.errors.micSettings');
                } else if (error.name === 'TypeError') {
                    errorMessage = this.$t('Voice.errors.notSecure');
                } else if (error.message) {
                    errorMessage = error.message;
                }

                this.showError(errorMessage);
            }
        },
        async stopRecording() {
            if (this.mediaRecorder && this.isRecording) {
                // Останавливаем запись
                this.mediaRecorder.stop();
                this.voiceStopRecording();

                // Останавливаем все треки медиапотока
                if (this.stream) {
                    this.stream.getTracks().forEach(track => track.stop());
                }
            }
        },
        removeProduct(index) {
            this.products.splice(index, 1);
        },
        processProductData(index) {
            // Проверяем, есть ли у продукта имя
            if (!this.products[index].name || this.products[index].name.trim() === '') {
                this.showError(this.$t('Voice.errors.emptyProductName'));
                return;
            }

            const product = this.products[index];
            
            // Если имя продукта было изменено, убеждаемся, что обнулен product_id
            if (product.nameModified) {
                product.product_id = null;
            }

            // Устанавливаем состояние обработки
            this.$store.commit('voice/RECORDING_PROCESS');

            // Всегда генерируем данные для продукта
            this.generateProductData(index);
        },
        // Поиск продукта по названию
        async searchProductByName(index) {
            const productName = this.products[index].name;

            try {
                const response = await searchProduct(productName);

                if (response.success && response.product) {
                    // Продукт найден в базе данных
                    const productData = response.product;

                    // Обновляем данные продукта
                    this.products[index].product_id = productData.product.id;
                    this.products[index].calories = productData.product.calories || 0;
                    this.products[index].protein = productData.product.proteins || 0;
                    this.products[index].fats = productData.product.fats || 0;
                    this.products[index].carbs = productData.product.carbohydrates || 0;

                    // Обновляем название продукта найденным в базе данных
                    this.products[index].name = productData.product_translation.name;
                    this.products[index].originalName = productData.product_translation.name;

                    // Сбрасываем флаги
                    this.products[index].needsSearch = false;
                    this.products[index].nameModified = false;

                    // Устанавливаем флаг, что поиск был выполнен
                    this.products[index].searchedBefore = true;

                    // Если рейтинг низкий, предлагаем сгенерировать данные
                    if (response.should_generate) {
                        this.showSuccess(this.$t('Voice.success.productFoundLowMatch', { product: productData.product_translation.name }));
                    } else {
                        this.showSuccess(this.$t('Voice.success.productFound', { product: productData.product_translation.name }));
                    }
                } else {
                    // Продукт не найден, предлагаем сгенерировать данные
                    this.products[index].needsSearch = false; // Поиск выполнен, но безуспешно

                    // Устанавливаем флаг, что поиск был выполнен
                    this.products[index].searchedBefore = true;

                    // Генерируем данные автоматически
                    await this.generateProductData(index);
                }
            } catch (error) {
                this.showError(error.message || this.$t('Voice.errors.searchError'));
                console.error('Ошибка при поиске продукта:', error);
            } finally {
                this.$store.commit('voice/RECORDING_COMPLETE', this.audioBlob);
            }
        },
        // Генерация данных о продукте
        async generateProductData(index) {
            const productName = this.products[index].name;

            try {
                // Показываем индикатор загрузки
                this.$store.commit('voice/RECORDING_PROCESS');
                
                const response = await generateProductData(productName);

                // Проверяем успешность запроса
                if (response.success && response.data) {
                    // Обновляем данные продукта
                    this.products[index].calories = response.data.calories || 0;
                    this.products[index].protein = response.data.proteins || 0;
                    this.products[index].fats = response.data.fats || 0;
                    this.products[index].carbs = response.data.carbohydrates || 0;
                    this.products[index].isGenerated = true;

                    // Если имя продукта было изменено, убеждаемся что это новый продукт
                    if (this.products[index].nameModified) {
                        this.products[index].product_id = null;
                    }

                    // Сбрасываем флаг поиска
                    this.products[index].needsSearch = false;

                    // Показываем сообщение об успехе
                    this.showSuccess(this.$t('Voice.success.dataGenerated', { product: productName }));
                } else {
                    // Если запрос неуспешен
                    throw new Error(response.message || this.$t('Voice.errors.generateError'));
                }
            } catch (error) {
                // Обработка ошибок
                this.showError(error.message || this.$t('Voice.errors.generateError'));
                console.error('Ошибка при генерации данных:', error);
            } finally {
                // В любом случае завершаем обработку
                this.$store.commit('voice/RECORDING_COMPLETE', this.audioBlob);
            }
        },
        saveToMeal(mealType, mealLabel) {
            // Преобразование русских названий частей дня в английские
            const mealTypeMap = {
                'завтрак': 'morning',
                'обед': 'dinner',
                'ужин': 'supper'
            };

            // Получаем английское название части дня (если это русское слово)
            const englishMealType = mealTypeMap[mealType] || mealType;

            // Проверка на наличие продуктов
            if (!this.products.length) {
                this.showError(this.$t('Voice.errors.noProducts'));
                return;
            }

            // Проверяем и подготавливаем продукты перед отправкой
            const productsToSave = this.products.map(product => {
                // Создаем копию продукта, чтобы не изменять оригинал
                const preparedProduct = { ...product };

                // Если название продукта было изменено, убеждаемся что это новый продукт
                if (preparedProduct.nameModified) {
                    preparedProduct.product_id = null; // Гарантированно обнуляем ID
                    preparedProduct.isGenerated = false; // Устанавливаем как пользовательский продукт
                }
                
                // Проверяем, есть ли у продукта вес
                if (!preparedProduct.weight || preparedProduct.weight <= 0) {
                    preparedProduct.weight = 100; // Устанавливаем вес по умолчанию
                }

                return preparedProduct;
            });

            // Устанавливаем состояние обработки
            this.$store.commit('voice/RECORDING_PROCESS');

            // Отправляем продукты на сохранение
            saveVoiceProducts(productsToSave, englishMealType)
                .then(response => {
                    // Обновляем состояние после успешного сохранения
                    this.$store.commit('voice/RECORDING_COMPLETE', this.audioBlob);

                    // Показываем сообщение об успехе с названием приема пищи
                    this.showSuccess(this.$t('Voice.success.productsSaved', { meal: mealLabel || mealType }));

                    // Очищаем список продуктов и расшифровку
                    this.products = [];
                    this.transcription = '';
                    this.voiceResetRecording();
                })
                .catch(error => {
                    // Обработка ошибок
                    this.$store.commit('voice/RECORDING_COMPLETE', this.audioBlob);
                    this.showError(error.response?.data?.message || this.$t('Voice.errors.saveError'));
                    console.error('Ошибка при сохранении продуктов:', error);
                });
        },
        cancel() {
            this.showSuccess(this.$t('Voice.success.productsDeleted'));
            this.products = [];
            this.transcription = '';
            this.voiceResetRecording();
        },
        // Проверка поддержки браузером необходимых API
        checkBrowserSupport() {
            // Проверяем поддержку современных API
            const hasNavigator = typeof navigator !== 'undefined';
            const hasMediaDevices = hasNavigator && navigator.mediaDevices;
            const hasGetUserMedia = hasMediaDevices && typeof navigator.mediaDevices.getUserMedia === 'function';
            const hasMediaRecorder = typeof window !== 'undefined' && typeof window.MediaRecorder !== 'undefined';

            this.browserSupportsRecording = hasGetUserMedia && hasMediaRecorder;

            // Для более старых браузеров можно попробовать найти альтернативные API
            if (!this.browserSupportsRecording && hasNavigator) {
                // Проверяем устаревшие методы для доступа к медиа-устройствам
                navigator.getUserMedia = navigator.getUserMedia ||
                                       navigator.webkitGetUserMedia ||
                                       navigator.mozGetUserMedia ||
                                       navigator.msGetUserMedia;

                if (navigator.getUserMedia && typeof window.MediaRecorder !== 'undefined') {
                    this.browserSupportsRecording = true;
                }
            }

            console.log('Browser supports recording:', this.browserSupportsRecording);
        },
        saveOriginalValues() {
            // Сохраняем оригинальные значения продуктов по product_id
            this.originalProducts = {};

            this.products.forEach(item => {
                if (item.product_id) {
                    this.originalProducts[item.product_id] = {
                        name: item.name,
                        calories: item.calories,
                        protein: item.protein,
                        fats: item.fats,
                        carbs: item.carbs,
                        weight: item.weight
                    };
                }
            });

            console.log('Сохранены оригинальные значения продуктов:', this.originalProducts);
        },
        checkModifiedProducts() {
            // Проходим по всем продуктам и проверяем изменения
            this.products.forEach((product, index) => {
                // Проверяем, есть ли у продукта ID (существующий продукт)
                if (product.product_id) {
                    const original = this.originalProducts[product.product_id];

                    // Проверяем, изменились ли питательные вещества
                    if (original && (
                        product.calories !== original.calories ||
                        product.protein !== original.protein ||
                        product.fats !== original.fats ||
                        product.carbs !== original.carbs
                    )) {
                        // Устанавливаем флаг "модифицирован"
                        this.products[index].isModified = true;
                        console.log(`Продукт "${product.name}" был модифицирован:`, {
                            original: {
                                calories: original.calories,
                                protein: original.protein,
                                fats: original.fats,
                                carbs: original.carbs
                            },
                            modified: {
                                calories: product.calories,
                                protein: product.protein,
                                fats: product.fats,
                                carbs: product.carbs
                            }
                        });
                    }
                }
            });
        },
        onProductChanged(index) {
            // Проверяем, есть ли у продукта ID (существующий продукт)
            if (this.products[index].product_id) {
                const original = this.originalProducts[this.products[index].product_id];

                // Проверяем, изменились ли питательные вещества
                if (original && (
                    this.products[index].calories !== original.calories ||
                    this.products[index].protein !== original.protein ||
                    this.products[index].fats !== original.fats ||
                    this.products[index].carbs !== original.carbs ||
                    this.products[index].weight !== original.weight
                )) {
                    // Устанавливаем флаг "модифицирован"
                    this.products[index].isModified = true;
                    console.log(`Продукт "${this.products[index].name}" был модифицирован:`, {
                        original: {
                            calories: original.calories,
                            protein: original.protein,
                            fats: original.fats,
                            carbs: original.carbs,
                            weight: original.weight
                        },
                        modified: {
                            calories: this.products[index].calories,
                            protein: this.products[index].protein,
                            fats: this.products[index].fats,
                            carbs: this.products[index].carbs,
                            weight: this.products[index].weight
                        }
                    });
                }
            } else if (this.products[index].nameModified) {
                // Если это продукт с измененным названием, отмечаем его как isModified
                this.products[index].isModified = true;
            }
        },

        onProductNameChanged(index) {
            const product = this.products[index];

            // Если имя не изменилось, ничего не делаем
            if (product.originalName === product.name) {
                product.nameModified = false;
                product.needsSearch = false;
                return;
            }

            // Создаем новый пользовательский продукт:
            // 1. Устанавливаем флаг изменения имени
            product.nameModified = true;
            // 2. Обнуляем product_id, чтобы продукт был сохранен как новый
            product.product_id = null;
            // 3. Устанавливаем флаг isGenerated в false чтобы система знала, что это пользовательский продукт
            product.isGenerated = false;
            // 4. Устанавливаем флаг isModified в true чтобы продукт визуально выделялся
            product.isModified = true;
            
            // Установливаем needsSearch в false, чтобы при нажатии generate всегда использовалась генерация
            product.needsSearch = false;
            console.log(`Изменение имени продукта с "${product.originalName}" на "${product.name}". Создан новый пользовательский продукт.`);
            
            // Обновляем originalName, чтобы отслеживать дальнейшие изменения
            product.originalName = product.name;
        },
    },
    beforeUnmount() {
        // Освобождаем ресурсы при удалении компонента
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
        }
    }
}
</script>
<template>
    <div class="voice-page">
        <div class="voice-container">
            <h1>{{ $t('Voice.title') }}</h1>

            <!-- Сообщение об успешном действии -->
            <CaloriesSuccessNotification v-if="showSuccessMessage">
                {{ successMessage }}
            </CaloriesSuccessNotification>

            <!-- Сообщение об ошибке -->
            <CaloriesErrorNotification v-if="showErrorMessage">
                {{ errorMessage }}
            </CaloriesErrorNotification>

            <div class="voice-description">
                <p>{{ $t('Voice.description') }}</p>
            </div>

            <div class="voice-controls">
                <button
                    v-if="browserSupportsRecording"
                    @click="toggleRecording"
                    :class="['record-button', { 'recording': isRecording, 'processing': isProcessing }]"
                    :disabled="isProcessing"
                >
                    <FontAwesomeIcon
                        v-if="!isProcessing && !isRecording"
                        :icon="faMicrophone()"
                        class="mic-icon"
                    />
                    <FontAwesomeIcon
                        v-if="!isProcessing && isRecording"
                        :icon="faMicrophoneSlash()"
                        class="mic-icon"
                    />
                    <FontAwesomeIcon
                        v-if="isProcessing"
                        :icon="faSpinner()"
                        class="mic-icon fa-spin"
                    />
                    <span v-if="!isRecording && !isProcessing">{{ $t('Voice.startRecording') }}</span>
                    <span v-if="isRecording && !isProcessing">{{ $t('Voice.stopRecording') }}</span>
                    <span v-if="isProcessing">{{ $t('Voice.processing') }}</span>
                </button>

                <div v-if="!browserSupportsRecording" class="browser-warning">
                    <p>
                        <FontAwesomeIcon :icon="faExclamationTriangle()" class="warning-icon" />
                        {{ $t('Voice.browserWarning') }}
                    </p>
                </div>
            </div>

            <div class="transcription-container" v-if="transcription">
                <h2>{{ $t('Voice.transcriptionTitle') }}</h2>
                <div class="transcription-box">
                    {{ transcription }}
                </div>
            </div>

            <div class="products-container" v-if="products.length > 0">
                <h2>{{ $t('Voice.productsList') }}</h2>

                <div class="products-list">
                    <div class="product-item" v-for="(product, index) in products" :key="index" :class="{'modified-product': product.isModified}">
                        <div class="product-header">
                            <div class="product-name">
                                <input type="text" v-model="product.name" :placeholder="$t('Voice.productName')" @change="onProductNameChanged(index)" />
                                <span v-if="product.isModified" class="modified-tag">{{ $t('Voice.modifiedTag') }}</span>
                                <span v-if="product.nameModified" class="name-modified-tag">{{ $t('Voice.nameModifiedTag') }}</span>
                            </div>
                            <div class="product-actions">
                                <button class="generate-btn" @click="processProductData(index)" :title="$t('Voice.generateButton')">
                                    <FontAwesomeIcon :icon="faMagic()" />
                                    <span>{{ $t('Voice.generateButton') }}</span>
                                </button>
                                <button class="remove-btn" @click="removeProduct(index)" :title="$t('Voice.deleteProduct')">
                                    <FontAwesomeIcon :icon="faTrash()" />
                                </button>
                            </div>
                        </div>

                        <div class="product-details">
                            <div class="nutrition-item">
                                <label>{{ $t('Voice.weight') }}</label>
                                <input type="number" v-model="product.weight" min="0" @change="onProductChanged(index)" />
                            </div>
                            <div class="nutrition-item">
                                <label>{{ $t('Voice.calories') }}</label>
                                <input type="number" v-model="product.calories" min="0" @change="onProductChanged(index)" />
                            </div>
                            <div class="nutrition-item">
                                <label>{{ $t('Voice.protein') }}</label>
                                <input type="number" v-model="product.protein" min="0" step="0.1" @change="onProductChanged(index)" />
                            </div>
                            <div class="nutrition-item">
                                <label>{{ $t('Voice.fats') }}</label>
                                <input type="number" v-model="product.fats" min="0" step="0.1" @change="onProductChanged(index)" />
                            </div>
                            <div class="nutrition-item">
                                <label>{{ $t('Voice.carbs') }}</label>
                                <input type="number" v-model="product.carbs" min="0" step="0.1" @change="onProductChanged(index)" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="save-actions">
                    <button class="save-btn breakfast" @click="saveToMeal('завтрак', $t('Diary.morning'))">
                        <FontAwesomeIcon :icon="faSave()" />
                        <span>{{ $t('Voice.saveBreakfast') }}</span>
                    </button>
                    <button class="save-btn lunch" @click="saveToMeal('обед', $t('Diary.dinner'))">
                        <FontAwesomeIcon :icon="faSave()" />
                        <span>{{ $t('Voice.saveLunch') }}</span>
                    </button>
                    <button class="save-btn dinner" @click="saveToMeal('ужин', $t('Diary.supper'))">
                        <FontAwesomeIcon :icon="faSave()" />
                        <span>{{ $t('Voice.saveDinner') }}</span>
                    </button>
                    <button class="cancel-btn" @click="cancel">
                        <span>{{ $t('Voice.cancel') }}</span>
                    </button>
                </div>
            </div>

            <div class="voice-tips" v-if="!products.length">
                <h3>{{ $t('Voice.tips.title') }}</h3>
                <ul>
                    <li>{{ $t('Voice.tips.tip1') }}</li>
                    <li>{{ $t('Voice.tips.tip2') }}</li>
                    <li>{{ $t('Voice.tips.tip3') }}</li>
                    <li>{{ $t('Voice.tips.tip4') }}</li>
                </ul>
            </div>
        </div>
    </div>
</template>
<style scoped lang="scss">
.voice-page {
    padding: 20px;

    .voice-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px;
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        position: relative;

        .success-message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            animation: fadeInOut 3s forwards;

            .success-message-content {
                display: flex;
                align-items: center;
                padding: 12px 24px;
                background-color: #4CAF50;
                color: white;
                border-radius: 30px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);

                .success-icon {
                    margin-right: 10px;
                    font-size: 1.2rem;
                }

                span {
                    font-weight: 500;
                    font-size: 1.1rem;
                }
            }
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
            font-size: 2rem;
        }

        .voice-description {
            text-align: center;
            margin-bottom: 40px;

            p {
                color: #666;
                line-height: 1.6;
                font-size: 1.1rem;
            }
        }

        .voice-controls {
            display: flex;
            justify-content: center;
            margin: 30px 0;

            .record-button {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 16px 28px;
                background-color: #4CAF50;
                color: white;
                border: none;
                border-radius: 50px;
                font-size: 1.2rem;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);

                &:hover {
                    background-color: #43A047;
                    transform: translateY(-2px);
                    box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
                }

                &:active {
                    transform: translateY(1px);
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                }

                &.recording {
                    background-color: #f44336;
                    animation: pulse 1.5s infinite;

                    &:hover {
                        background-color: #e53935;
                    }
                }

                &.processing {
                    background-color: #FFB74D;
                    cursor: not-allowed;

                    &:hover {
                        background-color: #FFB74D;
                        transform: none;
                    }
                }

                .mic-icon {
                    margin-right: 10px;
                    font-size: 1.4rem;
                }

                span {
                    font-weight: 600;
                }
            }

            .browser-warning {
                max-width: 500px;
                padding: 15px 20px;
                background-color: #FFF3E0;
                border: 1px solid #FFB74D;
                border-radius: 8px;

                p {
                    display: flex;
                    align-items: center;
                    color: #E65100;
                    font-size: 1rem;
                    line-height: 1.5;
                    margin: 0;
                }

                .warning-icon {
                    color: #FF9800;
                    font-size: 1.6rem;
                    margin-right: 15px;
                    flex-shrink: 0;
                }
            }
        }

        .transcription-container {
            margin: 20px 0;

            h2 {
                color: #444;
                margin-bottom: 15px;
                font-size: 1.5rem;
            }

            .transcription-box {
                background-color: #f9f9f9;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                padding: 15px;
                font-size: 1.1rem;
                line-height: 1.6;
                color: #333;
                font-style: italic;
            }
        }

        .products-container {
            margin: 30px 0;

            h2 {
                color: #444;
                margin-bottom: 20px;
                font-size: 1.5rem;
            }

            .products-list {
                .product-item {
                    background-color: #f9f9f9;
                    border-radius: 10px;
                    padding: 15px;
                    margin-bottom: 20px;
                    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                    transition: all 0.3s ease;

                    &:hover {
                        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
                    }

                    &.modified-product {
                        background-color: #fff8e1;
                        border-left: 4px solid #FFC107;

                        .modified-tag {
                            display: inline-block;
                            background-color: #FFC107;
                            color: #333;
                            font-size: 0.8rem;
                            padding: 2px 8px;
                            border-radius: 12px;
                            margin-left: 10px;
                            font-weight: 500;
                        }
                    }

                    .name-modified-tag {
                        display: inline-block;
                        background-color: #2196F3;
                        color: white;
                        font-size: 0.8rem;
                        padding: 2px 8px;
                        border-radius: 12px;
                        margin-left: 10px;
                        font-weight: 500;
                    }

                    .product-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 15px;

                        .product-name {
                            flex: 1;

                            input {
                                width: 100%;
                                padding: 10px 15px;
                                font-size: 1.1rem;
                                border: 1px solid #ddd;
                                border-radius: 25px;
                                background-color: #fff;

                                &:focus {
                                    outline: none;
                                    border-color: #4CAF50;
                                    box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
                                }
                            }
                        }

                        .product-actions {
                            display: flex;
                            gap: 10px;

                            .generate-btn {
                                display: flex;
                                align-items: center;
                                padding: 8px 15px;
                                background-color: #2196F3;
                                color: white;
                                border: none;
                                border-radius: 25px;
                                cursor: pointer;
                                transition: all 0.2s ease;

                                &:hover {
                                    background-color: #1E88E5;
                                }

                                svg {
                                    margin-right: 8px;
                                }
                            }

                            .remove-btn {
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                width: 36px;
                                height: 36px;
                                background-color: #f44336;
                                color: white;
                                border: none;
                                border-radius: 50%;
                                cursor: pointer;
                                transition: all 0.2s ease;

                                &:hover {
                                    background-color: #e53935;
                                }
                            }
                        }
                    }

                    .product-details {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 15px;

                        .nutrition-item {
                            flex: 1;
                            min-width: 120px;

                            label {
                                display: block;
                                margin-bottom: 5px;
                                color: #666;
                                font-size: 0.9rem;
                            }

                            input {
                                width: 100%;
                                padding: 8px 12px;
                                border: 1px solid #ddd;
                                border-radius: 25px;
                                font-size: 1rem;

                                &:focus {
                                    outline: none;
                                    border-color: #4CAF50;
                                    box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
                                }
                            }
                        }
                    }
                }
            }

            .save-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                margin-top: 30px;

                button {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 12px 20px;
                    border: none;
                    border-radius: 25px;
                    font-size: 1rem;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s ease;

                    svg {
                        margin-right: 8px;
                    }

                    &.save-btn {
                        color: white;
                        flex: 1;
                        min-width: 200px;

                        &.breakfast {
                            background-color: #FF9800;

                            &:hover {
                                background-color: #F57C00;
                            }
                        }

                        &.lunch {
                            background-color: #4CAF50;

                            &:hover {
                                background-color: #43A047;
                            }
                        }

                        &.dinner {
                            background-color: #3F51B5;

                            &:hover {
                                background-color: #3949AB;
                            }
                        }
                    }

                    &.cancel-btn {
                        background-color: #f5f5f5;
                        color: #666;

                        &:hover {
                            background-color: #e0e0e0;
                        }
                    }
                }
            }
        }

        .voice-tips {
            margin-top: 40px;
            background-color: #f5f5f5;
            border-radius: 8px;
            padding: 20px;

            h3 {
                color: #555;
                margin-bottom: 15px;
                font-size: 1.3rem;
            }

            ul {
                padding-left: 20px;

                li {
                    color: #666;
                    margin-bottom: 8px;
                    line-height: 1.5;
                }
            }
        }
    }
}

@keyframes pulse {
    0% {
        opacity: 1;
    }
    50% {
        opacity: 0.8;
    }
    100% {
        opacity: 1;
    }
}

@keyframes fadeInOut {
    0% {
        opacity: 0;
        transform: translate(-50%, -20px);
    }
    15% {
        opacity: 1;
        transform: translate(-50%, 0);
    }
    85% {
        opacity: 1;
        transform: translate(-50%, 0);
    }
    100% {
        opacity: 0;
        transform: translate(-50%, -20px);
    }
}

// Адаптивность для мобильных устройств
@media (max-width: 768px) {
    .voice-page {
        padding: 10px;

        .voice-container {
            padding: 20px;

            h1 {
                font-size: 1.6rem;
            }

            .voice-description p {
                font-size: 1rem;
            }

            .voice-controls .record-button {
                padding: 14px 24px;
                font-size: 1rem;

                .mic-icon {
                    font-size: 1.2rem;
                }
            }

            .products-container {
                h2 {
                    font-size: 1.3rem;
                }

                .products-list {
                    .product-item {
                        .product-header {
                            flex-direction: column;
                            align-items: flex-start;

                            .product-name {
                                width: 100%;
                                margin-bottom: 10px;
                            }

                            .product-actions {
                                width: 100%;
                                justify-content: space-between;
                            }
                        }

                        .product-details {
                            .nutrition-item {
                                min-width: 100%;
                            }
                        }
                    }
                }

                .save-actions {
                    flex-direction: column;

                    button {
                        width: 100%;
                    }
                }
            }

            .transcription-container h2 {
                font-size: 1.3rem;
            }

            .voice-tips h3 {
                font-size: 1.2rem;
            }
        }
    }
}
</style>
