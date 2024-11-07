<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Orphan extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function guardian(){
        return $this->belongsTo(User::class, 'guardian_id', 'id');
    }

    public function sponser(){
        return $this->belongsTo(User::class, 'sponser_id', 'id');
    }
    
    // public function sponsers(){
    //     return $this->belongsToMany('App\Models\User', 'orphan_sponsers' ,'orphan_id' , 'sponser_id');
    // }

    public function posts(){
        return $this->hasMany(Post::class, 'orphan_id', 'id');
    }

}
