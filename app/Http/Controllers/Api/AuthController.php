<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserNotificationSetting;
use App\Models\VerificationCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::whereEmail($request->email)->first();

        if($user){

            if(is_null($user->email_verified_at)){
                return response()->json([
                    'status' => 404,
                    "msg" => "User not verified",
                    "data" => null,
                ],404);
            }

            if(Hash::check($request->password, $user->password)){
                return response()->json([
                    'status' => 200,
                    "msg" => "User is Exist",
                    "data" => [
                        'user' => $user,
                    ],
                ]);
            }
            
            return response()->json([
                'status' => 401,
                "msg" => "password is wrong",
                "data" => null,
            ],401);
        }

        return response()->json([
            'status' => 401,
            "msg" => "Unauthorized",
            "data" => null,
        ],401);
        
    }
    
    function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:191',
            'email' => 'required|email|unique:users,email',
            'mobile' => 'required|string',
            'password' => 'required|min:6|confirmed',
            'accept_policy' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::whereEmail($request->email)->first();

        if($user){
            return response()->json([
                'status' => 404,
                "msg" => "User Already Exist",
                "data" => [
                    'user' => $user,
                ],
            ],404);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'password' => $request->password,
            'accept_policy' => ($request->accept_policy >= 1 ) ? 1 : 0,
            'role_id' => 3,
            'status' => 'Not Assigned',
        ]);

        $code = random_int(1000, 9999);;
        VerificationCode::create([
            'email' => $user->email,
            'code' => $code,
            'verify_to' => Carbon::now()->addMinutes(15),
        ]);

        Mail::send('emails.verification', ['code' => $code], function ($message) use ($user) {
            $message->to($user->email);
            $message->subject('Email Verification Code');
        });

        $this->add_user_notification_settings($user->id);

        $this->add_notification($user->id , 1);

        return response()->json([
            'status' => 200,
            "msg" => "User registered successfully.",
            "data" => [
                'user' => $user,
            ],
        ],200);
        
    }

    function forget_password(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|exists:users,email',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::whereEmail($request->email)->first();

        if($user){
            $code = random_int(1000, 9999);;
            VerificationCode::create([
                'email' => $user->email,
                'code' => $code,
                'verify_to' => Carbon::now()->addMinutes(15),
            ]);

            Mail::send('emails.verification', ['code' => $code], function ($message) use ($user) {
                $message->to($user->email);
                $message->subject('Email Verification Code');
            });

            return response()->json([
                'status' => 200,
                "msg" => "Send Code Successfully",
                "data" => [
                    'user' => $user,
                    'code' => $code,
                ],
            ]);
        }else{
            return response()->json([
                'status' => 404,
                "msg" => "Email does not Exist",
                "data" => null,
            ],404);
        }
    }

    function resend_code(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|exists:users,email',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $verificationCode = VerificationCode::where('email',$request->email)->first();
        $user = User::whereEmail($request->email)->first();
        if($verificationCode){
            $verificationCode->delete();   
        }

        $code = random_int(1000, 9999);;
        $verificationCode = VerificationCode::create([
            'email' => $request->email,
            'code' => $code,
            'verify_to' => Carbon::now()->addMinutes(15),
        ]);

        Mail::send('emails.verification', ['code' => $code], function ($message) use ($request) {
            $message->to($request->email);
            $message->subject('Email Verification Code');
        });

        return response()->json([
            'status' => 200,
            "msg" => "Resend Code Successfully",
            "data" => [
                'user' => $user,
                'code' => $code,
                'verification' => $verificationCode,
            ],
        ]);
    }

    function confirm_code(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();
        if(!$user){
            return response()->json([
                'status' => 404,
                "msg" => "User not Exist",
                "data" => null,
            ],404);
        }

        $verificationCode = VerificationCode::where('email', $request->email)
            ->where('code', $request->code)
            ->with('user')
            ->first();

        if (!$verificationCode) {
            return response()->json([
                'status' => 400,
                "msg" => "Invalid Verification Code, Please request another code.",
                "data" => null,
            ],400);
        }

        if (!is_null($verificationCode->verify_to) && ($verificationCode->verify_to < Carbon::now())) {
            $verificationCode->delete();
            return response()->json([
                'status' => 400,
                "msg" => "Expired Verification Code.",
                "data" => null,
            ],400);
        }

        $verificationCode->delete();
        
        $user->update([
            'email_verified_at' => Carbon::now(),
        ]);
        
        return response()->json([
            'status' => 200,
            "msg" => "Verification Code Successfully!",
            "data" => [
                'user' => $user,
            ],
        ]);
    }

    function reset_password(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::whereEmail($request->email)->first();

        if($user){
            $user->update([
                'password' => $request->password,
            ]);

            return response()->json([
                'status' => 200,
                "msg" => "Update Password Successfully",
                "data" => [
                    'user' => $user,
                ],
            ]);
        }else{
            return response()->json([
                'status' => 401,
                "msg" => "Unauthorized",
                "data" => null,
            ],401);
        }
    }

    private function add_user_notification_settings($user_id) {
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

    private function add_notification($user_id,$notification_setting_id) {
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
    
    private function get_notifications($user_id) {

        $sql = "
            SELECT
                user_notification_settings.id,
                users.id AS user_id,
                users.name,
                users.image,
                notification_settings.title
            FROM
                user_notification_settings
            INNER JOIN notification_settings ON notification_settings.id = user_notification_settings.notification_setting_id
            INNER JOIN notifications ON notifications.notification_setting_id = notification_settings.id
            INNER JOIN users ON users.id = notifications.user_id
            WHERE
                user_notification_settings.user_id = ? AND user_notification_settings.status = 1;
        ";
        $notifications = DB::select($sql,[$user_id]);

        if($notifications){
            return $notifications;
        }
        return response()->json([
            'status' => 404,
            "msg" => "Not have any notification",
            "data" => null,
        ],404);
    }
}
