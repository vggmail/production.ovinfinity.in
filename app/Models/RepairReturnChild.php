<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairReturnChild extends Model
{
    protected $table = 'inrepairreturnchild';
    protected $primaryKey = 'ID';

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'RepairReturn',
        'RepairIssueChild',
        'ItemMaster',
        'IssuedQuantity',
        'Quantity', // Returned Quantity
        'Rate',
        'Amount',
        'IsActive',
        'CreatedBy',
        'UpdatedBy',
    ];

    protected $casts = [
        'IssuedQuantity' => 'float',
        'Quantity' => 'float',
        'Rate' => 'float',
        'Amount' => 'float',
        'IsActive' => 'boolean',
        'CreatedOn' => 'datetime',
        'UpdatedOn' => 'datetime',
    ];

    public function repairReturnRelation()
    {
        return $this->belongsTo(RepairReturn::class, 'RepairReturn', 'ID');
    }

    public function repairIssueChildRelation()
    {
        return $this->belongsTo(RepairIssueChild::class, 'RepairIssueChild', 'ID');
    }

    public function itemMasterRelation()
    {
        return $this->belongsTo(ItemMaster::class, 'ItemMaster', 'ID');
    }
}
