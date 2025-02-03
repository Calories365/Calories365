# Calories365: Calorie Counter with Telegram Bot and AI

**Calories365** is a web application and Telegram bot that allow you to conveniently keep a food diary, automatically process voice messages, and obtain calorie statistics (using AI).

## [Try the Calorie Diary Now!](https://calculator.calories365.com)

---

## 1. Project Essence

> **Record your food and see how many calories you consume.**

- **Calorie Diary**  
  In our web application, you can log everything you ate during the day. Find a product in the extensive database or add your own option — any individual dishes and ingredients will be accounted for.

- **Visual Statistics**  
  Track the dynamics of calorie consumption over different periods. You'll easily understand if you regularly exceed the norm or, conversely, stay within the recommended indicators.

- **Telegram Bot**  
  Connect the bot through your personal account and enter data **by voice**:
    1. Name the product — the bot will convert your voice to text and generate a list of dishes.
    2. If you did not specify the weight, the bot will offer average values or generate calorie, protein, fat, and carbohydrate (CPFC) data using artificial intelligence.
    3. If necessary, make adjustments and save the entry to your diary with one click.

- **Time Savings**  
  No need to open a website or install an additional application — Telegram is enough. Daily statistics and quick data entry are always at hand.

Thus, if your goal is to **quickly** and **easily** count calories, "Calories365" together with our bot will be an excellent solution.

---

## 2. Technologies Used

- **Laravel** (with connected packages **Laravel Sanctum** and **Laravel Fortify** for authentication and security)
- **MySQL** — DBMS for data storage (users, products, diary).
- **Redis** — caching and session storage.
- **Meilisearch** — product database search.
- **Telegram SDK** — integration with Telegram.
- **FFmpeg** — audio/video conversion (especially when processing voice messages).
- **Vue.js** — frontend framework (SPA + dynamic forms/tables).
- **OAuth** — a protocol for authorization (for signing in via Google).
- **Docker** — containerization and orchestration (PHP-FPM, Nginx, MySQL, Redis).
- **Cloudflare** — proxying and additional protection at the DNS/SSL level.

---

## 3. Architectural Features

### 3.1 Backend Architecture for Telegram Bots

The project has a separate "Bot Panel" that implements the **service layer** for managing bots. Additionally, the **Bot Panel** functions as an admin panel where you can:

- **Add** and **configure** Telegram bots,
- **Monitor** users and bots,
- Interact with message processing logic through a convenient web interface.

Key aspects of the service layer:
1. **Controller** receives Updates from Telegram → passes them to `TelegramHandler`.
2. **TelegramHandler** determines which bot (strategy) is used, passes data through middlewares (Laravel Pipeline), and invokes the corresponding logic.
3. **Strategies** (e.g., `CaloriesService`) inherit from the base class (`BaseService`), where handlers are registered:
    - `MessageUpdateHandler` (processing texts, voice messages, images)
    - `CallbackQueryHandler` (processing inline buttons)
    - and others.

This ensures flexibility in adding new bots and extensions.  
**More details**: [See the separate README on the bot service layer](./README.BotPanelArchitecture.en.md)

### 3.2 Dynamic Forms and Tables in Vue

Universal components have been developed for the frontend:
- Forms are built based on configs: each object defines a field (type, placeholder, required, etc.).
- Tables are also configured with configs: each object in the array describes a column (name, cell type, character limit, etc.).

This allows adding or removing fields and columns without changing the component code.  
**More details**: [See the separate README on dynamic forms/tables](./README.DynamicFormsAndTables.en.md)

---

## 4. Development Environment

- **Modified Laradock**: a unified environment with PHP-FPM, Node.js, MySQL, Redis, Meilisearch, etc.
- **Ngrok**: remote access to the local server (Telegram Webhook can come to the local machine).
- **FFmpeg**: installed for media file conversion/analysis (voice messages).

---

## 5. CI/CD and Server Deployment

### Brief Settings

- Deployment is done on a **own server** configured with Cloudflare (DNS/SSL).
- **Docker + docker-compose**: for each application (Calories365 and Bot Panel), there is a set of containers (PHP, Nginx, Redis, MySQL, etc.). Services communicate through the **internal Docker network**.
- **GitHub Actions**: when pushing to `main`, deployment to the server and production build are triggered automatically.

### Details

- Each of the **two services** (Calories365 and Bot Panel) includes:
    - **Dockerfile**, using multi-stage builds (PHP, Node/Vite, Nginx).
    - **docker-compose.yml**, describing containers and their dependencies.
    - **nginx.conf**, setting the virtual host (server blocks) for each service.

---

## 6. Conclusion

**Calories365** is a universal tool for counting calories and controlling your diet:
- **Telegram bot** saves your time thanks to voice data entry.
- **Web application** provides visual statistics, a wide product database, and a convenient interface.
- **Bot Panel** serves as an admin panel for bots and provides flexible Telegram logic configuration, as well as user monitoring.
- **Architecture** (microservices within Docker, flexible configs, CI/CD) simplifies further development and scaling.

If you need to **quickly** and **comfortably** monitor your diet — our "Calories365" project with a Telegram bot and a dynamic web dashboard is perfectly suited for these tasks!

---

> **Additionally**:
> - [**Service Layer Architecture (Bot Panel)**](./README.BotPanelArchitecture.en.md)
> - [**Dynamic Forms and Tables in Vue**](./README.DynamicFormsAndTables.en.md)  
