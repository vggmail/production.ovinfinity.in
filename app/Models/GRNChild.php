<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GRNChild extends Model
{
    protected $table = 'ingrnchild';
    protected $primaryKey = 'ID';

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'GRN',
        'PI',
        'PIChild',
        'ItemMaster',
        'Quantity',
        'CreatedBy',
        'UpdatedBy',
    ];

    protected $casts = [
        'Quantity' => 'float',
        'CreatedOn' => 'datetime',
        'UpdatedOn' => 'datetime',
    ];

    public function grnRelation()
    {
        return $this->belongsTo(GRN::class, 'GRN', 'ID');
    }

    public function piRelation()
    {
        return $this->belongsTo(PI::class, 'PI', 'ID');
    }

    public function piChildRelation()
    {
        return $this->belongsTo(PIChild::class, 'PIChild', 'ID');
    }

    public function itemMasterRelation()
    {
        return $this->belongsTo(ItemMaster::class, 'ItemMaster', 'ID');
    }
}
