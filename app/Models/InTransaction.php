<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InTransaction extends Model
{
    protected $table = 'intransaction';
    protected $primaryKey = 'ID';

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'EntryDate',
        'InvoiceNo',
        'TransactionType',
        'RollNumber',
        'RollSize',
        'FabricColor',
        'LoomNumber',
        'Lamination',
        'RequiredGramMeter',
        'OpeningMeter',
        'ClosingMeter',
        'ActualMeter',
        'GrossWeight',
        'CoreWeight',
        'NetWeight',
        'ActualMeterWeight',
        'Variation',
        'IsActive',
        'CreatedBy',
        'UpdatedBy',
    ];

    public function rollSizeRelation()
    {
        return $this->belongsTo(RollSize::class, 'RollSize', 'ID');
    }

    public function fabricColorRelation()
    {
        return $this->belongsTo(FabricColor::class, 'FabricColor', 'ID');
    }

    public function loomNumberRelation()
    {
        return $this->belongsTo(LoomNumber::class, 'LoomNumber', 'ID');
    }
}
