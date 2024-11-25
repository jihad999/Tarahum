<?php

use App\Models\Notification;
use App\Models\User;
use App\Models\UserNotificationSetting;
use Illuminate\Support\Facades\DB;

function add_user_notification_settings($user_id) {
    $user = User::where('id',$user_id)->with(['notification_settings'])->first();
    
    if($user){
        if(count($user->notification_settings)>0 && $user->notification_settings){
            foreach ($user->notification_settings as $notification_setting) {
                UserNotificationSetting::updateOrCreate([
                    'user_id' => $user->id,
                    'notification_setting_id' => $notification_setting->id,
                ],[
                    'user_id' => $user->id,
                    'notification_setting_id' => $notification_setting->id,
                    'status' => 1,
                ]);
            }
        }
    }
}

function add_notification($user_id,$notification_setting_id) {
    $notification = Notification::create([
        'user_id' => $user_id,
        'notification_setting_id' => $notification_setting_id,
    ]);

    if($notification){
        return response()->json([
            'status' => 200,
            'msg' => 'Add Notification Successfully',
            'data' => [
                'user_notification' => $user_notification->notification_settings??null,
            ],
        ]);
    }

    return response()->json([
        'status' => 404,
        'msg' => 'Add Notification Faild',
        'data' => null,
    ],404);
    
}

function get_notifications($user_id) {
    $user = User::whereId($user_id)->first();
    
    // $sql = "
    //     SELECT
    //         user_notification_settings.id,
    //         users.id AS user_id,
    //         users.name,
    //         users.image,
    //         notification_settings.title,
    //         notifications.created_at
    //     FROM
    //         user_notification_settings
    //     INNER JOIN notification_settings ON notification_settings.id = user_notification_settings.notification_setting_id
    //     INNER JOIN notifications ON notifications.notification_setting_id = notification_settings.id
    //     INNER JOIN users ON users.id = notifications.user_id
    //     WHERE
    //         user_notification_settings.user_id = ? AND user_notification_settings.status= 1 AND notifications.created_at >= users.created_at
    //     Order By
    //         notifications.created_at DESC;
    // ";
    $sql_user_not_tarahum = "";
    if($user->role_id != 1){
        $sql_user_not_tarahum = " AND notifications.user_id = $user_id ";
    }

    $sql = "
        SELECT
            notifications.id,
            users.id AS user_id,
            users.name,
            users.image,
            notification_settings.title,
            notifications.created_at
        FROM
            user_notification_settings
        INNER JOIN notification_settings ON notification_settings.id = user_notification_settings.notification_setting_id
        INNER JOIN notifications ON notifications.notification_setting_id = notification_settings.id
        INNER JOIN users ON users.id = notifications.user_id
        WHERE
            user_notification_settings.user_id = ? AND user_notification_settings.status = 1 AND notifications.created_at >= users.created_at $sql_user_not_tarahum
        UNION
        SELECT
            notifications.id,
            users.id AS user_id,
            users.name,
            users.image,
            notification_settings.title,
            notifications.created_at
        FROM
            notification_settings
        INNER JOIN notifications ON notifications.notification_setting_id = notification_settings.id AND notifications.user_id = ?
        INNER JOIN users ON users.id = notifications.user_id
        WHERE
            notifications.created_at >= users.created_at AND notification_settings.role_id IS NULL $sql_user_not_tarahum
        ORDER BY
            created_at
        DESC
            ;
    ";
    
    $notifications = DB::select($sql,[$user_id , $user_id]);
    if($notifications){
        return $notifications;
    }

    return ;

    
}