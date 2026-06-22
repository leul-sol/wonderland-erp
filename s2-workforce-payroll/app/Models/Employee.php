<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'employee_number',
        'full_name',
        'email',
        'department_id',
        'job_title',
        'base_salary',
        'default_role',
        'status',
        'hire_date',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'hire_date' => 'date',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function payrollLines(): HasMany
    {
        return $this->hasMany(PayrollLine::class);
    }
}
