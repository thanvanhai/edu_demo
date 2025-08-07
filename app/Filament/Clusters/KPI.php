<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class KPI extends Cluster  
{
    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';
    protected static ?string $title  = 'KPI';
    protected static ?string $navigationLabel = 'KPI';
    protected static ?string $slug = 'kpi';
    protected static bool $shouldRegisterNavigation = true;

    public static function getNavigationLabel(): string
    {
        return 'Quản lý KPI';
    }

}
