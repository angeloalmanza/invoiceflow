# InvoiceFlow – backend

API Laravel. Istruzioni, endpoint e modello dati sono nel [README principale](../README.md).

```bash
composer install && cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate
php artisan serve   # http://localhost:8000
vendor/bin/phpunit  # test
```
