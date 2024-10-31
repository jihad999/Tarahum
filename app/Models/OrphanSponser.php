<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrphanSponser extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function sponser(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function orphan(){
        return $this->belongsTo(Orphan::class, 'orphan_id', 'id');
    }

}
