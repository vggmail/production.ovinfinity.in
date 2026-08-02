<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InDispatchChild extends Model
{
    protected $table = 'indispatchchild';
    protected $primaryKey = 'ID';

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'Dispatch',
        'SourceType',
        'RollSize',
        'RequiredGramMeter',
        'FabricColor',
        'RollNumber',
        'IsActive',
        'CreatedBy',
        'UpdatedBy',
    ];

    protected $casts = [
        'IsActive' => 'boolean',
        'CreatedOn' => 'datetime',
        'UpdatedOn' => 'datetime',
    ];

    public function dispatchRelation()
    {
        return $this->belongsTo(InDispatch::class, 'Dispatch', 'ID');
    }

    public function rollSizeRelation()
    {
        return $this->belongsTo(RollSize::class, 'RollSize', 'ID');
    }

    public function fabricColorRelation()
    {
        return $this->belongsTo(FabricColor::class, 'FabricColor', 'ID');
    }
}
