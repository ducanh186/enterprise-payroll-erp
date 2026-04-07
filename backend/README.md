# Backend API

Laravel 11 API cho hệ thống Enterprise Payroll ERP.

## Tài liệu liên quan

- [../README.md](../README.md): tổng quan toàn dự án, cài đặt, API và điều hướng tài liệu.
- [../DEV_GUIDE.md](../DEV_GUIDE.md): hướng dẫn chạy dự án cho developer.
- [../sql_serv_imp.md](../sql_serv_imp.md): giải thích cơ chế SQL Integration / Procedures.
- [../HOW_TO_ADD_NEW_REPORT.md](../HOW_TO_ADD_NEW_REPORT.md): hướng dẫn bàn giao để khách tự thêm stored procedure mới.

## Lệnh thường dùng

```bash
composer install
php artisan key:generate
php artisan route:list --path=api
php artisan serve
vendor/bin/phpunit --configuration=phpunit.sqlite.xml --no-coverage
```

## Thư mục quan trọng

- `app/Http/Controllers/Api`: entry point cho các API controllers.
- `app/Services`: business logic và orchestration giữa controller với database.
- `app/Models`: Eloquent models.
- `database/migrations`: schema Laravel.
- `database/seeders`: seed data phục vụ môi trường dev/test.
- `database/sql`: SQL Server handoff scripts, gồm tables, views, functions và stored procedures.

## Ghi chú vận hành

- Auth API dùng **Sanctum Bearer token**, không dùng SPA cookie/CSRF flow.
- SQL Integration chạy qua `ProcedureService` và metadata tables, không để frontend gọi trực tiếp stored procedure.
- Nếu thêm procedure mới cho khách, ưu tiên cập nhật ở `database/sql` và metadata thay vì hardcode endpoint mới.
