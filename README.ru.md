# Calories365: Счётчик калорий с Telegram-ботом и ИИ
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

## 1. Суть проекта

Calories365 — моё полнофункциональное веб-приложение и Telegram-бот (в отдельном приложении-админке для Telegram-ботов) для учёта калорий. Проект родился из личной потребности: хотелось фиксировать еду исключительно голосом, а существующие решения этого не предлагали.

## [Попробуйте Дневник Калорий прямо сейчас!](https://calculator.calories365.online)

### Голосовой ввод и само заполняемая БД

- Доступен двумя способами:  
  **а)** голосовое сообщение в Telegram-боте;  
  **б)** страница «Голосовой ввод» на сайте.
- После расшифровки продукт ищется в базе. При низком совпадении ИИ генерирует новую запись, сохраняемую в общую (самопополняемую) базу только если она валидна.
- Если пользователь не назвал массу, подставляется среднестатистическое значение.
- Логика работы: микрофон → Whisper → GPT-4o → база данных → интерфейс.

### Калькулятор калорий
Быстрый расчёт дневной потребности и отслеживание факта/нормы в интерфейсе приложения.

### Календарь дневных итогов
Календарная сетка показывает, сколько килокалорий съедено каждый день и были ли превышения или дефицит.

<p align="center">
  <img src="./public/cal.gif" width="500" alt="Demo GIF">
</p>


## 2. Архитектурные особенности

### 2.1 Backend-архитектура для Telegram-ботов

Telegram-бот работает в отдельном приложении («Bot Panel»), выступает в роли админ-панели, и все запросы Telegram обрабатываются через очереди Horizon. Приложение предоставляет:

* управление и конфигурирование Telegram-ботов;
* мониторинг активности пользователей и ботов;
* взаимодействие с логикой обработки сообщений через веб-интерфейс.

Основные компоненты:

* **Контроллер** принимает запросы от Telegram и передаёт их в `TelegramHandler`;
* **TelegramHandler** выбирает стратегию, применяет middleware (Laravel Pipeline) и вызывает нужные обработчики;
* **BaseService** определяет фабрики обработчиков для каждого типа обновления;
* **Стратегии** (например, `CaloriesService`) наследуют `BaseService` и добавляют/переопределяют обрабочтики:
    * `MessageUpdateHandler` (текст, голос, изображения);
    * `CallbackQueryHandler` (inline-кнопки).

Подробнее: см. [README по сервисному слою ботов](./README.BotPanelArchitecture.ru.md).

### 2.2 Динамические формы и таблицы на Vue

Во фронтенде Bot Panel используются универсальные компоненты:

* формы описываются конфигурационными объектами (тип поля, плейсхолдер, обязательность и т. д.);
* таблицы так же конфигурируются объектами (заголовок, тип ячейки, лимит символов и прочее).

Это позволяет добавлять/убирать поля и столбцы без изменения кода компонентов.

Подробнее: см. [README по динамическим формам/таблицам](./README.DynamicFormsAndTables.ru.md)

---

## 3. Среда разработки

- **Docker-окружение:** одна команда поднимает весь стек включая Ngrok для удалённого доступа.
- **Автоустановка вебхука:** при старте контейнера вебхук Telegram-бота устанавливается автоматически.

Подробнее: см. [README по Docker-конфигам](https://github.com/Calories365/Configs/blob/main/README.ru.md)

---

## 4. CI/CD и деплой

* Деплой выполняется автоматически на self-hosted сервере с интеграцией Cloudflare (DNS, SSL, Zero Trust Tunnel).
* Используется Docker и docker-compose: каждый проект (Calories365 и Bot Panel) имеет набор контейнеров (PHP, Nginx, Redis, MySQL, Meilisearch и др.), взаимодействующих через общую внутреннюю Docker-сеть.
* Доступ по SSH так же осуществляется через Cloudflare Zero Trust Tunnel с авторизацией по приватным ключам и OAuth.

Подробнее: см. [README по Docker-конфигам](https://github.com/Calories365/Configs/blob/main/README.ru.md)

---

## 5. Дополнительные инструменты

* Оплата через **WayForPay**.
* Аутентификация по **OAuth** (Google).
* Качество кода контролируется **Larastan**, **Laravel Pint** и форматером JS.
