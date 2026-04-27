<?php

namespace Database\Seeders;

use App\Services\SystemConfigService;
use Illuminate\Database\Seeder;

/**
 * SystemConfigSeeder
 * 
 * Initializes system configuration defaults
 */
class SystemConfigSeeder extends Seeder
{
    public function run(): void
    {
        SystemConfigService::initializeDefaults();
        
        $this->command->info('System configurations initialized successfully!');
    }
}