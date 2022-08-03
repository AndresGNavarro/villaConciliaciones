<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $table = "documents";
    protected $primaryKey = 'pkDocument';

    public function conciliation(){
        return $this->belongsTo(Conciliation::class, 'pkConciliation','pkConciliation');
    }

    public function period(){
        return $this->belongsTo(Period::class, 'pkPeriod','pkPeriod');
    }

    public function documentType(){
        return $this->belongsTo(DocumentType::class, 'pkDocumentType','pkDocumentType');
    }
}
