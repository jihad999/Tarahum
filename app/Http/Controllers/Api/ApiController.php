<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Friend;
use App\Models\Orphan;
use App\Models\OrphanSponser;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\PaymentInformation;
use App\Models\Plan;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use App\Models\VerificationCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
            if($user->role_id ==1 ){
                $sponsers = User::where('role_id',3)->where('orphan_id',null)->get();
                return response()->json([
                    'status' => 200,
                    "msg" => "Home",
                    "data" => [
                        'user' => $user,
                        'sponsers' => $sponsers,
                    ],
                ]);

            }elseif($user->role_id ==2){
                $user = User::whereId($request->user_id)->with('orphan.sponser')->first();
                return response()->json([
                    'status' => 200,
                    "msg" => "Home",
                    "data" => [
                        'user' => $user,
                    ],
                ]);
            }elseif($user->role_id ==3){
                $user = User::whereId($request->user_id)->with('orphan.guardian')->first();
                return response()->json([
                    'status' => 200,
                    "msg" => "Home",
                    "data" => [
                        'user' => $user,
                    ],
                ]);
            }
        }
        return response()->json([
            'status' => 404,
            "msg" => "User is not exist",
            "data" => null,
        ]);
    }

    // Tarhum 
    function get_orphans(Request $request)  {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string|exists:orphans,status',
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
            'image' => 'nullable|min:3|max:191|mimes:png,jpg,jpeg,svg',
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
            ]);
        }

        $guardian = User::where('id',$request->guardian_id)->where('role_id',2)->where('orphan_id',null)->first();
        
        if(!$guardian){
            return response()->json([
                'status' => 404,
                "msg" => "Guardian has an orphan",
                "data" => null,
            ]);
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
            'image' => $request->image??null,
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
        ]);
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
            $orphan = Orphan::whereId($request->orphan_id)->whereNull('sponser_id')->first();
            
            if(!$orphan){
                return response()->json([
                    'status' => 404,
                    "msg" => "Orphan has sponser",
                    "data" => null,
                ]);
            }

            $sponser = User::whereId($request->sponser_id)->where('role_id',3)->whereNull('orphan_id')->first();

            if(is_null($sponser)){
                return response()->json([
                    'status' => 404,
                    "msg" => "sponser_id not exist",
                    "data" => null,
                ]);
            }

            if(!$sponser){
                return response()->json([
                    'status' => 404,
                    "msg" => "sponser has orphan",
                    "data" => null,
                ]);
            }
            
            if($sponser){
                $orphan->update([
                    'status' => 'Sponserd',
                    'sponser_id' => $request->sponser_id??null,
                ]);
                
                $sponser->update([
                    'status' => "Assigned",
                    'orphan_id' => $request->orphan_id??null,
                ]);

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
            ]);
        }

        return response()->json([
            'status' => 404,
            "msg" => "Orphan Not Exist",
            "data" => null,
        ]);
    }

    function get_sponsers(Request $request)  {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string|exists:users,status',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $sponsers = User::where('role_id',3);
        if(isset($request->status)){
            $sponsers = $sponsers->where('status',$request->status);
        }
        $sponsers = $sponsers->get();

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
            'status' => 200,
            "msg" => "not exist any sponser",
            "data" => null,
        ]);
    }

    function get_sponser(Request $request)  {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|string|exists:users,id',
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
        ]);
        
    }

    // Settings
    function update_image_profile(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|exists:users,id',
            'image' => 'required|min:3|max:191',
            // |mimes:png,jpg,jpeg,svg
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::whereId($request->user_id)->first();

        if($user){
            $user->update([
                'image' => $request->image,
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
        ]);
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
                ]);
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
        ]);
    }

    function manage_users(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|users|exists:users,id',
            'role_id' => 'nullable|numeric|exists:roles,id',
            'name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::whereId($request->user_id)->first();
        if($user){
            if($user->role_id == 1){
                $users = User::where('id',"<>",$user->id)->with('role');
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
            ]);
        }

        return response()->json([
            'status' => 404,
            "msg" => "The user is not exist.",
            "data" => null,
        ]);
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

            Mail::send('emails.add_user', ['password' => $password], function ($message) use ($user) {
                $message->to($user->email);
                $message->subject('Email Verification Code');
            });

            return response()->json([
                'status' => 200,
                "msg" => "User added successfully. Check your email for get password.",
                "data" => [
                    'user' => $user,
                ],
            ]);
        }
        return response()->json([
            'status' => 200,
            "msg" => "User Already Exist",
            "data" => [
                'user' => $user,
            ],
        ]);
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
            'status' => 200,
            "msg" => "not exist any roles",
            "data" => null,
        ]);
    }

    function get_notifications(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::whereId($request->user_id)->with('notification_settings')->first();

        if($user){
            return [
                'status' => 404,
                'msg' => 'User not exist',
                'data' => [
                    'notifications' => $user->notification_settings??null,
                ],
            ];
        }
        return [
            'status' => 404,
            'msg' => 'User not exist',
            'data' => null,
        ];
    }

    // Payments
    function add_plan_payment(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|exists:users,id',
            'plan_id' => 'required|numeric|exists:plans,id',
            // 'price' => 'required|numeric|exists:plans,price',
            'billing_ship_address' => 'required|numeric',
            'card_type' => 'required|string|min:3|max:191',
            'card_name' => 'required|string|min:3|max:191',
            'card_number' => 'required|numeric',
            'expired_date' => 'required',
            'cvv' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        // expired_date
        $sponser = User::whereId($request->user_id)->where('role_id',3)->with(['orphan'])->first();

        if($sponser){
            $plan = Plan::whereId($request->plan_id)->first();
            $payment = Payment::updateOrCreate([
                'sponser_id' => $sponser->id,
            ],[
                'sponser_id' => $sponser->id,
                'plan_id' => $request->plan_id,
                'card_type' => $request->card_type,
                'card_name' => $request->card_name,
                'card_number' => $request->card_number,
                'expired_date' => $request->expired_date,
                'cvv' => $request->cvv,
                'billing_ship_address' => $request->billing_ship_address ? 1 : 0,
            ]);
            $payment_info = PaymentDetail::updateOrCreate([
                'payment_id' => $payment->id,
            ],[
                'payment_id' => $payment->id,
                'price' => $plan->price,
            ]);
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
        ]);
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
                PaymentDetail::where('payment_id',$sponser->payment->id)->orderByDesc('id')->first()->update([
                    'status' => $request->status,
                ]);
        
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
                "msg" => "The sponser doesn't have payments",
                "data" => [
                    'sponser' => $sponser,
                ],
            ]);
            
        } 
        return response()->json([
            'status' => 404,
            "msg" => "Sponser is not Exist",
            "data" => null,
        ]);
    }
    
    function get_plan_with_details(Request $request)  {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|exists:users,id',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $sponser = User::whereId($request->user_id)->where('role_id',3)->with(['payment.plan','payment.payment_info'])->first();
        // dd($sponser);
        if($sponser){
            if(is_null($sponser->payment)){
                return response()->json([
                    'status' => 404,
                    "msg" => "Sponser doesn't have any payment",
                    "data" => null,
                ]);
            }

            if(is_null($sponser->payment->plan)){
                return response()->json([
                    'status' => 404,
                    "msg" => "Sponser doesn't have any plan",
                    "data" => null,
                ]);
            }

            if(is_null($sponser->payment->payment_info)){
                return response()->json([
                    'status' => 404,
                    "msg" => "Sponser doesn't have payment history",
                    "data" => null,
                ]);
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
        ]);
    }

    // Posts
    function add_post(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|exists:users,id',
            'orphan_id' => 'nullable|numeric|exists:orphans,id',
            'description' => 'required|string|min:3|max:500',
            'image' => 'required|min:3|max:191',
            // |mimes:png,jpg,jpeg,svg
            'title' => 'nullable|min:3|max:191|string',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $post = Post::create([
            'description' => $request->description??null,
            'user_id' => $request->user_id??null,
            'orphan_id' => $request->orphan_id??null,
            'image' => $request->image??null,
            'title' => $request->title??null,
        ]);

        if($post){
            $user = User::whereId($request->user_id)->first();
            $orphan = User::whereId($request->orphan_id)->first();

            if($user){
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
        ]);

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
        ]);

    }

    function update_post(Request $request) {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|numeric|exists:posts,id',
            'orphan_id' => 'nullable|numeric|exists:orphans,id',
            'description' => 'required|string|min:3|max:500',
            'image' => 'required|min:3|max:191',
            // |mimes:png,jpg,jpeg,svg
            'title' => 'nullable|min:3|max:191|string',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $post = Post::whereId($request->post_id)->with('user')->first();
        $post->update([
            'description' => $request->description??null,
            'image' => $request->image??null,
            'title' => $request->title??null,
        ]);

        if($post){
            
            // dd($post);
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
        ]);

    }

    function get_posts(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|numeric|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::where('id',$request->user_id)->with('posts')->first();
        if($user){
            return response()->json([
                'status' => 200,
                "msg" => "Get Posts",
                "data" => [
                    'user' => $user??null,
                    'posts' => $user->posts??null,
                ],
            ]);
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
        ]);

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
        ]);
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
        ]);
    }
    
    function add_plan(Request $request) {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:3|max:191',
            'description' => 'required|string|min:3|max:500',
            'price' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $plan = Plan::create([
            'title' => $request->title??null,
            'description' => $request->description??null,
            'price' => $request->price??null,
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
        ]);
    }

    function update_plan(Request $request) {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|numeric|exists:plans,id',
            'title' => 'required|string|min:3|max:191',
            'description' => 'required|string|min:3|max:500',
            'price' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        Plan::whereId($request->plan_id)->update([
            'title' => $request->title??null,
            'description' => $request->description??null,
            'price' => $request->price??null,
        ]);

        $plan = Plan::whereId($request->plan_id)->first();

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
        ]);
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
        ]);
    }



    // function add_sponser_friends(Request $request) {
    //     $validator = Validator::make($request->all(), [
    //         'user_id' => 'required|numeric|exists:users,id',
    //         'sponser_id' => 'required|numeric|exists:users,id',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json($validator->errors(), 422);
    //     }

    //     $user = User::whereId($request->user_id)->with(['friends'])->first();

    //     if($user){
    //         $sponser = User::whereId($request->sponser_id)->where('role_id',3)->first();
    //         if($sponser){
    //             Friend::updateOrCreate([
    //                 'user_id' => $user->id,
    //                 'sponser_id' => $sponser->id,
    //             ],[
    //                 'user_id' => $user->id,
    //                 'sponser_id' => $sponser->id,
    //             ]);

    //             return response()->json([
    //                 'status' => 200,
    //                 "msg" => "Successfully added friend",
    //                 "data" => [
    //                     'user' => $user,
    //                     'friends' => $user->friends,
    //                 ],
    //             ]);
    //         }
            
    //         return response()->json([
    //             'status' => 404,
    //             "msg" => "Sponser Not Found",
    //             "data" => null,
    //         ]);
    //     }

    //     return response()->json([
    //         'status' => 404,
    //         "msg" => "User Not Found",
    //         "data" => null,
    //     ]);
    // }

    // function get_sponser_orphan(Request $request) {
    //     $validator = Validator::make($request->all(), [
    //         'user_id' => 'required|numeric|exists:users,id',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json($validator->errors(), 422);
    //     }

    //     $sponser = User::whereId($request->user_id)->with(['orphan.guardian.posts'])->first();

    //     if($sponser){

    //         if(is_null($sponser->orphan_id)){
    //             return response()->json([
    //                 'status' => 404,
    //                 "msg" => "Sponser must have orphan",
    //                 "data" => [
    //                     'sponser' => $sponser,
    //                 ],
    //             ]);
    //         }
    //         return response()->json([
    //             'status' => 200,
    //             "msg" => "Get Orphan",
    //             "data" => [
    //                 'sponser' => $sponser,
    //                 'orphan' => $sponser->orphan,
    //             ],
    //         ]);
    //     }
        
    //     return response()->json([
    //         'status' => 404,
    //         "msg" => "Sponser is not Exist",
    //         "data" => null,
    //     ]);
    // }

    // Guardian
    // function get_guardian_sponser(Request $request) {
    //     $validator = Validator::make($request->all(), [
    //         'user_id' => 'required|numeric|exists:users,id',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json($validator->errors(), 422);
    //     }

    //     $guardian = User::whereId($request->user_id)->where('role_id',2)->with(['orphan.sponser','orphan.posts'])->first();

    //     if($guardian){

    //         if(is_null($guardian->orphan_id)){
    //             return response()->json([
    //                 'status' => 404,
    //                 "msg" => "Guardian must have orphan",
    //                 "data" => [
    //                     'guardian' => $guardian,
    //                 ],
    //             ]);
    //         }

    //         return response()->json([
    //             'status' => 200,
    //             "msg" => "Get sponser",
    //             "data" => [
    //                 'guardian' => $guardian,
    //                 'orphan' => $guardian->orphan,
    //                 'sponser' => $guardian->orphan->sponser,
    //             ],
    //         ]);
    //     }
        
    //     return response()->json([
    //         'status' => 404,
    //         "msg" => "guardian is not Exist",
    //         "data" => null,
    //     ]);
    // }

    // function update_notification(Request $request) {
    //     $validator = Validator::make($request->all(), [
    //         'notification_setting_id' => 'required|numeric|exists:notification_settings,id',
    //         'user_id' => 'required|numeric|exists:users,id',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json($validator->errors(), 422);
    //     }
        
    //     $user = User::whereId($request->user_id)->with('notification_settings')->first();

    //     if($user){
    //         return [
    //             'status' => 404,
    //             'msg' => 'User not exist',
    //             'data' => [
    //                 'notifications' => $user->notification_settings??null,
    //             ],
    //         ];
    //     }
    //     return [
    //         'status' => 404,
    //         'msg' => 'User not exist',
    //         'data' => null,
    //     ];
    // }
    
    // function get_guardians()  {
        
    //     $guardians = User::where('role_id',2)->get();
        
    //     return response()->json([
    //         'status' => 200,
    //         "msg" => "Get guardians",
    //         "data" => [
    //             'guardians' => $guardians,
    //         ],
    //     ]);
    // }

    // function get_guardian(Request $request)  {
    //     $validator = Validator::make($request->all(), [
    //         'user_id' => 'nullable|string|exists:users,id',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json($validator->errors(), 422);
    //     }
    //     $guardian = User::where('role_id',2)->where('id',$request->user_id)->first();
        
    //     return response()->json([
    //         'status' => 200,
    //         "msg" => "Get guardians",
    //         "data" => [
    //             'guardian' => $guardian,
    //         ],
    //     ]);
    // }

    // function user_info(Request $request) {
    //     // payment history
    //     $validator = Validator::make($request->all(), [
    //         'user_id' => 'required|numeric|exists:users,id',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json($validator->errors(), 422);
    //     }

    //     $user = User::whereId($request->user_id)->where('role_id',3)->with(['friends','role','payment.plan','payment.payment_info'])->first();

    //     if($user){
    //         return response()->json([
    //             'status' => 200,
    //             "msg" => "Get User Info",
    //             "data" => [
    //                 'user' => $user,
    //                 'friends' => $user->friends,
    //                 'role' => $user->role,
    //                 'plan' => $user->payment->plan,
    //                 'payment_info' => $user->payment->payment_info,
    //             ],
    //         ]);
    //     }

    //     return response()->json([
    //         'status' => 404,
    //         "msg" => "Sponser not Found",
    //         "data" => null,
    //     ]);
    // }

}
