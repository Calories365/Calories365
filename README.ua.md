# Calories365: Лічильник калорій з Telegram-ботом та ІІ
![Laravel](https://img.shields.io/badge/laravel-black?logo=laravel)
![MySQL](https://img.shields.io/badge/MySQL-black?logo=MySQL)
![Redis](https://img.shields.io/badge/Redis-black?logo=Redis)
![Meilisearch](https://img.shields.io/badge/Meilisearch-black?logo=Meilisearch)
![Telegram SDK](https://img.shields.io/badge/Telegram%20SDK-black?logo=Telegram)
![FFmpeg](https://img.shields.io/badge/FFmpeg-black?logo=FFmpeg)
![Vue.js](https://img.shields.io/badge/Vue.js-black?logo=Vue.js)
![OAuth](https://img.shields.io/badge/OAuth-black?logo=Google)
![Docker](https://img.shields.io/badge/Docker-black?logo=Docker)
![Cloudflare](https://img.shields.io/badge/Cloudflare-black?logo=Cloudflare)
![Larastan](https://img.shields.io/badge/Larastan-black?logo=laravel)
![Laravel Pint](https://img.shields.io/badge/Laravel%20Pint-black?logo=laravel)
![Prettier](https://img.shields.io/badge/Prettier-black?logo=prettier)
---

## 1. Суть проєкту

Calories365 — мій повнофункціональний веб-застосунок і Telegram-бот (в окремій адмінці для Telegram-ботів) для обліку калорій. Проєкт народився з особистої потреби: хотілося фіксувати їжу виключно голосом, а наявні рішення цього не пропонували.

## [Спробуйте Щоденник Калорій зараз!](https://calculator.calories365.com)

### Голосове введення та самопоповнювана БД

- Доступний двома способами:  
  **а)** голосове повідомлення в Telegram-боті;  
  **б)** сторінка «Голосове введення» на сайті.
- Після розшифровки продукт шукається в базі. За низької схожості ШІ генерує новий запис, який зберігається в спільну базу лише за умови, що він валідний.
- Якщо користувач не назвав вагу продукта, підставляється середньостатистичне значення.
- Логіка роботи: мікрофон → Whisper → GPT-4o → база даних → інтерфейс.

### Калькулятор калорій
Швидкий розрахунок денної потреби та відстеження факту/норми в інтерфейсі застосунку.

### Календар щоденних підсумків
Календарна сітка показує, скільки кілокалорій з’їдено щодня та чи були перевищення або дефіцит.

<p align="center">
  <img src="./public/cal.gif" width="500" alt="Демо GIF">
</p>

## 2. Архітектурні особливості

### 2.1 Backend-архітектура для Telegram-ботів

Telegram-бот працює в окремому застосунку («Bot Panel»), виступає в ролі адмін-панелі, надаючи такі можливості:

* керування та конфігурування Telegram-ботів;
* моніторинг активності користувачів і ботів;
* взаємодія з логікою обробки повідомлень через веб-інтерфейс.

Основні компоненти:

* **Контролер** приймає запити від Telegram і передає їх у `TelegramHandler`;
* **TelegramHandler** обирає стратегію, застосовує middleware (Laravel Pipeline) і викликає потрібні обробники;
* **BaseService** визначає фабрики обробників для кожного типу оновлення;
* **Стратегії** (наприклад, `CaloriesService`) наслідують `BaseService` та додають/перезаписують обробники:
    * `MessageUpdateHandler` (текст, голос, зображення);
    * `CallbackQueryHandler` (inline-кнопки).

Детальніше: див. [README про сервісний шар ботів](./README.BotPanelArchitecture.ua.md).

### 2.2 Динамічні форми та таблиці на Vue

У фронтенді Bot Panel використовуються універсальні компоненти:

* форми описуються конфігураційними об’єктами (тип поля, плейсхолдер, обов’язковість тощо);
* таблиці так само конфігуруються об’єктами (заголовок, тип комірки, ліміт символів тощо).

Це дозволяє додавати/видаляти поля та стовпці без зміни коду компонентів.

Детальніше: див. [README про динамічні форми/таблиці](./README.DynamicFormsAndTables.ua.md)

---

## 3. Середовище розробки

- **Docker-оточення:** одна команда підіймає весь стек, включно з Ngrok для віддаленого доступу.
- **Автоустановка вебхука:** під час запуску контейнера вебхук Telegram-бота встановлюється автоматично.

Детальніше: див. [README по Docker конфігураціях](https://github.com/Calories365/Configs/blob/main/README.uk.md)

---

## 4. CI/CD та деплой

* Деплой виконується автоматично на self-hosted сервері з інтеграцією Cloudflare (DNS, SSL, Zero Trust Tunnel).
* Використовуються Docker і docker-compose: кожен проєкт (Calories365 і Bot Panel) має набір контейнерів (PHP, Nginx, Redis, MySQL, Meilisearch та ін.), що взаємодіють через спільну внутрішню Docker-мережу.
* Доступ по SSH також здійснюється через Cloudflare Zero Trust Tunnel з авторизацією за приватними ключами та OAuth.

Детальніше: див. [README по Docker конфігураціях](https://github.com/Calories365/Configs/blob/main/README.uk.md)

---

## 5. Додаткові інструменти

* Оплата через **WayForPay**.
* Автентифікація через **OAuth** (Google).
* Якість коду контролюється **Larastan**, **Laravel Pint** та форматером JS.
