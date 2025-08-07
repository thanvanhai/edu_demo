<?php

namespace App\Services;

use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Metric;

class GoogleAnalyticsService
{
    protected $client;
    protected $propertyId = '499225094';

    public function __construct()
    {
        $this->client = new BetaAnalyticsDataClient([
            'credentials' => storage_path('app/ga/service-account.json'),
        ]);
    }

    public function getTodayVisitors()
    {
        $response = $this->client->runReport([
            'property' => "properties/{$this->propertyId}",
            'dateRanges' => [
                new DateRange([
                    'start_date' => 'today',
                    'end_date' => 'today',
                ]),
            ],
            'metrics' => [
                new Metric(['name' => 'activeUsers']),
            ],
        ]);

        $rows = $response->getRows();
        return count($rows) > 0 ? $rows[0]->getMetricValues()[0]->getValue() : 0;
    }

    public function getRealtimeActiveUsers()
    {
        $response = $this->client->runRealtimeReport([
            'property' => "properties/{$this->propertyId}",
            'metrics' => [
                new Metric(['name' => 'activeUsers']),
            ],
        ]);

        $rows = $response->getRows();
        return count($rows) > 0 ? $rows[0]->getMetricValues()[0]->getValue() : 0;
    }
    public function getTotalSessions()
    {
        $response = $this->client->runReport([
            'property' => "properties/{$this->propertyId}",
            'dateRanges' => [
                new DateRange([
                    'start_date' => '2020-01-01', // Hoặc mốc GA4 bạn đã thiết lập
                    'end_date' => 'today',
                ]),
            ],
            'metrics' => [
                new Metric(['name' => 'sessions']),
            ],
        ]);

        $rows = $response->getRows();
        return count($rows) > 0 ? $rows[0]->getMetricValues()[0]->getValue() : 0;
    }
}
