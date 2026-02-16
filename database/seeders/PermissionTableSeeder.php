<?php

namespace Database\Seeders;

use App\Constants\AppPermission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = AppPermission::all();
        foreach ($permissions as $item) {
            Permission::query()
                ->updateOrCreate([
                    'name' => $item,
                ], [
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                    'description' => ucwords($item),
                ]);
        }
    }
}
