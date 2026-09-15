<?php

namespace App\Console\Commands;

use App\Models\Permission;
use Illuminate\Console\Command;

class AddPermissionsCommand extends Command
{
    protected $signature = 'permissions:add';

    public function handle(): int
    {
        $permissions = [
            ['name' => 'View Vehicles', 'slug' => 'vehicles.view', 'module' => 'vehicles'],
            ['name' => 'Create Vehicles', 'slug' => 'vehicles.create', 'module' => 'vehicles'],
            ['name' => 'Update Vehicles', 'slug' => 'vehicles.update', 'module' => 'vehicles'],
            ['name' => 'Delete Vehicles', 'slug' => 'vehicles.delete', 'module' => 'vehicles'],
            ['name' => 'View Amenities', 'slug' => 'amenities.view', 'module' => 'amenities'],
            ['name' => 'Create Amenities', 'slug' => 'amenities.create', 'module' => 'amenities'],
            ['name' => 'Update Amenities', 'slug' => 'amenities.update', 'module' => 'amenities'],
            ['name' => 'Delete Amenities', 'slug' => 'amenities.delete', 'module' => 'amenities'],
        ];

        foreach ($permissions as $perm) {
            Permission::updateOrCreate(
                ['slug' => $perm['slug']],
                $perm
            );
            $this->info("Added: {$perm['slug']}");
        }

        $this->info('Done!');
        return Command::SUCCESS;
    }
}