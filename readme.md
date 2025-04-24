# Z-Test Backend

Сервис для управления тендерами, созданный на Symfony.

## Требования
- PHP 8.4+
- MySQL 8.0+
- Composer
- Symfony CLI (для запуска сервера)

## Установка
1. Клонируйте репозиторий: `git clone https://github.com/Thunderkiss52/tender_symfony`
2. Установите зависимости: `composer install`
3. Настройте базу данных в `.env`: `DATABASE_URL=mysql://user:password@127.0.0.1:3308/tender?serverVersion=8.0`
4. Выполните миграции: `php bin/console doctrine:migrations:migrate`
5. Импортируйте данные: `php bin/console app:import-tenders test_task_data.csv`
6. Запустите сервер: `symfony serve`

## API Эндпоинты
- **POST /tenders** — Создание тендера.
- **GET /tenders/{id}** — Получение тендера по ID.
- **GET /tenders** — Получение списка тендеров по фильтру.