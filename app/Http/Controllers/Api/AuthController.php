<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Friend;
use App\Models\Notification;
use App\Models\Orphan;
use App\Models\OrphanSponser;
use App\Models\Post;
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
            "msg" => "Unauthorized",
            "data" => null,
        ]);
        
    }
    
    function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:191',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'accept_policy' => 'required|numeric',
            'role_id' => 'nullable|numeric|exists:roles,id',
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
            ]);
        }else{
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'accept_policy' => ($request->accept_policy >= 1 ) ? 1 : 0,
                'role_id' => 3,
                'status' => 'Assigned',
            ]);

            $this->add_user_notification_settings($user->id);

            $this->add_notification($user->id , 1);

            return response()->json([
                'status' => 201,
                "msg" => "User registered successfully. Check your email for the verification code.",
                "data" => [
                    'user' => $user,
                ],
            ]);
        }
    }

    function forget_password(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::whereEmail($request->email)->first();

        if($user){
            $code = Str::random(4);
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
                ],
            ]);
        }else{
            return response()->json([
                'status' => 404,
                "msg" => "Email does not Exist",
                "data" => null,
            ]);
        }
    }

    function confirm_code(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
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
            ]);
        }

        if (!is_null($verificationCode->verify_to) && ($verificationCode->verify_to < Carbon::now())) {

            $verificationCode->delete();

            return response()->json([
                'status' => 400,
                "msg" => "Expired Verification Code.",
                "data" => null,
            ]);
        }

        $verificationCode->delete();

        return response()->json([
            'status' => 200,
            "msg" => "Verification Code Successfully!",
            "data" => [
                'user' => $verificationCode->user,
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
            ]);
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
                        'status' => 0,
                    ]);
                }
            }
        }
        // dd($user);
    }

    private function add_notification($user_id,$notification_setting_id) {
        $notification = Notification::create([
            'user_id' => $user_id,
            'notification_setting_id' => $notification_setting_id,
        ]);

        if($notification){
            return [
                'status' => 200,
                'msg' => 'Add Notification Successfully',
                'data' => [
                    'user_notification' => $user_notification->notification_settings??null,
                ],
            ];
        }

        return [
            'status' => 404,
            'msg' => 'Add Notification Faild',
            'data' => null,
        ];
        
    }
}
