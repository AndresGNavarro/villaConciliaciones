<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $table = "menus";
    protected $primaryKey = 'pkMenu';

    /* El objetivo del método menus() es recorrer todas las opciones del menú y en aquellas opciones “padre” obtener sus “hijos” 
    u opciones que dependerán de la opción principal, y éste grupo de ítems quedarán registrados en un array llamado submenú. */
    public static function menus()
    {
        $menus = new Menu();
        $data = $menus->optionsMenu();
        /* dd($data); */
        $menuAll = [];
        foreach ($data as $line) {
            $item = [ array_merge($line, ['submenu' => $menus->getChildren($data, $line) ]) ];
            $menuAll = array_merge($menuAll, $item);
        }
        return $menus->menuAll = $menuAll;
    }

    /* Retorna un array con las opciones del menú activas (enabled = 1) y ordenadas por parent, order y name. */
    public function optionsMenu()
    {
            $menuOptions = Menu::where('enabled', 1)
            ->orderby('parent')
            ->orderby('order')
            ->orderby('name')
            ->get()
            ->toArray();
            return $menuOptions;
            /* return $this->where('enabled', 1)
            ->orderby('parent')
            ->orderby('order')
            ->orderby('name')
            ->get()
            ->toArray(); */
    }

    /* Recorre el array $data para extraer los “hijos” (el valor del campo parent debe coincidir con el id de la opción superior). */
    public function getChildren($data, $line)
    {
        $children = [];
        foreach ($data as $line1) {
            if ($line['pkMenu'] == $line1['parent']) {
                $children = array_merge($children, [ array_merge($line1, ['submenu' => $this->getChildren($data, $line1) ]) ]);
            }
        }
        return $children;
    }

    public function roles(){
        return $this->belongsToMany(Role::class, 'menu_role', 'pkMenu', 'pkRole');
    }
    
    
}
