<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeFeedbackSurveys extends Command
{
    /**
     * php artisan make:feedback-surveys {count=5}
     */
    protected $signature = 'make:feedback-surveys {count=5}';

    protected $description = 'Tạo nhiều file FeedbackSurveyX kế thừa FeedbackSurveyBase (dùng slug để lấy khảo sát, title động từ DB)';

    public function handle()
    {
        $count = (int) $this->argument('count');
        $baseNamespace = 'App\\Filament\\Clusters\\KhaoSat\\Pages';
        $basePath = app_path('Filament/Clusters/KhaoSat/Pages');

        if (! File::exists($basePath)) {
            File::makeDirectory($basePath, 0755, true);
        }

        for ($i = 1; $i <= $count; $i++) {
            $className = "FeedbackSurvey{$i}";
            $filePath = $basePath . '/' . $className . '.php';

            if (File::exists($filePath)) {
                $this->warn("⚠ {$className} đã tồn tại, bỏ qua.");
                continue;
            }

            $stub = <<<PHP
<?php

namespace {$baseNamespace};

class {$className} extends FeedbackSurveyBase
{
    protected static ?string \$surveySlug = 'feedback-survey-{$i}';
}
PHP;

            File::put($filePath, $stub);
            $this->info("✅ Đã tạo: {$className}");
        }

        $this->info("🎯 Hoàn tất tạo {$count} file FeedbackSurveyX.");
    }
}
