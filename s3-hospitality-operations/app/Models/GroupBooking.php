<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupBooking extends Model
{
    protected $fillable = [
        'group_code',
        'group_name',
        'contact_name',
        'contact_email',
        'contact_phone',
        'check_in_date',
        'check_out_date',
        'status',
        'room_count',
    ];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'check_out_date' => 'date',
        ];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
