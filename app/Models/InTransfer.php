<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InTransfer extends Model
{
    protected $table = 'intransfer';
    protected $primaryKey = 'ID';

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'EntryDate',
        'PartyName',
        'TotalRolls',
        'IsActive',
        'CreatedBy',
        'UpdatedBy',
    ];

    protected $casts = [
        'IsActive' => 'boolean',
        'CreatedOn' => 'datetime',
        'UpdatedOn' => 'datetime',
    ];

    public function partyRelation()
    {
        return $this->belongsTo(Party::class, 'PartyName', 'ID');
    }

    public function children()
    {
        return $this->hasMany(InTransferChild::class, 'Transfer', 'ID');
    }
}
