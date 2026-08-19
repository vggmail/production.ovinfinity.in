<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MRLEntryChild extends Model
{
    protected $table = 'inmrlentrychild';
    protected $primaryKey = 'ID';

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'MRLEntry',
        'ItemMaster',
        'Quantity',
        'IsActive',
        'CreatedBy',
        'UpdatedBy',
    ];

    protected $casts = [
        'Quantity' => 'float',
        'IsActive' => 'boolean',
        'CreatedOn' => 'datetime',
        'UpdatedOn' => 'datetime',
    ];

    public function mrlEntryRelation()
    {
        return $this->belongsTo(MRLEntry::class, 'MRLEntry', 'ID');
    }

    public function itemMasterRelation()
    {
        return $this->belongsTo(ItemMaster::class, 'ItemMaster', 'ID');
    }
}
