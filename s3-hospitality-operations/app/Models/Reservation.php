<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reservation extends Model
{
    protected $fillable = [
        'guest_id',
        'confirmation_code',
        'guest_name',
        'guest_email',
        'guest_phone',
        'room_type_id',
        'room_id',
        'check_in_date',
        'check_out_date',
        'quoted_rate',
        'total_nights',
        'status',
        'adults',
        'notes',
        'created_by',
        'checked_in_at',
        'checked_out_at',
        'group_booking_id',
    ];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'check_out_date' => 'date',
            'quoted_rate' => 'decimal:2',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
        ];
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(GuestProfile::class, 'guest_id');
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function folio(): HasOne
    {
        return $this->hasOne(Folio::class);
    }

    public function groupBooking(): BelongsTo
    {
        return $this->belongsTo(GroupBooking::class);
    }
}
