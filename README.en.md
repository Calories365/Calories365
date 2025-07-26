# Calories365: Calorie Tracker with Telegram Bot & AI
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

## 1. Project Overview

Calories365 is my fully-featured web application and Telegram bot (managed in a separate Bot Panel admin app) for calorie tracking.  
The idea came from a personal need: logging food *only* by voice, something existing solutions didn’t offer.

## [Try the Calorie Diary now!](https://calculator.calories365.com)

### Voice Input & Self-Filling Database

- Available in two ways:  
  **a)** a voice message to the Telegram bot;  
  **b)** the “Voice Input” page on the website.
- After transcription, the product is looked up in the database. If similarity is low, the AI generates a new entry, saved to the shared DB only if valid.
- If the user omits weight, an average value is substituted.
- Flow: microphone → Whisper → GPT-4o → database → UI.

### Calorie Calculator
Instant daily requirement calculation plus fact/target tracking in the interface.

### Daily Summary Calendar
A calendar grid shows daily kcal intake and whether you exceeded or under-shot your target.

<p align="center">
  <img src="./public/cal.gif" width="500" alt="Demo GIF">
</p>

## 2. Architecture Highlights

### 2.1 Backend Architecture for Telegram Bots

The Telegram bot lives in a separate app (“Bot Panel”), acting as an admin panel and providing:

* management and configuration of Telegram bots;
* monitoring of user and bot activity;
* interaction with message-processing logic via a web interface.

Core components:

* **Controller** receives Telegram updates and passes them to `TelegramHandler`;
* **TelegramHandler** selects a strategy, applies middleware (Laravel Pipeline) and triggers the required handlers;
* **BaseService** defines handler factories for every update type;
* **Strategies** (e.g., `CaloriesService`) extend `BaseService` and add/override handlers:
    * `MessageUpdateHandler` (text, voice, images);
    * `CallbackQueryHandler` (inline buttons).

Details: see [README for the bot service layer](./README.BotPanelArchitecture.en.md).

### 2.2 Dynamic Forms & Tables in Vue

The Bot Panel frontend uses universal components:

* forms are described by config objects (field type, placeholder, required, etc.);
* tables are likewise configured (header, cell type, char limit, etc.).

This lets you add/remove fields and columns without touching component code.

Details: see [README on dynamic forms/tables](./README.DynamicFormsAndTables.en.md)

---

## 3. Development Environment

- **Docker setup:** one command spins up the entire stack, including Ngrok for remote access.
- **Auto webhook setup:** the Telegram bot webhook is set automatically on container start.

Details: see [README on Docker configs](https://github.com/Calories365/Configs/blob/main/README.en.md)

---

## 4. CI/CD & Deploy

* Deploy runs automatically to a self-hosted server with Cloudflare integration (DNS, SSL, Zero Trust Tunnel).
* Docker & docker-compose: each project (Calories365 and Bot Panel) has a set of containers (PHP, Nginx, Redis, MySQL, Meilisearch, etc.) communicating over a shared internal Docker network.
* SSH access also goes through Cloudflare Zero Trust Tunnel with private-key + OAuth auth.

Details: see [README on Docker configs](https://github.com/Calories365/Configs/blob/main/README.en.md)

---

## 5. Additional Tools

* Payments via **WayForPay**.
* Authentication through **OAuth** (Google).
* Code quality enforced by **Larastan**, **Laravel Pint** and the JS formatter **Prettier**.
