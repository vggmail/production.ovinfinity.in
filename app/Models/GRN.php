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
        'Supplier',
        'PINumbers',
        'Remarks',
        'IsActive',
        'CreatedBy',
        'UpdatedBy',
    ];

    protected $casts = [
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
