# Game Price Tracker

Отслеживание цен на игры в Steam с уведомлениями в Telegram и Email.

Web: https://steam.khanvps.xyz/
Telegram: @GamePriceTrackerTelegramBot

![Laravel](https://img.shields.io/badge/Laravel-13-red?logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.5-blue?logo=php)
![Tests](https://img.shields.io/badge/tests-16%20passed-green)
![Docker](https://img.shields.io/badge/Docker-ready-blue?logo=docker)
![License](https://img.shields.io/badge/license-MIT-green)

## Функциональность

- **Поиск игр** через Steam (HTML-парсинг + API).
- **График цен** на странице игры (Chart.js).
- **Уведомления** при падении цены ниже целевой (Email + Telegram).
- **Telegram-бот** с набором команд:
    - `/start` — начало работы.
    - `/search` — поиск игр.
    - `/track` — добавить в отслеживание.
    - `/list` — список отслеживаемых игр.
    - `/price` — текущая цена.
    - `/set` — установить целевую цену.
    - `/untrack` — удалить из отслеживания.
    - `/notify` — настройка уведомлений.
    - `/cancel` — отмена последней команды.
    - `/email` — привязка email с верификацией.
    - `/help` — список доступных команд.
- **Email-уведомления** через очереди (Redis).
- **История цен** и график изменения.
- **Веб-интерфейс** на Blade с аутентификацией (Breeze).
- **Docker** — запуск одной командой.

## Стек

- **Backend:** Laravel 13, PHP 8.5
- **Database:** PostgreSQL 16
- **Queue/Cache:** Redis
- **Frontend:** Blade, Chart.js
- **Bot:** Telegram Bot API
- **API:** Steam (appdetails + HTML-парсинг)
- **Deploy:** Docker, Docker Compose, Nginx, Supervisor

---

## 🇬🇧 English

**Game Price Tracker** — a Steam game price monitoring service with Telegram and Email notifications.

### Features

- **Game search** via Steam (HTML parsing + API)
- **Price history chart** (Chart.js)
- **Notifications** when price drops below target (Email + Telegram)
- **Telegram bot** with commands: `/start`, `/search`, `/track`, `/list`, `/price`, `/set`, `/untrack`, `/notify`, `/cancel`, `/email`, `/help`
- **Email verification** via code
- **Queue-driven** notifications (Redis)
- **Docker** for easy deployment

### Stack

- **Backend:** Laravel 13, PHP 8.5
- **Database:** PostgreSQL 16
- **Queue/Cache:** Redis
- **Frontend:** Blade, Chart.js
- **Bot:** Telegram Bot API
- **API:** Steam (appdetails + HTML parsing)
- **Deploy:** Docker, Docker Compose, Nginx, Supervisor

### Quick Start

\`\`\`bash
git clone https://github.com/khantuevandrei/game-price-tracker.git
cd game-price-tracker
cp .env.example .env

# Fill .env (TELEGRAM_BOT_TOKEN required)

docker compose up -d --build
docker compose exec app php artisan migrate
docker compose exec app php artisan key:generate
\`\`\`

Open: `http://localhost:8080`

### Demo

- **Web:** [http://212.108.82.148:8080/](http://212.108.82.148:8080/)
- **Telegram:** [@GamePriceTrackerTelegramBot](https://t.me/GamePriceTrackerTelegramBot)

---

## Скриншоты

Каталог
![Каталог](public/screenshots/catalog.png)

Страница игры
![Каталог](public/screenshots/game.png)

Telegram-бот
![Бот](public/screenshots/bot.png)

Dashboard
![Dashboard](public/sreenshots/dashboard.png)

## Быстрый старт (локально)

\`\`\`bash
git clone https://github.com/khantuevandrei/game-price-tracker.git
cd game-price-tracker
cp .env.example .env

## Заполни .env (TELEGRAM_BOT_TOKEN обязательно)

docker compose up -d --build
docker compose exec app php artisan migrate
docker compose exec app php artisan key:generate
\`\`\`

Открыть: `http://localhost:8080`

## Команды Artisan

| Команда                | Описание                          |
| ---------------------- | --------------------------------- |
| `prices:fetch`         | Обновление цен из Steam API       |
| `app:telegram-polling` | Обработка сообщений Telegram-бота |

## Архитектура

\`\`\`
Web (Blade) -> Controller -> Service -> Steam API
Telegram Bot -> TelegramPolling -> Service -> Steam API
Cron -> FetchPrices -> Steam API -> PriceHistory
Queue -> SendPriceAlert -> Email / Telegram
\`\`\`
