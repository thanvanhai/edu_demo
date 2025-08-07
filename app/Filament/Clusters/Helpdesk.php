<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Helpdesk extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $title  = 'Helpdesk';
    protected static ?string $navigationLabel = 'Helpdesk';
    protected static ?string $slug = 'Helpdesk';
    protected static bool $shouldRegisterNavigation = true;
}
