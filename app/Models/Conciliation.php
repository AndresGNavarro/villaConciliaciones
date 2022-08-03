<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conciliation extends Model
{
    use HasFactory;

    protected $table = "conciliations";
    protected $primaryKey = 'pkConciliation';

    public function user(){
        return $this->belongsTo(User::class, 'id','id');
    }

    public function period(){
        return $this->belongsTo(Period::class, 'pkPeriod','pkPeriod');
    }

    public function documents(){
        return $this->hasMany(Document::class, 'pkConciliation','pkConciliation');
    }
}
