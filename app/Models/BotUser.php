<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotUser extends Model
{
    protected $fillable = [
        'chat_id',
        'first_name',
        'second_name',
        'company_name',
        'uname',
        'phone',
        'step',
        'is_active',
        'lang',
    ];

    protected $attributes = [
        'lang' => null,
        'step' => 'start'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
