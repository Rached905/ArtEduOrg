# ArtEduOrg

![PHP 8.2](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php)
![Symfony 6](https://img.shields.io/badge/Symfony-6-000000?logo=symfony)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql)

A platform for buying and selling artworks online. Built with **Symfony 6**, **PHP 8.2**, and **MySQL**.

---

## 1. Project overview

- **Name:** ArtEduOrg
- **What it is:** An online marketplace where artists can sell their works and buyers can discover and purchase them.
- **Tech stack:** Symfony 6, PHP 8.2, MySQL.

---

## 2. Platform features

The platform has three types of users:

- **Admin**  
  Manages users (artists and buyers), artwork listings, orders, and payments. Has access to a full dashboard with statistics.

- **Vendeur (Artist / Seller)**  
  Can create a profile, upload and manage artworks (photos, price, description, category), and track orders and sales.

- **Client (Buyer)**  
  Can browse artworks, search and filter, add items to favorites, add to cart, place orders, and track order history.

---

## 3. How to run locally (for developers)

**Requirements:** PHP 8.2, Composer, MySQL, Symfony CLI.

1. Clone the repo from GitHub.
2. Copy `.env.example` to `.env` and set `DATABASE_URL` (e.g. your local MySQL connection).
3. Run:
   ```bash
   composer install
   php bin/console doctrine:migrations:migrate
   symfony serve
   ```
4. Open **http://localhost:8000** in your browser.

---

## 4. How to run with Docker (recommended — no technical setup)

Best option if you don’t want to install PHP or MySQL on your machine.

1. Install **Docker Desktop** from [docker.com/products/docker-desktop](https://www.docker.com/products/docker-desktop/).
2. Clone or download the project folder.
3. Open a terminal in the project folder.
4. Run:
   ```bash
   docker-compose up -d
   ```
5. Wait 1–2 minutes.
6. Open **http://localhost:8080** in your browser.

The database and tables are created automatically; no configuration needed. You can register an account and start testing.

---

## 5. How to pull and run from Docker Hub (no code needed)

If you only have the Docker image (no source code), you can run the app like this:

1. Install **Docker Desktop**.
2. Create a folder on your computer.
3. In that folder, create a file named **`docker-compose.yml`** and paste the content below.
4. Create a file named **`.env`** with one line (replace `DOCKER_HUB_LOGIN` with the image owner’s login):
   ```
   ARTEDUORG_IMAGE=DOCKER_HUB_LOGIN/arteduorg-app:latest
   ```
5. Open a terminal in that folder and run:
   ```bash
   docker-compose up -d
   ```
6. Wait 1–2 minutes, then open **http://localhost:8080**.

You can register an account and test the platform immediately.

**Content for `docker-compose.yml`:**

```yaml
version: "3.8"

services:
  app:
    image: ${ARTEDUORG_IMAGE:-arteduorg/arteduorg-app:latest}
    container_name: arteduorg-app
    ports:
      - "8080:80"
    environment:
      APP_ENV: prod
      APP_SECRET: ${APP_SECRET:-change-me-in-production}
      DATABASE_URL: mysql://arteduorg:arteduorg_secret@mysql:3306/arteduorg?serverVersion=8.0&charset=utf8mb4
      MAILER_DSN: ${MAILER_DSN:-null://null}
      MAILER_FROM: ${MAILER_FROM:-ArtEduOrg <noreply@example.com>}
      STRIPE_PUBLIC_KEY: ${STRIPE_PUBLIC_KEY:-}
      STRIPE_SECRET_KEY: ${STRIPE_SECRET_KEY:-}
    depends_on:
      mysql:
        condition: service_healthy
    volumes:
      - var_data:/var/www/html/var
      - uploads_data:/var/www/html/public/uploads
      - invoices_data:/var/www/html/public/invoices

  mysql:
    image: mysql:8.0
    container_name: arteduorg-mysql
    environment:
      MYSQL_ROOT_PASSWORD: root_secret
      MYSQL_DATABASE: arteduorg
      MYSQL_USER: arteduorg
      MYSQL_PASSWORD: arteduorg_secret
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-u", "root", "-proot_secret"]
      interval: 5s
      timeout: 5s
      retries: 10

volumes:
  mysql_data:
  var_data:
  uploads_data:
  invoices_data:
```

---

## 6. Screenshots (main flows)

