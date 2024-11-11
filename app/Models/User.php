<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function role(){
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function orphan(){
        return $this->belongsTo(orphan::class, 'orphan_id', 'id');
    }

    public function friends(){
        return $this->belongsToMany('App\Models\User', 'friends' ,'user_id' , 'sponser_id');
    }

    public function sponser_orphans(){
        return $this->belongsToMany('App\Models\Orphan', 'orphan_sponsers' ,'sponser_id' , 'orphan_id');
    }

    public function payment(){
        return $this->hasOne(Payment::class, 'sponser_id', 'id');
    }
    
    public function posts(){
        return $this->hasMany(Post::class, 'user_id', 'id');
    }

    public function notification_settings(){
        return $this->hasMany(NotificationSetting::class, 'role_id', 'role_id');
    }

    public function user_notifications(){
        return $this->hasMany(UserNotificationSetting::class, 'user_id', 'id');
    }

    public function notifications(){
        return $this->hasMany(Notification::class, 'notification_setting_id', 'id');
    }
}
