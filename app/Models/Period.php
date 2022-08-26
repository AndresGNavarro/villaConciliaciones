<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Period extends Model
{
    use HasFactory;

    protected $table = "periods";
    protected $primaryKey = 'pkPeriod';
    public $timestamps = false;

    public function conciliations(){
        return $this->hasMany(Conciliation::class, 'pkPeriod','pkPeriod');
    }

    public function documents(){
        return $this->hasMany(Document::class, 'pkPeriod','pkPeriod');
    }
}
