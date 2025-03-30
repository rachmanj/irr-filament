<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdditionalDocumentType extends Model
{
    protected $table = 'additional_document_types';
    
    protected $fillable = ['type_name'];
} 