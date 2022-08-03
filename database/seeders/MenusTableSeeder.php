<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenusTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Menu::create([
            'name' => 'Inicio',
            'slug' => 'home',
            'icon' => 'ni ni-tv-2',
            'parent' => 0,
            'order' => 0,			
        ]);
        $m1 =  Menu::create([
            'name' => 'Procesos',
            'slug' => '',
            'icon' => 'ni ni-collection',
            'parent' => 0,
            'order' => 1,			
        ]);
        $m2 =  Menu::create([
            'name' => 'Configuraciones',
            'slug' => '',
            'icon' => 'ni ni-settings',
            'parent' => 0,
            'order' => 2,			
        ]);
        Menu::create([
            'name' => 'Nuevo Análisis',
            'slug' => 'home',
            'icon' => '',
            'parent' => $m1->pkMenu,
            'order' => 0,			
        ]);
        Menu::create([
            'name' => 'Historial',
            'slug' => 'home',
            'icon' => '',
            'parent' => $m1->pkMenu,
            'order' => 1,			
        ]);
        Menu::create([
            'name' => 'IATAS',
            'slug' => 'iatas',
            'icon' => '',
            'parent' => $m2->pkMenu,
            'order' => 0,		
        ]);
        Menu::create([
            'name' => 'Usuarios',
            'slug' => 'user',
            'icon' => '',
            'parent' => $m2->pkMenu,
            'order' => 1,		
        ]);
        Menu::create([
            'name' => 'Roles',
            'slug' => 'role',
            'icon' => '',
            'parent' => $m2->pkMenu,
            'order' => 2,		
        ]);
        Menu::create([
            'name' => 'Sucursales',
            'slug' => 'subsidiary',
            'icon' => '',
            'parent' => $m2->pkMenu,
            'order' => 3,		
        ]);
        Menu::create([
            'name' => 'Menus',
            'slug' => 'menu',
            'icon' => '',
            'parent' => $m2->pkMenu,
            'order' => 4,		
        ]);
    
    }
}
