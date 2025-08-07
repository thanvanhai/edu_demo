<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Services\GoogleAnalyticsService;

class AnalyticsOverview extends Widget
{
    protected static string $view = 'filament.widgets.analytics-overview';

    //trương hợp muốn ẩn widget này đi vì đã đặt đúng thư mục app/Filament/Widgets thì sẽ tự động lên panel admin
    public static function canView(): bool
    {
        return false;
    }

    public function getViewData(): array
    {
        $service = new GoogleAnalyticsService();

        return [
            'activeUsers' => $service->getRealtimeActiveUsers(),
            'visitsToday' => $service->getTodayVisitors(),
            'totalVisits'   => $service->getTotalSessions(),
        ];
    }
}
