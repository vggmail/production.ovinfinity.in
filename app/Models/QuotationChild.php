<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationChild extends Model
{
    protected $table = 'inquotationchild';
    protected $primaryKey = 'ID';

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'Quotation',
        'MRLEntryChild',
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

    public function quotationRelation()
    {
        return $this->belongsTo(Quotation::class, 'Quotation', 'ID');
    }

    public function mrlChildRelation()
    {
        return $this->belongsTo(MRLEntryChild::class, 'MRLEntryChild', 'ID');
    }

    public function itemMasterRelation()
    {
        return $this->belongsTo(ItemMaster::class, 'ItemMaster', 'ID');
    }
}
