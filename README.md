# balanseApi

Простое, надёжное и полностью протестированное API для управления балансом пользователей.  
Работает с PostgreSQL, развёрнуто в Docker, покрыто интеграционными тестами.

## Возможности

- **Пополнение баланса** (`POST /api/deposit`)
- **Списание средств** (`POST /api/withdraw`) — с защитой от ухода в минус
- **Перевод между пользователями** (`POST /api/transfer`) — атомарно, с двумя записями в логе
- **Получение текущего баланса** (`GET /api/balance/{user_id}`)
- **Просмотр истории операций** (`GET /api/transactions/{user_id}`)

Все операции:
- Выполняются в **транзакциях**
- Возвращают **валидный JSON**
- Используют корректные HTTP-статусы:
  - `200` — успех
  - `400 / 422` — ошибка валидации
  - `404` — пользователь не найден
  - `409` — недостаточно средств

Типы транзакций: `deposit`, `withdraw`, `transfer_in`, `transfer_out`.

## Установка и запуск

### 1. Клонирование проекта
```bash 
git clone https://github.com/nejicun/balanseApi.git
cd balanseApi
```
### 2. Запуск окружения
```bash 
docker-compose up -d
docker-compose exec app composer install
```
### 3. Применение миграций и заполнение тестовыми данными
```bash 
docker-compose exec app php artisan migrate --seed --seeder=UserBalanceSeeder
```
### 4. Тестирование
```bash 
docker-compose exec app php artisan test
```

### Доступ к сервисам
 - API: http://localhost:8000/api/ ...
 - Веб-интерфейс для отладки: http://localhost:8000
 - Требуется Docker Desktop с включённой поддержкой WSL 2 (на Windows).
 - На Linux/macOS — достаточно установленного Docker и Docker Compose. 

### Технологии
 - PHP 8.2 (FPM)
 - Laravel 12
 - PostgreSQL 15
 - Nginx
 - Docker Compose (минималистичная сборка без Laradock)

 - PHPUnit — 16 feature-тестов, 100% покрытие бизнес-логики
