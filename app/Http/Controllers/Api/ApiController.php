<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\AddPaymentMonthlyJob;
use App\Mail\AddUserEmail;
use App\Models\Notification;
use App\Models\NotificationSetting;
use App\Models\Orphan;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\Plan;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotificationSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class ApiController extends Controller
{

    function home(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::whereId($request->user_id)->first();
        if($user){
            $notifications = get_notifications($user->id);

            if($user->role_id ==1 ){
                $sponsers = User::where('role_id',3)->where('orphan_id',null)->orderByDesc('created_at')->get();
                return response()->json([
                    'status' => 200,
                    "msg" => "Home",
                    "data" => [
                        'user' => $user,
                        'sponsers' => $sponsers,
                        'notifications' => $notifications??null,
                    ],
                ]);

            }elseif($user->role_id ==2){
                $user = User::whereId($request->user_id)->with('orphan.sponser')->first();
                return response()->json([
                    'status' => 200,
                    "msg" => "Home",
                    "data" => [
                        'user' => $user,
                        'notifications' => $notifications??null,
                    ],
                ]);
            }elseif($user->role_id ==3){
                $user = User::whereId($request->user_id)->with('orphan.guardian')->first();
                return response()->json([
                    'status' => 200,
                    "msg" => "Home",
                    "data" => [
                        'user' => $user,
                        'notifications' => $notifications??null,
                    ],
                ]);
            }
        }
        return response()->json([
            'status' => 404,
            "msg" => "User is not exist",
            "data" => null,
        ],404);
    }

    // Tarhum 
    function get_orphans(Request $request)  {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $orphans = Orphan::query();
        if($request->status){
            $orphans = $orphans->where('status',$request->status);
        }
        $orphans = $orphans->get();

        return response()->json([
            'status' => 200,
            "msg" => "Get Orphans",
            "data" => [
                'orphans' => $orphans,
            ],
        ]);
    }

    function add_orphan(Request $request)  {
        $validator = Validator::make($request->all(), [
            'guardian_id' => 'required|numeric|exists:users,id',
            'first_name' => 'required|string|min:3|max:191',
            'last_name' => 'required|string|min:3|max:191',
            'location' => 'required|string|min:3|max:191',
            'about' => 'required|string|min:3|max:500',
            'date' => 'required|date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'age' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $guardian = User::where('id',$request->guardian_id)->where('role_id',2)->first();

        if(!$guardian){
            return response()->json([
                'status' => 404,
                "msg" => "Guardian not exist",
                "data" => null,
            ],404);
        }

        $guardian = User::where('id',$request->guardian_id)->where('role_id',2)->where('orphan_id',null)->first();
        
        if(!$guardian){
            return response()->json([
                'status' => 404,
                "msg" => "Guardian has an orphan",
                "data" => null,
            ],404);
        }
        
        $imageName = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '-' . $image->getClientOriginalName();
            $publicPath = public_path('images');
            $image->move($publicPath, $imageName);
        }

        $orphan = Orphan::updateOrCreate([
            'first_name' => $request->first_name??null,
            'last_name' => $request->last_name??null,
        ],[
            'guardian_id' => $request->guardian_id??null,
            'first_name' => $request->first_name??null,
            'last_name' => $request->last_name??null,
            'location' => $request->location??null,
            'about' => $request->about??null,
            'date' => $request->date??null,
            'image' => $imageName??null,
            'age' => $request->age??null,
            'status' => 'Not Sponserd',
        ]);

        
        $guardian = $guardian->update([
            'orphan_id' => $orphan->id,
        ]);

        if($orphan){
            return response()->json([
                'status' => 200,
                "msg" => "Orphan Added Successfully",
                "data" => [
                    'orphan' => $orphan,
                ],
            ]);
        }

        return response()->json([
            'status' => 404,
            "msg" => "Orphan Added Faild",
            "data" => null,
        ],404);
        
    }

    function update_orphan(Request $request)  {
        $validator = Validator::make($request->all(), [
            'orphan_id' => 'required|numeric|exists:users,id',
            'first_name' => 'nullable|string|min:3|max:191',
            'last_name' => 'nullable|string|min:3|max:191',
            'location' => 'nullable|string|min:3|max:191',
            'about' => 'nullable|string|min:3|max:500',
            'date' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'age' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $orphan = Orphan::where('id',$request->orphan_id)->first();

        if(!$orphan){
            return response()->json([
                'status' => 404,
                "msg" => "Orphan not exist",
                "data" => null,
            ],404);
        }

        $imageName = $orphan->image??null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '-' . $image->getClientOriginalName();
            $publicPath = public_path('images');
            $image->move($publicPath, $imageName);
        }

        $orphan->update([
            'first_name' => $request->first_name??$orphan->first_name,
            'last_name' => $request->last_name??$orphan->last_name,
            'location' => $request->location??$orphan->location,
            'about' => $request->about??$orphan->about,
            'date' => $request->date??$orphan->date,
            'image' => $imageName??$orphan->image,
            'age' => $request->age??$orphan->age,
        ]);
        

        return response()->json([
            'status' => 200,
            "msg" => "Orphan updated Successfully",
            "data" => [
                'orphan' => $orphan,
            ],
        ]);
        
    }

    function get_orphan(Request $request) {
        $validator = Validator::make($request->all(), [
            'orphan_id' => 'required|numeric|exists:orphans,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $orphan = Orphan::whereId($request->orphan_id)->with(['guardian','sponser','posts'])->first();

        if($orphan){
            return response()->json([
                'status' => 200,
                "msg" => "Get Orphan Info",
                "data" => [
                    'orphan' => $orphan,
                    'guardian' => $orphan->guardian,
                    'sponser' => $orphan->sponser,
                    'posts' => $orphan->posts,
                ],
            ]);
        }
        return response()->json([
            'status' => 404,
            "msg" => "Orphan not Found",
            "data" => null,
        ],404);
    }

    function sponser_assign_orphan(Request $request) {
        $validator = Validator::make($request->all(), [
            'orphan_id' => 'required|numeric|exists:orphans,id',
            'sponser_id' => 'required|numeric|exists:users,id',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $orphan = Orphan::whereId($request->orphan_id)->first();
        
        if($orphan){
            $orphan = Orphan::whereId($request->orphan_id)->whereNull('sponser_id')->with('guardian')->first();
            
            if(!$orphan){
                return response()->json([
                    'status' => 404,
                    "msg" => "Orphan has sponser",
                    "data" => null,
                ],404);
            }

            $sponser = User::whereId($request->sponser_id)->where('role_id',3)->whereNull('orphan_id')->first();

            if(is_null($sponser)){
                return response()->json([
                    'status' => 404,
                    "msg" => "sponser_id not exist",
                    "data" => null,
                ],404);
            }

            if(!$sponser){
                return response()->json([
                    'status' => 404,
                    "msg" => "sponser has orphan",
                    "data" => null,
                ],404);
            }
            
            if($sponser){
                $sponser->update([
                    'status' => "Assigned",
                    'orphan_id' => $request->orphan_id??null,
                ]);
                add_notification($sponser->id , 7);

                $orphan->update([
                    'status' => 'Sponserd',
                    'sponser_id' => $request->sponser_id??null,
                ]);
                add_notification($orphan->guardian->id , 5);
                
                return response()->json([
                    'status' => 200,
                    "msg" => "Assigned Success",
                    "data" => [
                        'orphan' => $orphan,
                        'sponser' => $sponser,
                        'guardian' => $orphan->guardian??null,
                    ],
                ]);
            }

            return response()->json([
                'status' => 404,
                "msg" => "Sponser Not Exist",
                "data" => [
                    'orphan' => $orphan,
                    'guardian' => $orphan->guardian??null,
                ],
            ],404);
        }

        return response()->json([
            'status' => 404,
            "msg" => "Orphan Not Exist",
            "data" => null,
        ],404);
    }

    function get_sponsers(Request $request)  {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $sponsers = User::where('role_id',3);
        if(isset($request->status)){
            $sponsers = $sponsers->where('status',$request->status);
        }
        $sponsers = $sponsers->orderByDesc('created_at')->get();

        if($sponsers){
            return response()->json([
                'status' => 200,
                "msg" => "Get Sponsers",
                "data" => [
                    'sponsers' => $sponsers,
                ],
            ]);
        }

        return response()->json([
            'status' => 404,
            "msg" => "not exist any sponser",
            "data" => null,
        ],404);
    }

    function get_sponser(Request $request)  {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $sponser = User::where('role_id',3)->where('id',$request->user_id)->with(['payment.payment_info','orphan.guardian.posts'])->first();
        
        if($sponser){
            return response()->json([
                'status' => 200,
                "msg" => "Get sponser",
                "data" => [
                    'sponser' => $sponser,
                    'orphan' => $sponser->orphan,
                    'payment_history' => $sponser->payment?->payment_info??null,
                ],
            ]);
        }
        return response()->json([
            'status' => 404,
            "msg" => "Sponser not Exist",
            "data" => null,
        ],404);
        
    }

    // Settings
    function update_image_profile(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|exists:users,id',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::whereId($request->user_id)->first();

        if($user){
            
            $imageName = null;
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '-' . $image->getClientOriginalName();
                $publicPath = public_path('images');
                $image->move($publicPath, $imageName);
            }
                
            $user->update([
                'image' => $imageName,
            ]);

            return response()->json([
                'status' => 200,
                "msg" => "Update Image Profile Successfully",
                "data" => [
                    'user' => $user,
                ],
            ]);
        }
        return response()->json([
            'status' => 404,
            "msg" => "User not found",
            "data" => null,
        ],404);
    }

    function update_my_account(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|exists:users,id',
            'lang' => 'required|string',
            'current_password' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::whereId($request->user_id)->first();

        if($user){
            if(!Hash::check($request->current_password, $user->password)){
                return response()->json([
                    'status' => 404,
                    "msg" => "Current Password is Wrong!",
                    "data" => null,
                ],404);
            }

            $user->update([
                'password' => $request->password,
                'lang' => $request->lang,
            ]);

            return response()->json([
                'status' => 200,
                "msg" => "Update my account Successfully",
                "data" => [
                    'user' => $user,
                ],
            ]);
        }
        return response()->json([
            'status' => 404,
            "msg" => "Update my account Failed",
            "data" => null,
        ],404);
    }

    function manage_users(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role_id' => 'nullable|numeric|exists:roles,id',
            'name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::whereId($request->user_id)->where('role_id',1)->first();
        
        if($user){
            if($user->role_id == 1){
                $users = User::where('id',"<>",$user->id);
                if(isset($request->role_id) && !is_null($request->role_id)){
                    $users = $users->where('role_id',$request->role_id);
                }
                if(isset($request->name) && !is_null($request->name)){
                    $users = $users->where('name',"LIKE","%".$request->name."%");
                }
                $users = $users->with('role')->get();
                return response()->json([
                    'status' => 200,
                    "msg" => "List users",
                    "data" => [
                        'users' => $users,
                    ],
                ]);
            }
            return response()->json([
                'status' => 404,
                "msg" => "Not have a permissions",
                "data" => null,
            ],404);
        }

        return response()->json([
            'status' => 404,
            "msg" => "The user is not exist or Not have a tarahum permissions.",
            "data" => null,
        ],404);
    }

    function add_user(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:191',
            'email' => 'required|email|unique:users,email',
            'role_id' => 'required|numeric|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::whereEmail($request->email)->first();

        if(!$user){
            $password = Str::random(6);
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $password,
                'accept_policy' => 1,
                'role_id' => $request->role_id??3,
                'status' => $request->role_id == 3 ? "Not Assigned" : null,
            ]);

            add_user_notification_settings($user->id);

            if($user->role_id == 3){
                add_notification($user->id , 1);
            }elseif ($user->role_id == 2) {
                add_notification($user->id , 4);
            }

            Mail::to($user->email)->send(new AddUserEmail('Welcome to Tarahum! Here is your password',$user->email,$password));

            return response()->json([
                'status' => 200,
                "msg" => "User added successfully. Check your email for get password.",
                "data" => [
                    'password' => $password,
                    'user' => $user,
                ],
            ],);
        }
        return response()->json([
            'status' => 404,
            "msg" => "User Already Exist",
            "data" => [
                'user' => $user,
            ],
        ],404);
    }

    function update_user(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|exists:users,id',
            'name' => 'nullable|string',
            'mobile' => 'nullable|string',
            'location' => 'nullable|string',
            'about' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::whereId($request->user_id)->first();

        if($user){
            $user->update([
                'name' => $request->name??$user->name,
                'mobile' => $request->mobile??$user->mobile,
                'location' => $request->location??$user->location,
                'about' => $request->about??$user->about,
            ]);

            return response()->json([
                'status' => 200,
                "msg" => "User Updated successfully.",
                "data" => [
                    'user' => $user,
                ],
            ],);
        }

        return response()->json([
            'status' => 404,
            "msg" => "User Not Exist",
            "data" => null,
        ],404);
    }

    function get_roles() {
        $roles = Role::get();
        if($roles){
            return response()->json([
                'status' => 200,
                "msg" => "Get Roles",
                "data" => [
                    'roles' => $roles,
                ],
            ]);
        }
        return response()->json([
            'status' => 404,
            "msg" => "not exist any roles",
            "data" => null,
        ],404);
    }

    function get_user_notification_settings(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::whereId($request->user_id)->with(['user_notifications.notification_settings'])->first();

        if($user){
            if($user->user_notifications && count($user->user_notifications)>0){
                return response()->json([
                    'status' => 200,
                    'msg' => 'User not exist',
                    'data' => [
                        'user' => $user,
                        'notification_settings' => $user->user_notifications,
                    ],
                ]);
            }
            return response()->json([
                'status' => 404,
                'msg' => 'User not have notification settings',
                'data' => [
                    'user' => $user,
                ],
            ],404);
        }
        return response()->json([
            'status' => 404,
            'msg' => 'User not exist',
            'data' => null,
        ],404);

    }

    function update_user_notifications(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|exists:users,id',
            'notification_setting_id' => 'required|numeric|exists:notification_settings,id',
            'status' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::whereId($request->user_id)->with(['user_notifications'])->first();

        if($user){            
            if($user->user_notifications && count($user->user_notifications)>0){
                $notification_setting = NotificationSetting::where('id',$request->notification_setting_id)->where('role_id',$user->role_id)->first();

                if($notification_setting){
                    $user->user_notifications->where('notification_setting_id',$request->notification_setting_id)->first()->update(['status'=>($request->status) ? 1 : 0]);
                    return response()->json([
                        'status' => 200,
                        'msg' => 'update notification successfully',
                        'data' => [
                            'user' => $user??null,
                            'notifications' => $user->user_notifications??null,
                        ],
                    ]);
                }
                return response()->json([
                    'status' => 404,
                    'msg' => 'notification setting id not compataple with user',
                    'data' => [
                        'user' => $user??null,
                        'notification_setting_id' => $request->notification_setting_id??null,
                    ],
                ],404);
            }

            return response()->json([
                'status' => 404,
                'msg' => 'User not have a notifications',
                'data' => [
                    'user' => $user??null,
                ],
            ],404);
        }
        return response()->json([
            'status' => 404,
            'msg' => 'User not exist',
            'data' => null,
        ],404);
    }

    // Payments
    function add_plan_payment(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|exists:users,id',
            'plan_id' => 'required|numeric|exists:plans,id',
            'billing_ship_address' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $sponser = User::whereId($request->user_id)->where('role_id',3)->with(['orphan'])->first();

        if($sponser){
            $plan = Plan::whereId($request->plan_id)->first();
            $payment = Payment::updateOrCreate([
                'sponser_id' => $sponser->id,
            ],[
                'sponser_id' => $sponser->id,
                'plan_id' => $request->plan_id,
                'billing_ship_address' => $request->billing_ship_address ? 1 : 0,
                'status' => 1,
            ]);

            $payment_info = PaymentDetail::create([
                'payment_id' => $payment->id,
                'price' => $plan->price,
            ]);

            AddPaymentMonthlyJob::dispatch($payment->id);
            add_notification($sponser->id , 1);

            return response()->json([
                'status' => 200,
                "msg" => "Add sponser payment",
                "data" => [
                    'sponser' => $sponser,
                    'payment' => $payment,
                    'payment_info' => $payment_info,
                ],
            ]);
        }

        return response()->json([
            'status' => 404,
            "msg" => "Sponser is not Exist",
            "data" => null,
        ],404);
    }
    
    function update_payment_status(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|exists:users,id',
            'status' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $sponser = User::whereId($request->user_id)->where('role_id',3)->with(['payment.payment_info'])->first();

        if($sponser){
            if($sponser->payment){
                $payment_detail = PaymentDetail::where('payment_id',$sponser->payment->id)->orderByDesc('id')->first();
                $payment_detail->update([
                    'status' => $request->status,
                ]);

                if($payment_detail){
                    if($payment_detail->status == "Paid"){
                        add_notification($sponser->id , 2);
                        add_notification($sponser->id , 8);
                    }elseif($payment_detail->status == "Declined"){
                        add_notification($sponser->id , 3);
                    }
            
                    return response()->json([
                        'status' => 200,
                        "msg" => "Update status payment for sponser Successfully",
                        "data" => [
                            'sponser' => $sponser,
                            'payment' => $sponser->payment,
                            'payment_info' => $sponser->payment->payment_info,
                        ],
                    ]);
                }
                return response()->json([
                    'status' => 404,
                    "msg" => "The sponser doesn't have payment details",
                    "data" => [
                        'sponser' => $sponser,
                        'payment' => $sponser->payment,
                    ],
                ]);
                
            }

            return response()->json([
                'status' => 404,
                "msg" => "The sponser doesn't have payments",
                "data" => [
                    'sponser' => $sponser,
                ],
            ],404);
            
        } 
        return response()->json([
            'status' => 404,
            "msg" => "Sponser is not Exist",
            "data" => null,
        ],404);
    }
    
    function get_plan_with_details(Request $request)  {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|exists:users,id',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $sponser = User::whereId($request->user_id)->where('role_id',3)->with(['payment.plan','payment.payment_info'])->first();

        if($sponser){
            if(is_null($sponser->payment)){
                return response()->json([
                    'status' => 404,
                    "msg" => "Sponser doesn't have any payment",
                    "data" => null,
                ],404);
            }

            if(is_null($sponser->payment->plan)){
                return response()->json([
                    'status' => 404,
                    "msg" => "Sponser doesn't have any plan",
                    "data" => null,
                ],404);
            }

            if(is_null($sponser->payment->payment_info)){
                return response()->json([
                    'status' => 404,
                    "msg" => "Sponser doesn't have payment history",
                    "data" => null,
                ],404);
            }

            return response()->json([
                'status' => 200,
                "msg" => "Get payment details for sponsers",
                "data" => [
                    'user'=> $sponser??null,
                    'payment'=> $sponser->payment??null,
                    'plan'=> $sponser->payment->plan??null,
                    'payment_info'=> $sponser->payment->payment_info??null
                ],
            ]);
        }
        return response()->json([
            'status' => 404,
            "msg" => "Sponser is not Exist",
            "data" => null,
        ],404);
    }

    function cancel_plan(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $sponser = User::whereId($request->user_id)->where('role_id',3)->with(['orphan','payment.plan','payment.payment_info'])->first();

        if($sponser){
            if($sponser->orphan){
                $sponser->orphan->update([
                    'sponser_id' => null,
                    'status' => 'Not Sponserd',
                ]);
            }
            $sponser->update([
                'orphan_id' => null,
                'status' => 'Not Assigned',
            ]);

            if($sponser->payment){
                Payment::where('sponser_id',$sponser->id)->update([
                    'status' => 0,
                ]);
                // if(count($sponser->payment->payment_info)>0 && $sponser->payment->payment_info){
                //     foreach ($sponser->payment->payment_info as $payment_info) {
                //         PaymentDetail::where('payment_id',$payment_info->id)->delete();
                //     }
                // }
                return response()->json([
                    'status' => 200,
                    "msg" => "Sponser cancel plan successfully",
                    "data" => [
                        'sponser' => $sponser,
                    ],
                ]);
            }
            
            return response()->json([
                'status' => 200,
                "msg" => "Not have any payment",
                "data" => [
                    'sponser' => $sponser,
                ],
            ]);
        }

        return response()->json([
            'status' => 404,
            "msg" => "Sponser is not Exist",
            "data" => null,
        ],404);
    }

    // Posts
    function add_post(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|exists:users,id',
            'orphan_id' => 'nullable|numeric|exists:orphans,id',
            'description' => 'required|string|min:3|max:500',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'title' => 'nullable|min:3|max:191|string',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        
        $imageName = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '-' . $image->getClientOriginalName();
            $publicPath = public_path('images');
            $image->move($publicPath, $imageName);
        }

        $post = Post::create([
            'description' => $request->description??null,
            'user_id' => $request->user_id??null,
            'orphan_id' => $request->orphan_id??null,
            'image' => $imageName??null,
            'title' => $request->title??null,
        ]);

        if($post){
            $user = User::whereId($request->user_id)->first();

            if(($user->role_id == 1) && is_null($request->orphan_id)){
                add_notification($user->id , 6);
            }

            $orphan = User::whereId($request->orphan_id)->first();

            if($orphan){
                return response()->json([
                    'status' => 200,
                    "msg" => "Publish your post successfully",
                    "data" => [
                        'post' => $post,
                        'user' => $user,
                        'orphan' => $orphan??null,
                    ],
                ]);
            }

            return response()->json([
                'status' => 200,
                "msg" => "Publish your update successfully",
                "data" => [
                    'post' => $post,
                ],
            ]);
            
        }
        return response()->json([
            'status' => 404,
            "msg" => "Publish your post not successfully",
            "data" => null,
        ],404);

    }

    function get_post(Request $request) {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|numeric|exists:posts,id',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $post = Post::whereId($request->post_id)->with('user')->first();

        if($post){
            return response()->json([
                'status' => 200,
                "msg" => "Get Post",
                "data" => [
                    'post' => $post??null,
                    'user' => $post->user??null,
                ],
            ]);
        }
        return response()->json([
            'status' => 404,
            "msg" => "The post not found",
            "data" => null,
        ],404);

    }

    function update_post(Request $request) {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|numeric|exists:posts,id',
            'orphan_id' => 'nullable|numeric|exists:orphans,id',
            'description' => 'nullable|string|min:3|max:500',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'title' => 'nullable|min:3|max:191|string',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $post = Post::whereId($request->post_id)->with('user')->first();
        
        $imageName = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '-' . $image->getClientOriginalName();
            $publicPath = public_path('images');
            $image->move($publicPath, $imageName);
        }
        
        $post->update([
            'description' => $request->description??$post->description,
            'image' => $imageName??$post->image,
            'title' => $request->title??$post->title,
        ]);

        if($post){
            return response()->json([
                'status' => 200,
                "msg" => "Publish your update successfully",
                "data" => [
                    'post' => $post,
                    'user' => $post->user??null,
                ],
            ]);
        }
        return response()->json([
            'status' => 404,
            "msg" => "Edit your update not successfully",
            "data" => null,
        ],404);

    }

    function get_posts(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|numeric|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $users = User::where('role_id',1)->with(['posts'])->get();

        $tarahum_posts = [];
        foreach ($users as $user) {
            foreach ($user->posts as $post) {
                if(is_null($post->orphan_id)){
                    $tarahum_posts[] = $post;
                }
            }
        }
        $tarahum_posts = collect($tarahum_posts);

        $user = User::where('id',$request->user_id)->first();
        
        if($user){
            $posts = Post::where('user_id',$user->id)->where('orphan_id',null)->get();
            $posts = $posts->merge($tarahum_posts);
            if($posts && count($posts)>0){
                return response()->json([
                    'status' => 200,
                    "msg" => "Get Posts",
                    "data" => [
                        'user' => $user??null,
                        'posts' => $posts??null,
                    ],
                ]);
            }
            return response()->json([
                'status' => 200,
                "msg" => "Not have posts",
                "data" => [
                    'user' => $user??null,
                ],
            ]);
        }

        if($tarahum_posts){
            return response()->json([
                'status' => 200,
                "msg" => "Get Posts",
                "data" => [
                    'posts' => $tarahum_posts??null,
                ],
            ]);
        }
        
        return response()->json([
            'status' => 404,
            "msg" => "The User doesn't have any post",
            "data" => null,
        ],404);

    }

    function delete_post(Request $request) {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|numeric|exists:posts,id',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $post = Post::whereId($request->post_id)->delete();

        if($post){
            return response()->json([
                'status' => 200,
                "msg" => "Delete Post Successfully",
                "data" => null,
            ]);
        }
        return response()->json([
            'status' => 404,
            "msg" => "Delete Post faild",
            "data" => null,
        ],404);
    }

    // Plans
    function get_plans() {
        $plans = Plan::get();
        if($plans){
            return response()->json([
                'status' => 200,
                "msg" => "Get Plans",
                "data" => [
                    'plans' => $plans,
                ],
            ]);
        }
        return response()->json([
            'status' => 404,
            "msg" => "Not exist plans currently",
            "data" => [
                'plans' => $plans,
            ],
        ],404);
    }
    
    function add_plan(Request $request) {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:3|max:191',
            'description' => 'required|string|min:3|max:500',
            'price' => 'required|numeric',
            'duration_day' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $plan = Plan::create([
            'title' => $request->title??null,
            'description' => $request->description??null,
            'price' => $request->price??null,
            'duration_day' => $request->duration_day??null,
        ]);

        if($plan){
            return response()->json([
                'status' => 200,
                "msg" => "Add Plan Successfully",
                "data" => [
                    'plan' => $plan,
                ],
            ]);
        }
        return response()->json([
            'status' => 404,
            "msg" => "Add plan faild",
            "data" => null,
        ],404);
    }

    function update_plan(Request $request) {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|numeric|exists:plans,id',
            'title' => 'nullable|string|min:3|max:191',
            'description' => 'nullable|string|min:3|max:500',
            'price' => 'nullable|numeric',
            'duration_day' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $plan = Plan::whereId($request->plan_id)->first();

        $plan = Plan::whereId($request->plan_id)->update([
            'title' => $request->title??$plan->title,
            'description' => $request->description??$plan->description,
            'price' => $request->price??$plan->price,
            'duration_day' => $request->duration_day??$plan->duration_day,
        ]);

        

        if($plan){
            return response()->json([
                'status' => 200,
                "msg" => "Update Plan Successfully",
                "data" => [
                    'plan' => $plan,
                ],
            ]);
        }
        return response()->json([
            'status' => 404,
            "msg" => "Update plan faild",
            "data" => null,
        ],404);
    }

    function delete_plan(Request $request) {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|numeric|exists:plans,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $plan = Plan::whereId($request->plan_id)->delete();

        if($plan){
            return response()->json([
                'status' => 200,
                "msg" => "Delete Plan Successfully",
                "data" => null,
            ]);
        }
        return response()->json([
            'status' => 404,
            "msg" => "Delete plan faild",
            "data" => null,
        ],404);
    }

    function get_guardians() {
        $guardians = User::where('role_id',2)->where('orphan_id',null)->get();
        return response()->json([
            'status' => 200,
            "msg" => "Get guardians",
            "data" => [
                'guardians' => $guardians,
            ],
        ]);
    }

    function get_guardian(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $guardian = User::where('role_id',2)->where('id',$request->user_id)->first();
        
        if($guardian){
            return response()->json([
                'status' => 200,
                "msg" => "Get guardian",
                "data" => [
                    'guardian' => $guardian,
                ],
            ]);
        }

        return response()->json([
            'status' => 404,
            "msg" => "Guardian not exist",
            "data" => [
                'guardian' => $guardian,
            ],
        ],404);
    }

    function user_info(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::whereId($request->user_id)->where('role_id',3)->with(['friends','role','payment.plan','payment.payment_info'])->first();

        if($user){
            return response()->json([
                'status' => 200,
                "msg" => "Get User Info",
                "data" => [
                    'user' => $user,
                    'friends' => $user->friends,
                    'role' => $user->role,
                    'plan' => $user->payment->plan,
                    'payment_info' => $user->payment->payment_info,
                ],
            ]);
        }

        return response()->json([
            'status' => 404,
            "msg" => "Sponser not Found",
            "data" => null,
        ]);
    }

    function request_payment(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $sponser = User::where('role_id',3)->where('id',$request->user_id)->first();
        if($sponser){
            if(!is_null($sponser->orphan_id)){
                add_notification($sponser->id,9);
                return response()->json([
                    'status' => 200,
                    "msg" => "Sucssess request payment",
                    "sponser" => [
                        'sponser' => $sponser,
                    ],
                ]);
            }
            return response()->json([
                'status' => 400,
                "msg" => "No orphan assigned yet",
                "sponser" => [
                    'sponser' => $sponser,
                ],
            ]);
            
        }
        return response()->json([
            'status' => 404,
            "msg" => "Faild request payment",
            "data" => null,
        ],404);
    }

}
