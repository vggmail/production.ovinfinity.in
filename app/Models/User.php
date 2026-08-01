<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'auusercredentials';
    protected $primaryKey = 'UserCredID';

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'UserCode',
        'FullName',
        'UserName',
        'Password',
        'ContactNo',
        'EmailId',
        'Address',
        'City',
        'IsActive',
        'CreatedBy',
        'CreatedOn',
        'UpdatedBy',
        'UpdatedOn'
    ];

    protected $hidden = [
        'Password',
        'remember_token',
    ];

    protected $casts = [
        'CreatedOn' => 'datetime',
        'UpdatedOn' => 'datetime',
        'IsActive' => 'boolean'
    ];

    /**
     * Override default password column name for Laravel Auth.
     */
    public function getAuthPassword()
    {
        return $this->Password;
    }
}
