<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $table = "roles";
    protected $primaryKey = 'pkRole';

    public function users(){
        return $this->hasMany(User::class, 'pkRole','pkRole');
    }

    public function menus(){
        return $this->belongsToMany(Menu::class, 'menu_role', 'pkRole', 'pkMenu');
    }
}
