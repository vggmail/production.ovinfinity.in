<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Technician extends Model
{
    protected $table = 'umtechnician';
    protected $primaryKey = 'ID';

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'Name',
        'Code',
        'Phone',
        'IsActive',
        'CreatedBy',
        'UpdatedBy',
    ];

    protected $casts = [
        'IsActive' => 'boolean',
        'CreatedOn' => 'datetime',
        'UpdatedOn' => 'datetime',
    ];
}
