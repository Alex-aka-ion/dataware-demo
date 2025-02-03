# Symfony Microservices Project

Этот проект представляет собой микросервисную архитектуру на базе Symfony, включающую следующие сервисы:

- **API Gateway** — для маршрутизации и агрегации запросов.
- **Product Service** — управление продуктами.
- **Order Service** — управление заказами.

Проект использует **PostgreSQL** для хранения данных и **Redis** для кеширования. В качестве прокси-сервера используется **Nginx**.
Кеширование в проекте пока не подключено в коде, только подключен **Redis**.

Окружение: Nginx + PHP8.3-fpm + Symfony 7.2 + Postgres 15

---

## 📦 Структура проекта

```
├── docker/                  # Конфигурации Docker (Nginx, PHP, Xdebug)
├── services/
│   ├── api-gateway/         # API Gateway (Symfony)
│   ├── product-service/     # Product Service (Symfony)
│   └── order-service/       # Order Service (Symfony)
├── docker-compose.yml       # Основной Docker Compose файл
└── README.md                # Документация проекта
```

---

## Установка и запуск

### Предварительные требования

- [Docker](https://www.docker.com/) и [Docker Compose](https://docs.docker.com/compose/install/)

### Клонирование репозитория

```bash
git clone https://github.com/Alex-aka-ion/dataware-demo
cd dataware-demo
```

### Запуск контейнеров

```bash
docker compose up -d --build
```

### Проверка работы сервисов

- **API Gateway:** [http://localhost:51000](http://localhost:51000)
- **Product Service:** [http://localhost:52000](http://localhost:52000)
- **Order Service:** [http://localhost:53000](http://localhost:53000)

---

### Тестирование

Для запуска тестов в сервисах выполните:

```bash
docker exec -it php-fpm-api-gateway bin/phpunit
docker exec -it php-fpm-product-service bin/phpunit
docker exec -it php-fpm-order-service bin/phpunit
```

---

## Документация API

Документация API через Swagger UI доступна по следующим адресам:

- **Product Service:** [http://localhost:52000/api/doc](http://localhost:52000/api/doc)
- **Order Service:** [http://localhost:53000/api/doc](http://localhost:53000/api/doc)

Либо через Api-gateway:
- **Product Service:** [http://localhost:51000/api/doc-product-service](http://localhost:51000/api/doc-product-service)
- **Order Service:** [http://localhost:51000/api/doc-order-service](http://localhost:51000/api/doc-order-service)

---

## 🚓 Полезные команды

- Перезапуск контейнеров: `docker compose restart`
- Просмотр логов: `docker compose logs -f`
- Остановка и удаление контейнеров: `docker compose down`

