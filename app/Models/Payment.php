<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function payment_info(){
        return $this->hasMany(PaymentDetail::class, 'payment_id', 'id');
    }

    public function plan(){
        return $this->belongsTo(Plan::class, 'plan_id', 'id');
    }

    public function sponser(){
        return $this->hasOne(User::class, 'id', 'sponser_id');
    }

}
