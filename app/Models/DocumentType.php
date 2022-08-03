<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory;

    protected $table = "document_types";
    protected $primaryKey = 'pkDocumentType';

    public function documents(){
        return $this->hasMany(Document::class, 'pkDocumentType','pkDocumentType');
    }
}
