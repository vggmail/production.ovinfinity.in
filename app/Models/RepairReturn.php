<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairReturn extends Model
{
    protected $table = 'inrepairreturn';
    protected $primaryKey = 'ID';

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'ReturnNo',
        'ReturnDate',
        'RepairIssue',
        'Supplier',
        'TotalItems',
        'TotalQuantity',
        'TotalAmount',
        'Status',
        'Remarks',
        'IsActive',
        'CreatedBy',
        'UpdatedBy',
    ];

    protected $casts = [
        'ReturnDate' => 'date:Y-m-d',
        'TotalItems' => 'integer',
        'TotalQuantity' => 'float',
        'TotalAmount' => 'float',
        'IsActive' => 'boolean',
        'CreatedOn' => 'datetime',
        'UpdatedOn' => 'datetime',
    ];

    public function repairIssueRelation()
    {
        return $this->belongsTo(RepairIssue::class, 'RepairIssue', 'ID');
    }

    public function supplierRelation()
    {
        return $this->belongsTo(Supplier::class, 'Supplier', 'ID');
    }

    public function children()
    {
        return $this->hasMany(RepairReturnChild::class, 'RepairReturn', 'ID');
    }
}
