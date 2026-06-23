<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuestProfile extends Model
{
    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'id_document_type',
        'id_document_number',
        'nationality',
        'address',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'guest_id');
    }

    public function folios(): HasMany
    {
        return $this->hasMany(Folio::class, 'guest_id');
    }
}
