<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairIssueChild extends Model
{
    protected $table = 'inrepairissuechild';
    protected $primaryKey = 'ID';

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'RepairIssue',
        'ItemMaster',
        'Quantity',
        'Rate',
        'Amount',
        'IsActive',
        'CreatedBy',
        'UpdatedBy',
    ];

    protected $casts = [
        'Quantity' => 'float',
        'Rate' => 'float',
        'Amount' => 'float',
        'IsActive' => 'boolean',
        'CreatedOn' => 'datetime',
        'UpdatedOn' => 'datetime',
    ];

    public function repairIssueRelation()
    {
        return $this->belongsTo(RepairIssue::class, 'RepairIssue', 'ID');
    }

    public function itemMasterRelation()
    {
        return $this->belongsTo(ItemMaster::class, 'ItemMaster', 'ID');
    }
}
