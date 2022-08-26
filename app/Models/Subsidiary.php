<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subsidiary extends Model
{
    use HasFactory;

    protected $table = "subsidiaries";
    protected $primaryKey = 'pkSubsidiary';

    public function users(){
        return $this->hasMany(User::class, 'pkSubsidiary','pkSubsidiary');
    }

    public function subsidiaryUser(){
        return $this->belongsToMany(User::class, 'user_subsidiary', 'pkSubsidiary', 'id');
    }
}
