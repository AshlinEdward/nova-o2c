<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'code',
        'name',
        'gstin',
        'state',
        'invoice_prefix',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
