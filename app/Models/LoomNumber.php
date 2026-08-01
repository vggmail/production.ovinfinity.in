<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoomNumber extends Model
{
    protected $table = 'umloomnumber';
    protected $primaryKey = 'ID';

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'LoomNumber',
        'LoomType',
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
