<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GRN extends Model
{
    protected $table = 'ingrn';
    protected $primaryKey = 'ID';

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'GRNNumber',
        'GRNDate',
        'InvoiceNo',
        'InvoiceDate',
        'Supplier',
        'PINumbers',
        'TotalAmount',
        'TotalGSTAmount',
        'GrandTotal',
        'Remarks',
        'IsActive',
        'CreatedBy',
        'UpdatedBy',
    ];

    protected $casts = [
        'TotalAmount' => 'float',
        'TotalGSTAmount' => 'float',
        'GrandTotal' => 'float',
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
        return $this->hasMany(GRNChild::class, 'GRN', 'ID');
    }
}
