<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asesor extends Model
{
    protected $guarded = [];

    protected $casts = [
        'layanan_satuan_pendidikan' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
