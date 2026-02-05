# Laravel 12 + Vue 3 + Vite + Tailwind 4 + MySQL (Docker) Boilerplate

Local development stack for a SPA application with:

- Laravel 12S2 (API backend)
- Vue 3 (Composition API + script setup)
- Vite
- Tailwind CSS v4
- MySQL 8.4
- Docker Compose (backend, frontend, db)
- Swagger / OpenAPI docs (L5-Swagger)
- Frontend + backend testing

---

## 🚀 Stack Overview

| Service        | URL / Port                              |
|----------------|-----------------------------------------|
| Frontend       | http://localhost:5173                   |
| Backend        | http://localhost:8080                   |
| API Ping       | http://localhost:8080/api/ping          |
| Swagger UI     | http://localhost:8080/api/documentation |
| Swagger JSON   | http://localhost:8080/docs              |
| MySQL          | localhost:3306                          |

---

## 📦 Requirements

- Docker + Docker Compose
- WSL2 / Linux / macOS recommended for dev

---

## 🛠️ First-Time Setup

docker compose build --no-cache
docker compose up -d

Verify:

docker compose ps
curl http://localhost:8080/api/ping

Open frontend:

http://localhost:5173

---

## 📚 Swagger / OpenAPI Docs

Swagger UI (GUI):
http://localhost:8080/api/documentation

Raw OpenAPI JSON:
http://localhost:8080/docs

Regenerate docs:

docker compose exec backend php artisan l5-swagger:generate

---

## 🧪 Testing

Frontend (Vitest):

docker compose exec frontend npm run test

Backend (PHPUnit):

docker compose exec backend php artisan test

---

## 🧹 Full Reset (Rebuild Everything)

docker compose down -v --remove-orphans
docker compose build --no-cache
docker compose up -d

---

## ⚠️ Common Issues & Fixes

### Laravel routes return 403 or 404

Cause: Debian Nginx default site overriding Laravel public root.

Fix (one-time):

docker compose exec backend rm -f /etc/nginx/sites-enabled/default
docker compose exec backend nginx -s reload

Permanent fix is baked into the Dockerfile.

---

### Laravel 500: permission denied on storage/framework/views

Fix:

docker compose exec backend chown -R www-data:www-data storage bootstrap/cache
docker compose exec backend chmod -R ug+rwX storage bootstrap/cache
docker compose exec backend php artisan optimize:clear

---

### Swagger UI 404

Verify routes:

docker compose exec backend php artisan route:list | grep swagger

Regenerate:

docker compose exec backend php artisan l5-swagger:generate

---

### Frontend tests: expect is not defined

Ensure Vitest globals:

test: {
globals: true,
environment: 'jsdom',
setupFiles: './src/test/setup.ts',
}

---

### MySQL connection issues

Check DB logs:

docker compose logs db

Confirm backend .env:

DB_HOST=db
DB_PORT=3306
DB_DATABASE=app
DB_USERNAME=app
DB_PASSWORD=app

---

## 📁 Project Structure

.
├── backend/
│   ├── app/
│   ├── public/
│   ├── config/
│   ├── docker/
│   ├── nginx/
│   └── Dockerfile
├── frontend/
│   ├── src/
│   ├── vite.config.ts
│   └── Dockerfile
├── mysql/
└── docker-compose.yml

---

## 🔁 Dev Workflow

docker compose up -d
docker compose logs -f backend
docker compose logs -f frontend
docker compose exec backend bash
docker compose exec frontend sh

---

## 📌 Production Notes

- Backend behind api.yourdomain.com
- Frontend uses VITE_API_BASE_URL=https://api.yourdomain.com
- Disable Swagger in production
- Build frontend as static assets

---

Happy building 🚀
