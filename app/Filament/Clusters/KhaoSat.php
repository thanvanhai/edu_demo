<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class KhaoSat extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-face-smile';
    protected static ?string $title  = 'Quản lý khảo sát';
    protected static ?string $navigationLabel = 'Quản lý khảo sát';
    protected static ?string $slug = 'survey';
    protected static bool $shouldRegisterNavigation = true;
}
