<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PIChild extends Model
{
    protected $table = 'inpichild';
    protected $primaryKey = 'ID';

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'PI',
        'MRLEntryChild',
        'MRLEntry',
        'ItemMaster',
        'Quantity',
        'BasicRate',
        'Amount',
        'TradeDisPercent',
        'TradeDisAmount',
        'NetValAfterTrade',
        'AdPayDisPercent',
        'AdPayDisAmount',
        'NetValAfterDis',
        'PackChargesPercent',
        'PackChargesAmount',
        'FreightPercent',
        'FreightAmount',
        'NetAmount',
        'GSTRate',
        'GSTAmount',
        'PIAmount',
        'NetRate',
        'IsActive',
        'CreatedBy',
        'UpdatedBy',
    ];

    protected $casts = [
        'Quantity' => 'float',
        'BasicRate' => 'float',
        'Amount' => 'float',
        'TradeDisPercent' => 'float',
        'TradeDisAmount' => 'float',
        'NetValAfterTrade' => 'float',
        'AdPayDisPercent' => 'float',
        'AdPayDisAmount' => 'float',
        'NetValAfterDis' => 'float',
        'PackChargesPercent' => 'float',
        'PackChargesAmount' => 'float',
        'FreightPercent' => 'float',
        'FreightAmount' => 'float',
        'NetAmount' => 'float',
        'GSTRate' => 'float',
        'GSTAmount' => 'float',
        'PIAmount' => 'float',
        'NetRate' => 'float',
        'IsActive' => 'boolean',
        'CreatedOn' => 'datetime',
        'UpdatedOn' => 'datetime',
    ];

    public function piRelation()
    {
        return $this->belongsTo(PI::class, 'PI', 'ID');
    }

    public function itemMasterRelation()
    {
        return $this->belongsTo(ItemMaster::class, 'ItemMaster', 'ID');
    }

    public function mrlEntryRelation()
    {
        return $this->belongsTo(MRLEntry::class, 'MRLEntry', 'ID');
    }

    public function mrlEntryChildRelation()
    {
        return $this->belongsTo(MRLEntryChild::class, 'MRLEntryChild', 'ID');
    }
}
