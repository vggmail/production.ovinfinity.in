<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialIssueChild extends Model
{
    protected $table = 'inmaterialissuechild';
    protected $primaryKey = 'ID';

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'MaterialIssue',
        'LoomNumber',
        'Department',
        'ItemMaster',
        'Quantity',
        'IsActive',
        'CreatedBy',
        'UpdatedBy',
    ];

    protected $casts = [
        'Quantity' => 'float',
        'IsActive' => 'boolean',
        'CreatedOn' => 'datetime',
        'UpdatedOn' => 'datetime',
    ];

    public function materialIssueRelation()
    {
        return $this->belongsTo(MaterialIssue::class, 'MaterialIssue', 'ID');
    }

    public function loomRelation()
    {
        return $this->belongsTo(LoomNumber::class, 'LoomNumber', 'ID');
    }

    public function departmentRelation()
    {
        return $this->belongsTo(Department::class, 'Department', 'ID');
    }

    public function itemMasterRelation()
    {
        return $this->belongsTo(ItemMaster::class, 'ItemMaster', 'ID');
    }
}
