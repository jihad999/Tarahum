<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function notifications(){
        return $this->hasMany(Notification::class, 'notification_setting_id', 'id');
    }
    
}
