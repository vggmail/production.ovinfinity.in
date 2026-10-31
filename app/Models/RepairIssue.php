<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairIssue extends Model
{
    protected $table = 'inrepairissue';
    protected $primaryKey = 'ID';

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'RepairNo',
        'RepairDate',
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
        'RepairDate' => 'date:Y-m-d',
        'TotalItems' => 'integer',
        'TotalQuantity' => 'float',
        'TotalAmount' => 'float',
        'IsActive' => 'boolean',
        'CreatedOn' => 'datetime',
        'UpdatedOn' => 'datetime',
    ];

    public function supplierRelation()
    {
        return $this->belongsTo(Supplier::class, 'Supplier', 'ID');
    }

    public function children()
    {
        return $this->hasMany(RepairIssueChild::class, 'RepairIssue', 'ID');
    }

    public function returns()
    {
        return $this->hasMany(RepairReturn::class, 'RepairIssue', 'ID');
    }
}
