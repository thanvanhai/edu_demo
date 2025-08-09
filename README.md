# 📚 EDU_DEMO

**EDU_DEMO** là hệ thống quản lý nội bộ cho trường học, xây dựng bằng **Laravel** + **Filament**  
Bao gồm các module quản lý đào tạo, nhân sự, email, camera, và đặc biệt là **hệ thống khảo sát trực tuyến** tương tự Google Form.

---

## 🚀 Công nghệ sử dụng

- **PHP** 8.2+
- **Laravel** 12.x
- **FilamentPHP** 3.x
- **SQL Server** (ODBC Driver 17)
- **TailwindCSS**
- **Plugin**: [ibrahim-bougaoua/filament-rating-star](https://filamentphp.com/plugins/ibrahim-bougaoua-star-rating)

---

## 📦 Cấu trúc thư mục chính
app/
├── Filament/
│ ├── Clusters/
│ │ └── KhaoSat/
│ │ └── Pages/
│ │ ├── FeedbackSurveyBase.php
│ │ ├── FeedbackSurvey1.php
│ │ ├── FeedbackSurvey2.php
│ │ └── ...
│ └── Resources/
│ └── SurveyResource.php
├── Console/
│ └── Commands/
│ └── MakeFeedbackSurveys.php


---

## 🛠 Cài đặt

1. Clone project  
   ```bash
   git clone https://github.com/ten-ban/edu_demo.git
   cd edu_demo

composer install
npm install && npm run build

Tạo nhiều file FeedbackSurvey:

bash
Copy
Edit
php artisan make:feedback-surveys 10
Xóa cache:

bash
Copy
Edit
php artisan optimize:clear
Build assets:

bash
Copy
Edit
npm run build

DB mẫu:
"..\edu_demo\database\edu_demo_09082025.bak"

Hỗ trợ – ChatGPT (OpenAI)
