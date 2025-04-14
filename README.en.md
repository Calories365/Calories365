# Calories365: Calorie Counter with AI

**Calories365** is a web application that allows you to conveniently keep a food diary, automatically transcribe voice messages, and receive calorie statistics (using AI).

[//]: # (## [Try the Calorie Diary now!](https://calculator.calories365.xyz))

---

## 1. Project Overview

> **Record your food and see how many calories you consume.**

- **Calorie Diary**  
  In the web application, you record everything you eat during the day. Find a product in the database or add your own option – any personalized dish or ingredient will be taken into account.

- **Visual Statistics**  
  Monitor the dynamics of your calorie consumption over different periods. You will immediately see if you regularly exceed your limits or, on the contrary, stay within the recommended levels.

- **Voice Input on the Website**  
  On the "Voice Input" page, click "Record" and dictate the product. The system will transcribe your speech, and if the weight is missing, it will offer average values or generate nutritional information (BJU) using AI. Adjust the data as needed and save the entry.

- **Time Savings**  
  Quick data entry and accessible statistics from any browser.

---

## 2. Technologies Used

- **Laravel** (Sanctum + Fortify for authentication and security)
- **MySQL** — Database for storing data
- **Redis** — Caching and session storage
- **Meilisearch** — Search in the product database
- **FFmpeg** — Audio/video conversion (processing voice messages)
- **Vue.js** — Frontend framework (SPA + dynamic forms/tables)
- **Docker** — Containerization (PHP‑FPM, Nginx, MySQL, Redis)
- **Cloudflare** — Proxying and DNS/SSL-level protection

---

## 3. Architectural Features

### 3.1 Dynamic Forms and Tables in Vue

Universal components have been developed for the frontend:

- **Forms** are built based on configurations: each object defines a field (type, placeholder, required status, etc.).
- **Tables** are also configurable: each object describes a column (header, cell type, character limit, and more).

This enables adding or removing fields and columns without changing the code.  
More details: [README.DynamicFormsAndTables.ru.md](./README.DynamicFormsAndTables.en.md)

---

## 4. Development Environment

- **Docker Environment for Development**:  
  When started, a development server is automatically launched along with Ngrok, providing remote access to the local environment.

---

## 5. CI/CD and Deployment

### Brief Overview of the Setup

- Deployment is carried out on a dedicated server configured with Cloudflare (DNS/SSL).
- **Docker + docker‑compose**:  
  The Calories365 service consists of containers for PHP, Nginx, Redis, MySQL, etc., which communicate via Docker's internal network.
- **GitHub Actions**:  
  Pushing to the branch triggers an automatic deployment and production build.

### Details

- The repository includes:
    - A **Dockerfile** with multi-stage builds (PHP, Node/Vite, Nginx)
    - A **docker‑compose.yml** that describes the containers and their dependencies
    - An **nginx.conf** with a virtual host configuration for the service

---

## 6. Conclusion

**Calories365** is a versatile tool for calorie counting and diet monitoring:

- **Voice Input** speeds up product addition directly from your browser.
- **The Web Application** provides visual statistics, an extensive product database, and an intuitive interface.
- **Flexible Architecture** (Docker, CI/CD, configuration-based components) simplifies development and scaling.

If you need to quickly and comfortably manage your diet, Calories365 is an excellent solution!

---

> **Additionally**:
> - [**Dynamic Forms and Tables in Vue**](./README.DynamicFormsAndTables.ru.md)
