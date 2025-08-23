<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class SmartCamera extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static ?string $title = 'Camera';
    protected static ?string $navigationLabel = 'Camera';
    protected static ?string $slug = 'smart-camera';
    protected static bool $shouldRegisterNavigation = true;

    public static function getNavigationLabel(): string
    {
        return 'Hệ thống camera thông minh';
    }
}
