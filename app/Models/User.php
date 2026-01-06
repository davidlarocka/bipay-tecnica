<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'saldo'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function sentTransactions()
    {
        return $this->hasMany(Transaction::class, 'from_user_id');
    }

    public function receivedTransactions()
    {
        return $this->hasMany(Transaction::class, 'to_user_id');
    }


}

