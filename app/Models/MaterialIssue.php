<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialIssue extends Model
{
    protected $table = 'inmaterialissue';
    protected $primaryKey = 'ID';

    const CREATED_AT = 'CreatedOn';
    const UPDATED_AT = 'UpdatedOn';

    protected $fillable = [
        'IssueNo',
        'IssueDate',
        'Technician',
        'TotalItems',
        'TotalQuantity',
        'Remarks',
        'IsActive',
        'CreatedBy',
        'UpdatedBy',
    ];

    protected $casts = [
        'IssueDate' => 'date:Y-m-d',
        'TotalItems' => 'integer',
        'TotalQuantity' => 'float',
        'IsActive' => 'boolean',
        'CreatedOn' => 'datetime',
        'UpdatedOn' => 'datetime',
    ];

    protected $appends = [
        'departments',
    ];

    public function technicianRelation()
    {
        return $this->belongsTo(Technician::class, 'Technician', 'ID');
    }

    public function children()
    {
        return $this->hasMany(MaterialIssueChild::class, 'MaterialIssue', 'ID');
    }

    public function getDepartmentsAttribute()
    {
        if (!$this->relationLoaded('children')) {
            return [];
        }

        $names = [];
        foreach ($this->children as $child) {
            if ($child->departmentRelation && $child->departmentRelation->DepartmentName) {
                $names[] = $child->departmentRelation->DepartmentName;
            } elseif ($child->itemMasterRelation && $child->itemMasterRelation->departmentRelation && $child->itemMasterRelation->departmentRelation->DepartmentName) {
                $names[] = $child->itemMasterRelation->departmentRelation->DepartmentName;
            }
        }

        return array_values(array_unique(array_filter($names)));
    }
}
