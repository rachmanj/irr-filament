<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'file_path',
        'original_name',
        'mime_type',
        'size',
        'uploaded_by'
    ];

    protected $appends = ['file_url'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class)->onDelete('cascade');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }
}
