# Forbes Middle East — Advanced Filtering API

## Run the project

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

API: **http://localhost:8000/api/v1**

Update `.env` with your PostgreSQL credentials before migrating.

Note: And in postman collection you will found the postmand collection and environment files
