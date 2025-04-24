# Z-Test Backend

Сервис для управления тендерами, созданный на Symfony.

## Установка
1. Клонируйте репозиторий: `git clone <your-repo-url>`
2. Установите зависимости: `composer install`
3. Настройте базу данных в `.env`: `DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"`
4. Выполните миграции: `php bin/console doctrine:migrations:migrate`
5. Импортируйте данные: `php bin/console app:import-tenders test_task_data.csv`
6. Запустите сервер: `symfony server:start --port=37705 --no-tls`

## API Эндпоинты
- **POST /api/tenders** — Создание тендера.
- **GET /api/tenders/{id}** — Получение тендера по ID.
- **POST /api/tenders/list** — Получение списка тендеров по фильтру.