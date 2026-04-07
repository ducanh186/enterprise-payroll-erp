# Frontend App

React 19 + Vite + TypeScript frontend cho Enterprise Payroll ERP.

## Tài liệu liên quan

- [../README.md](../README.md): tổng quan toàn dự án, cài đặt và API.
- [../DEV_GUIDE.md](../DEV_GUIDE.md): hướng dẫn chạy dự án cho developer.
- [../sql_serv_imp.md](../sql_serv_imp.md): giải thích cơ chế SQL Integration / Procedures.
- [../HOW_TO_ADD_NEW_REPORT.md](../HOW_TO_ADD_NEW_REPORT.md): hướng dẫn bàn giao cho khách ở layer SQL metadata.

## Lệnh thường dùng

```bash
npm install
npm run dev
npx tsc --noEmit
npm run build
npm run lint
```

## Thư mục quan trọng

- `src/pages`: routed pages của ứng dụng.
- `src/layouts`: layout chung, hiện có `AppLayout` cho khu vực đăng nhập.
- `src/context`: auth state và session handling.
- `src/lib`: API wrapper, formatters, RBAC helpers.
- `src/components`: shared UI components.
- `src/pages/ProceduresPage.tsx`: màn hình dynamic để thực thi stored procedure theo metadata.

## Ghi chú vận hành

- Toàn bộ text UI nên giữ bằng **tiếng Việt có dấu**, còn identifier trong code giữ bằng tiếng Anh.
- Frontend không gọi stored procedure trực tiếp; mọi thao tác đi qua API Laravel.
- SQL Integration render form và bảng theo metadata, nên khi thêm procedure mới thường không cần sửa code React.
