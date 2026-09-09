<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PI extends Model
{
    protected $table = 'inpi';
    protected $primaryKey = 'ID';

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'InvoiceEntryNo',
        'PIDate',
        'Supplier',
        'PINumber',
        'MRLNumbers',
        'TotalItems',
        'TotalQuantity',
        'TotalAdPayDisAmount',
        'TotalNetValAfterDis',
        'TotalPackChargesAmount',
        'TotalFreightAmount',
        'TotalNetAmount',
        'TotalGSTAmount',
        'TotalPIAmount',
        'Remarks',
        'IsActive',
        'CreatedBy',
        'UpdatedBy',
    ];

    protected $casts = [
        'TotalItems' => 'integer',
        'TotalQuantity' => 'float',
        'TotalAdPayDisAmount' => 'float',
        'TotalNetValAfterDis' => 'float',
        'TotalPackChargesAmount' => 'float',
        'TotalFreightAmount' => 'float',
        'TotalNetAmount' => 'float',
        'TotalGSTAmount' => 'float',
        'TotalPIAmount' => 'float',
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
        return $this->hasMany(PIChild::class, 'PI', 'ID');
    }
}
