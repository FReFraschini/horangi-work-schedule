<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnavailabilityRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'preference',
        'status',
        'note',
        'archived',
    ];

    protected $casts = [
        'date'     => 'date:Y-m-d',
        'archived' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
