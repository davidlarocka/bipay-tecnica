<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'amount',
    ];

    /**
     * Relación: usuario que envía la transacción.
     * Retorna el modelo User asociado a from_user_id.
     */
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

     /**
     * Relación: usuario que recibe la transacción.
     * Retorna el modelo User asociado a to_user_id.
     */
    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
