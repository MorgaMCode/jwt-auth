<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Database\Seeders\ArticleSeeder;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
          // create permissions
            Permission::create(['name' => 'create articles']);
            Permission::create(['name' => 'delete articles']);
            Permission::create(['name' => 'edit articles']);
            Permission::create(['name' => 'show articles']);
            Permission::create(['name' => 'list articles']);


            // create roles and assign created permissions
            $admin = Role::create(['name' => 'admin']);
            $admin->givePermissionTo(Permission::all());

            $customer = Role::create(['name' => 'customer']);
            $customer->givePermissionTo('list articles');
            $customer->givePermissionTo('show articles');


        $this->call(ArticleSeeder::class);

        $this->call(ArticleSeeder::class);

    }


}
