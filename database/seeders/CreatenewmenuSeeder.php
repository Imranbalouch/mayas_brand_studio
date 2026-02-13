<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Menu;


class CreatenewmenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        $now = Carbon::now();
        $email = 'ra@digitalgraphiks.ae';
        $user = User::where('email', $email)->first();
        $auth_id = $user->uuid;
        $role_id = $user->role_id;

        
        $menus = [
            [
                'uuid' => Str::uuid(),
                'name' => 'Permission Assign',
                'description' => 'empty description',
                'sort_id' => '1',
                'icon' => '',
                'auth_id' => $auth_id,
                'status' => '0',
                'parent_id' => '0',
                'url' => 'adminmenus',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Language',
                'description' => 'empty description',
                'sort_id' => '1',
                'icon' => '',
                'auth_id' => $auth_id,
                'status' => '0',
                'parent_id' => '0',
                'url' => 'adminmenus',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Special Permission',
                'description' => 'empty description',
                'sort_id' => '1',
                'icon' => '',
                'auth_id' => $auth_id,
                'status' => '0',
                'parent_id' => '0',
                'url' => 'adminmenus',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Activity Log',
                'description' => 'empty description',
                'sort_id' => '1',
                'icon' => '',
                'auth_id' => $auth_id,
                'status' => '0',
                'parent_id' => '0',
                'url' => 'adminmenus',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'User',
                'description' => 'empty description',
                'sort_id' => '1',
                'icon' => '',
                'auth_id' => $auth_id,
                'status' => '0',
                'parent_id' => '0',
                'url' => 'adminmenus',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Menu',
                'description' => 'empty description',
                'sort_id' => '1',
                'icon' => '',
                'auth_id' => $auth_id,
                'status' => '0',
                'parent_id' => '0',
                'url' => 'adminmenus',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Menu Translation',
                'description' => 'empty description',
                'sort_id' => '1',
                'icon' => '',
                'auth_id' => $auth_id,
                'status' => '0',
                'parent_id' => '0',
                'url' => 'adminmenus',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Role',
                'description' => 'empty description',
                'sort_id' => '1',
                'icon' => '',
                'auth_id' => $auth_id,
                'status' => '0',
                'parent_id' => '0',
                'url' => 'adminmenus',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Role Translation',
                'description' => 'empty description',
                'sort_id' => '1',
                'icon' => '',
                'auth_id' => $auth_id,
                'status' => '0',
                'parent_id' => '0',
                'url' => 'adminmenus',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Permission',
                'description' => 'empty description',
                'sort_id' => '1',
                'icon' => '',
                'auth_id' => $auth_id,
                'status' => '0',
                'parent_id' => '0',
                'url' => 'adminmenus',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Permission Translation',
                'description' => 'empty description',
                'sort_id' => '1',
                'icon' => '',
                'auth_id' => $auth_id,
                'status' => '0',
                'parent_id' => '0',
                'url' => 'adminmenus',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Testimonial',
                'description' => 'empty description',
                'sort_id' => '1',
                'icon' => '',
                'auth_id' => $auth_id,
                'status' => '0',
                'parent_id' => '0',
                'url' => 'adminmenus',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Website Config',
                'description' => 'empty description',
                'sort_id' => '1',
                'icon' => '',
                'auth_id' => $auth_id,
                'status' => '0',
                'parent_id' => '0',
                'url' => 'adminmenus',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],

        ];
        
        DB::table('menus')->insert($menus);
        // Menu::create($menus);

    }
    
}