<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AdditionalDocument extends Model
{
    protected $guarded = [];

    public function type(): BelongsTo
    {
        return $this->belongsTo(AdditionalDocumentType::class, 'type_id', 'id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function lpds(): BelongsToMany
    {
        return $this->belongsToMany(Lpd::class, 'additional_document_lpd');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'cur_loc', 'location_code');
    }

    public function getDepartmentNameAttribute(): ?string
    {
        return $this->department?->name;
    }

    public function getDepartmentProjectAttribute(): ?string
    {
        return $this->department?->project;
    }

    public function getDepartmentLocationCodeAttribute(): ?string
    {
        return $this->department?->location_code;
    }

    public function getDepartmentFullInfoAttribute(): ?string
    {
        if (!$this->department) {
            return null;
        }
        return "{$this->department->name} ({$this->department->location_code})" . 
               ($this->department->project ? " - {$this->department->project}" : '');
    }
}
