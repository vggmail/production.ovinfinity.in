<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MRLEntry extends Model
{
    protected $table = 'inmrlentry';
    protected $primaryKey = 'ID';

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'EntryDate',
        'TotalItems',
        'TotalQuantity',
        'IsActive',
        'CreatedBy',
        'UpdatedBy',
    ];

    protected $casts = [
        'TotalItems' => 'integer',
        'TotalQuantity' => 'float',
        'IsActive' => 'boolean',
        'CreatedOn' => 'datetime',
        'UpdatedOn' => 'datetime',
    ];

    public function children()
    {
        return $this->hasMany(MRLEntryChild::class, 'MRLEntry', 'ID');
    }
}
