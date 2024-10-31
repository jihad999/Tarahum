<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Friend;
use App\Models\Orphan;
use App\Models\OrphanSponser;
use App\Models\Post;
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
                'role_id' => $request->role_id??3,
            ]);

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

    function update_image_profile(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'image' => 'required|min:3|max:191|mimes:png,jpg,jpeg,svg',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::whereEmail($request->email)->first();

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
        }else{
            return response()->json([
                'status' => 404,
                "msg" => "Update Image is Failed",
                "data" => null,
            ]);
        }
    }

    function my_account(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'lang' => 'required|string',
            'current_password' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::whereEmail($request->email)->first();

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
                "msg" => "Update Profile Successfully",
                "data" => [
                    'user' => $user,
                ],
            ]);
        }else{
            return response()->json([
                'status' => 404,
                "msg" => "Update Image is Failed",
                "data" => null,
            ]);
        }
    }

    function add_user(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:191',
            'email' => 'required|email|unique:users,email',
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
            $password = Str::random(6);
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $password,
                'accept_policy' => 1,
                'role_id' => $request->role_id??3,
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
    }

    function manage_users(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'role_id' => 'nullable|numeric|exists:roles,id',
            'name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::whereEmail($request->email)->first();
        $users = User::where('id',"<>",$user->id)->with('role');

        if(isset($request->role_id) && !is_null($request->role_id)){
            $users = $users->where('role_id',$request->role_id);
        }
        if(isset($request->name) && !is_null($request->name)){
            $users = $users->where('name',"LIKE","%".$request->name."%");
        }

        $users = $users->get();

        if($user){

            if($user->role_id == 1){
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
                "msg" => "You are not Tarahum Permissions",
                "data" => null,
            ]);

        }else{
            return response()->json([
                'status' => 404,
                "msg" => "The user is not exist.",
                "data" => null,
            ]);
        }
    }

    function get_orphans()  {
        $orphans = Orphan::with(['sponsers','guardian'])->get();
        return response()->json([
            'status' => 200,
            "msg" => "Get Orphans",
            "data" => [
                'orphans' => $orphans,
            ],
        ]);
    }

    function get_orphan()  {
        $guardians = User::where('role_id',2)->get();
        return response()->json([
            'status' => 200,
            "msg" => "Get Guardians",
            "data" => [
                'guardians' => $guardians,
            ],
        ]);
    }

    function get_sponsers()  {
        $sponsers = User::where('role_id',2)->get();
        return response()->json([
            'status' => 200,
            "msg" => "Get Sponsers",
            "data" => [
                'sponsers' => $sponsers,
            ],
        ]);
    }

    function get_trahums()  {
        $trahums = User::where('role_id',2)->get();
        return response()->json([
            'status' => 200,
            "msg" => "Get Trahums",
            "data" => [
                'trahums' => $trahums,
            ],
        ]);
    }

    function store_orphan(Request $request)  {
        $validator = Validator::make($request->all(), [
            'sponser_id' => 'required|numeric|exists:users,id',
            'guardian_id' => 'required|numeric|exists:users,id',
            'first_name' => 'required|string|min:3|max:191',
            'last_name' => 'required|string|min:3|max:191',
            'location' => 'required|string|min:3|max:191',
            'about' => 'required|string|min:3|max:500',
            'date' => 'required|date',
            'image' => 'required|min:3|max:191|mimes:png,jpg,jpeg,svg',
            'age' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $orphan = Orphan::updateOrCreate([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
        ],[
            'sponser_id' => $request->sponser_id,
            'guardian_id' => $request->guardian_id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'location' => $request->location,
            'about' => $request->about,
            'date' => $request->date,
            'image' => $request->image,
            'age' => $request->age,
            'status' => 'Not Assigned',
        ]);

        return response()->json([
            'status' => 200,
            "msg" => "Orphan Added Successfully",
            "data" => [
                'orphan' => $orphan,
            ],
        ]);
    }

    function orphan_info(Request $request) {
        $validator = Validator::make($request->all(), [
            'orphan_id' => 'required|numeric|exists:orphans,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $orphan = Orphan::whereId($request->orphan_id)->with(['guardian','sponsers','updates'])->first();

        if($orphan){
            return response()->json([
                'status' => 200,
                "msg" => "Get Orphan Info",
                "data" => [
                    'orphan' => $orphan,
                    'guardian' => $orphan->guardian,
                    'sponsers' => $orphan->sponsers,
                    'updates' => $orphan->updates,
                ],
            ]);
        }
        return response()->json([
            'status' => 404,
            "msg" => "Orphan not Found",
            "data" => null,
        ]);
    }

    function assign_sponser(Request $request) {
        $validator = Validator::make($request->all(), [
            'orphan_id' => 'required|numeric|exists:orphans,id',
            'sponser_id' => 'required|numeric|exists:users,id',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $orphan_sponser = OrphanSponser::updateOrCreate([
            'orphan_id' => $request->orphan_id,
            'sponser_id' => $request->sponser_id,
        ],[
            'orphan_id' => $request->orphan_id,
            'sponser_id' => $request->sponser_id,
        ]);

        if($orphan_sponser){
            $orphan = Orphan::whereId($request->orphan_id)->update([
                'status' => 'Assigned',
            ]);
            $orphan = $orphan->with(['guardian','sponsers'])->first();
            return response()->json([
                'status' => 200,
                "msg" => "Assigned Success",
                "data" => [
                    'orphan' => $orphan,
                    'sponsers' => $orphan->sponsers,
                    'guardian' => $orphan->guardian??null,
                ],
            ]);
        }
        return response()->json([
            'status' => 404,
            "msg" => "Assigned Fail",
            "data" => null,
        ]);
    }

    function store_update(Request $request) {
        $validator = Validator::make($request->all(), [
            'orphan_id' => 'required|numeric|exists:users,id',
            'description' => 'required|string|min:3|max:500',
            'image' => 'required|min:3|max:191|mimes:png,jpg,jpeg,svg',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $post = Post::create([
            'description' => $request->description??null,
            'orphan_id' => $request->orphan_id??null,
            'post_type' => 'update',
            'image' => $request->image??null,
        ]);

        if($post){
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
            "msg" => "Publish your update not successfully",
            "data" => null,
        ]);

    }

    function home(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $user = User::whereId($request->user_id)->with(['role','friends'])->first();

        if($user){
            $sponsers = User::where('users.role_id',3)->whereNotIn('users.id',[$user->id])
            ->leftJoin('friends', function($join)use($user){
                $join->on('friends.sponser_id', 'users.id');
                $join->orOn('friends.user_id', 'users.id');
            })
            ->dd();
//             SELECT users.* from users
// LEFT JOIN friends ON friends.user_id = users.id OR friends.sponser_id = users.id
// WHERE users.role_id = 3 AND users.id NOT IN (6)
// GROUP BY users.id;
            return response()->json([
                'status' => 200,
                "msg" => "Get User",
                "data" => [
                    'user' => $user,
                    'friends' => $user->friends,
                    'sponser_friends' => $sponsers,
                ],
            ]);
        }
        return response()->json([
            'status' => 404,
            "msg" => "User is not exist",
            "data" => null,
        ]);
    }
}
