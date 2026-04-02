<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Absence extends Model
{
    protected $fillable = ['user_id', 'date', 'type', 'note'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
