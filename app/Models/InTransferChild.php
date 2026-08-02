<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InTransferChild extends Model
{
    protected $table = 'intransferchild';
    protected $primaryKey = 'ID';

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'Transfer',
        'SourceType',
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

    public function transferRelation()
    {
        return $this->belongsTo(InTransfer::class, 'Transfer', 'ID');
    }

    public function transactionRelation()
    {
        return $this->belongsTo(InTransaction::class, 'RollNumber', 'RollNumber');
    }
}
