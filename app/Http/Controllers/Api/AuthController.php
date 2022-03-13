<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\InternalUser;
use App\Models\UserRole;
use App\Models\crmApiCalls;
use App\Models\user_bank_details_history;
use App\Models\product;
use App\Models\plansOrders;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Auth;
use Image;
use App\Models\Amenitie;
use Illuminate\Support\Str;
use Twilio\Rest\Client;
use App\Models\eventtracker;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use DB;
use Illuminate\Support\Facades\Http;
use Mail;
use Illuminate\Support\Facades\Crypt;

class AuthController extends Controller
{

    /* Code added by Radhika Start */
    public function user_signup_new(Request $request)
    {
        $request->validate([
            'first_name' => 'required',
            'email' => 'required|string|unique:users',
            'other_mobile_number' => 'required|integer|unique:users',
            'selectType' => 'required',
            'agree_check' => 'required|boolean',
            'password' => 'required|string|confirmed',
			'gender' => 'required'						  
        ]);

        $token = getenv("TWILIO_AUTH_TOKEN");
        $twilio_sid = getenv("TWILIO_SID");
        $twilio_verify_sid = getenv("TWILIO_VERIFY_SID");
        $twilio = new Client($twilio_sid, $token);
        $twilio->verify->v2->services($twilio_verify_sid)
            ->verifications
            ->create("+91".$request->other_mobile_number, "sms");

        $user = new User([
            'name' => $request->first_name,
            'last_name' => $request->last_name,
			'gender' => $request->gender,							 
            'email' => $request->email,
            'usertype' => $request->selectType,
            'userSelect_type' => $request->selectType,
            'other_mobile_number' => $request->other_mobile_number,
            'internal_user' => "No",
            'password' => bcrypt($request->password)
        ]);

        $user->save();
        eventtracker::create(['symbol_code' => '1', 'event' => $request->email.' created a new account as a User']);

        return response()->json([
            'data' => $user,
            'message' => 'Successfully created user!',
            'status'  =>200
        ], 201);
    }

    /* Code added by Radhika End */
    
    public function user_signup(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|string|unique:users',
            'other_mobile_number' => 'required|integer',
            'profile_pic' => 'required',
            'password' => 'required|string|confirmed'
        ]);

        $base64_image = $request->input('profile_pic'); // your base64 encoded
        @list($type, $file_data) = explode(';', $base64_image);
        @list(, $file_data) = explode(',', $file_data);
        $imageName = 'IMAGE'.Str::random(30).'.'.'png';
        Storage::disk('public')->put('profile_image_file/'.$imageName, base64_decode($file_data));

        $token = getenv("TWILIO_AUTH_TOKEN");
        $twilio_sid = getenv("TWILIO_SID");
        $twilio_verify_sid = getenv("TWILIO_VERIFY_SID");
        $twilio = new Client($twilio_sid, $token);
        $twilio->verify->v2->services($twilio_verify_sid)
            ->verifications
            ->create("+91".$request->other_mobile_number, "sms");

        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'profile_pic' => 'profile_image_file/'.$imageName,
            'usertype' => 1,
            'other_mobile_number' => $request->other_mobile_number,
            'password' => bcrypt($request->password)
        ]);

        $user->save();
        eventtracker::create(['symbol_code' => '1', 'event' => $request->name.' created a new account as a User']);

        return response()->json([
            'data' => $user,
            'message' => 'Successfully created user!'
        ], 201);
    }
    



    public function verify_mobile_number(Request $request) {
        
         $user = User::where('id', $request['user_id'])->get();
         
        $db_mobile_no = $user[0]['other_mobile_number'];
        if($db_mobile_no === $request['other_mobile_number'])
        {
            $request->validate([ 
                'other_mobile_number' => 'required|integer'
            ]);
        }

        else {
            $request->validate([ 
                'other_mobile_number' => 'required|integer|unique:users'
            ]);
        }

        $token = getenv("TWILIO_AUTH_TOKEN");
        $twilio_sid = getenv("TWILIO_SID");
        $twilio_verify_sid = getenv("TWILIO_VERIFY_SID");
        $twilio = new Client($twilio_sid, $token);
        $twilio->verify->v2->services($twilio_verify_sid)
            ->verifications
            ->create("+91".$request->other_mobile_number, "sms");

    } 

    public function reset_password_send_otp(Request $request) {

        $data = $request->validate([
            'phone_number' => ['required', 'numeric']
        ]);

        $token = getenv("TWILIO_AUTH_TOKEN");
        $twilio_sid = getenv("TWILIO_SID");
        $twilio_verify_sid = getenv("TWILIO_VERIFY_SID");
        $twilio = new Client($twilio_sid, $token);
        $twilio->verify->v2->services($twilio_verify_sid)
            ->verifications
            ->create("+91".$data['phone_number'], "sms");

        return response()->json([
            'message' => 'OTP Sent',
            'status' => 'Success'
        ], 201);
    }

    public function reset_password_verify_otp(Request $request) {

        $data = $request->validate([
            'verification_code' => ['required', 'numeric'],
            'phone_number' => ['required', 'numeric']
        ]);

        $token = getenv("TWILIO_AUTH_TOKEN");
        $twilio_sid = getenv("TWILIO_SID");
        $twilio_verify_sid = getenv("TWILIO_VERIFY_SID");
        $twilio = new Client($twilio_sid, $token);
        $verification = $twilio->verify->v2->services($twilio_verify_sid)
            ->verificationChecks
            ->create($data['verification_code'], array('to' => "+91".$data['phone_number']));

        if ($verification->valid) {
            return response()->json([
                'message' => 'OTP Verified',
                'status' => 'Success'
            ], 201);
        }
        return response()->json([
            'message' => 'verification error'
        ], 401);
    }

    public function reset_password(Request $request) {

        $request->validate([
            'new_password' => 'required|min:6',
            'confirm_password' => 'required|same:new_password',
            'email' => 'required'			
        ]);

        User::where('email', $request->email)->update(['password' => bcrypt($request->new_password)]);

        return response()->json([
            'message' => 'Password Reset Successful',
            'status' => 'Success'
        ], 201);
    }

    public function reset_send_otp_email(Request $request) {

        $request->validate([
            'email' => 'required|email'
        ]);

        $sid = getenv("TWILIO_SID");
        $token = getenv("TWILIO_AUTH_TOKEN");
        $twilio_verify_sid = getenv("TWILIO_VERIFY_SID");
        $twilio = new Client($sid, $token);

        $verification = $twilio->verify->v2->services($twilio_verify_sid)
                                   ->verifications
                                   ->create($request->email, "email");   
        
         return response() -> json([
             'message' => 'The email has been sent'
         ], 201); 

        // $otp = random_int(100000, 999999);
        // $data = [
        //     'otp' => $otp
        //   ];

        // Mail::send('otp', $data, function($message) use ($request) {
        //     $message->from('support@housingstreet.com');
        //     $message->to($request->email)->subject('OTP Verification');
        // });

        // return response() -> json ([
        //     'message' => 'The email has been sent'
        // ], 201); 

    }

    public function rp_verify_otp_email(Request $request) {

        $data = $request->validate([
            'verification_code' => ['required', 'numeric'],
            'email' => ['required']
        ]);

        $token = getenv("TWILIO_AUTH_TOKEN");
        $twilio_sid = getenv("TWILIO_SID");
        $twilio_verify_sid = getenv("TWILIO_VERIFY_SID");
        $twilio = new Client($twilio_sid, $token);
        $verification_check = $twilio->verify->v2->services($twilio_verify_sid)
                                         ->verificationChecks
                                         ->create($data['verification_code'],
                                                  ["to" => $data['email']]
                                         );

        if ($verification_check->valid) {
            return response()->json([
                'message' => 'OTP Verified',
                'status' => 'Success'
            ], 201);
        }
        return response()->json([
            'message' => 'verification error'
        ], 401);
    }

    /* Code added by Radhika Start */

    public function internal_user_signup(Request $request)
    {
        $request->validate([
            'userName' => 'required',
            'email' => 'required|email|unique:users',
            'other_mobile_number' => 'required|integer|unique:users',
            'address1' => 'required',
            'password' => 'required',
            'userRole' => 'required'
        ]);
        
        $user = new User([
            'name' => $request->userName,
            'email' => $request->email,
            'other_mobile_number' => $request->other_mobile_number,
            'usertype' => 8,
            'address' => $request->address1,
            'address1' => $request->address2,
            'password' => bcrypt($request->password),
            'internal_user' => "Yes"
        ]);

        $user->save();
		foreach($request->userRole as $role => $value) {
            $role_id[] = $value['item_id'];
        }
        //return $role_id;
        $user->roles()->attach($role_id);												
       
        eventtracker::create(['symbol_code' => '2', 'event' => $request->user_name.' created a new Internal User Account']);


        return response()->json([
            'data' => $user,
            'message' => 'Successfully created Internal User'
        ], 201);
    }


    public function get_internal_user_details($user_id) {
        $internal_user_details =  DB::table('user_roles_pivot')->where('user_id', $user_id)->get();
        return $internal_user_details;        
    }

    public function delete_internal_user($user_id) {
        $user = User::where('id', $user_id)->first();
        $user->roles()->detach();
        $user->delete();
        return response() -> json ([
            'message' => 'The user has been deleted.'
        ]); 
    }

    public function create_user_role(Request $request){

        $request->validate([
            'role' => 'required|unique:user_roles',
            'role_id' => 'required|integer|unique:user_roles'
        ]);

        $newRoleID = uniqid() . '-' . $request->role_id;

        $role = new UserRole([
            'role' => $request->role,
            //'role_id' => $request->role_id,
            'role_id' => $newRoleID,
            'access_all_users' => $request->access_all_users,
            'access_properties' => $request->access_properties,
            //'access_requirements' => $request->access_requirements,
            'access_reviews' => $request->access_reviews,
            'access_lawyer_services' => $request->access_lawyer_services,
            'access_loan_control' => $request->access_loan_control,
            'access_user_creator' => $request->access_user_creator,
            'access_manage_blog' => $request->access_blog,
            'access_manage_roles' => $request->access_roles,
            'access_list_property' => $request->access_list_property
            
        ]);

        $role->save();
       // eventtracker::create(['symbol_code' => '2', 'event' => $request->name.' created a new User Role']);

       return response()->json([
        'data' => $role,
        'message' => 'Successfully created User Role'
    ], 201);

    }

    public function update_role(Request $request, $id) {

        UserRole::where('role_id', $id)->update([
            'access_all_users' => $request->access_all_users,
            'access_properties' => $request->access_properties,
			//'access_requirements' => $request->access_requirements,
            'access_reviews' => $request->access_reviews,
			'access_lawyer_services' => $request->access_lawyer_services,
            'access_loan_control' => $request->access_loan_control,
            'access_user_creator' => $request->access_user_creator,
            'access_manage_blog' => $request->access_manage_blog,
            'access_manage_roles' => $request->access_manage_roles,
            'access_list_property' => $request->access_list_property
        ]);

        return response() -> json ([
            'message' => 'The role details have been updated'
        ], 201);
    }

    public function delete_role($id) {

        $role = UserRole::where('role_id', $id);
        $role->delete();
        return response() -> json ([
            'message' => 'The role has been deleted.'
        ]); 
    }

    /* Code added by Radhika End */

    public function owner_signup(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|string|unique:users',
            'other_mobile_number' => 'required|integer',
            'address' => 'required',
            'pan_number' => 'required',
            'aadhar_number' => 'required',
            'profile_pic' => 'required',
            'password' => 'required|string|confirmed'
        ]);

        $base64_image = $request->input('profile_pic'); // your base64 encoded
        @list($type, $file_data) = explode(';', $base64_image);
        @list(, $file_data) = explode(',', $file_data);
        $imageName = 'IMAGE'.Str::random(30).'.'.'png';
        Storage::disk('public')->put('profile_image_file/'.$imageName, base64_decode($file_data));

        $token = getenv("TWILIO_AUTH_TOKEN");
        $twilio_sid = getenv("TWILIO_SID");
        $twilio_verify_sid = getenv("TWILIO_VERIFY_SID");
        $twilio = new Client($twilio_sid, $token);
        $twilio->verify->v2->services($twilio_verify_sid)
            ->verifications
            ->create("+91".$request->other_mobile_number, "sms");

        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'other_mobile_number' => $request->other_mobile_number,
            'address' => $request->address,
            'pan_number' => $request->pan_number,
            'aadhar_number' => $request->aadhar_number,
            'profile_pic' => 'profile_image_file/'.$imageName,
            'usertype' => 2,
            'password' => bcrypt($request->password)
        ]);

        $user->save();
        eventtracker::create(['symbol_code' => '2', 'event' => $request->name.' created a new account as a Owner']);


        return response()->json([
            'data' => $user,
            'message' => 'Successfully created owner'
        ], 201);
    }

    public function dealer_company_signup(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|string|unique:users',
            'other_mobile_number' => 'required|integer',
            'address' => 'required',
            'city' => 'required',
            'pan_number' => 'required',
            'aadhar_number' => 'required',
            'company_name' => 'required',
            'company_url' => 'required',
            'landline_number' => 'required',
            'company_profile' => 'required',
            'profile_pic' => 'required',
            'password' => 'required|string|confirmed'
        ]);

        $base64_image = $request->input('profile_pic'); // your base64 encoded
        @list($type, $file_data) = explode(';', $base64_image);
        @list(, $file_data) = explode(',', $file_data);
        $imageName = 'IMAGE'.Str::random(30).'.'.'png';
        Storage::disk('public')->put('profile_image_file/'.$imageName, base64_decode($file_data));

        $token = getenv("TWILIO_AUTH_TOKEN");
        $twilio_sid = getenv("TWILIO_SID");
        $twilio_verify_sid = getenv("TWILIO_VERIFY_SID");
        $twilio = new Client($twilio_sid, $token);
        $twilio->verify->v2->services($twilio_verify_sid)
            ->verifications
            ->create("+91".$request->other_mobile_number, "sms");

        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'other_mobile_number' => $request->other_mobile_number,
            'address' => $request->address,
            'city' => $request->city,
            'pan_number' => $request->pan_number,
            'aadhar_number' => $request->aadhar_number,
            'landline_number' => $request->landline_number,
            'company_name' => $request->company_name,
            'company_url' => $request->company_url,
            'company_profile' => $request->company_profile,
            'profile_pic' => 'profile_image_file/'.$imageName,
            'usertype' => 3,
            'password' => bcrypt($request->password)
        ]);

        $user->save();
        eventtracker::create(['symbol_code' => '3', 'event' => $request->name.' created a new account as a Dealer']);


        return response()->json([
            'data' => $user,
            'message' => 'Successfully created dealer/company'
        ], 201);
    }



    public function lawyer_signup(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|string|unique:users',
            'other_mobile_number' => 'required|integer',
            'address' => 'required',
            'city' => 'required',
            'pan_number' => 'required',
            'aadhar_number' => 'required',
            'landline_number' => 'required',
            'practice_number' =>'required',
            'law_firm_number' =>'required',
            'provided_service' =>'required',
            'place_of_practice' =>'required',
            'price_for_service' =>'required',
            'profile_pic' => 'required',
            'password' => 'required|string|confirmed'
        ]);

        $base64_image = $request->input('profile_pic'); // your base64 encoded
        @list($type, $file_data) = explode(';', $base64_image);
        @list(, $file_data) = explode(',', $file_data);
        $imageName = 'IMAGE'.Str::random(30).'.'.'png';
        Storage::disk('public')->put('profile_image_file/'.$imageName, base64_decode($file_data));

        $token = getenv("TWILIO_AUTH_TOKEN");
        $twilio_sid = getenv("TWILIO_SID");
        $twilio_verify_sid = getenv("TWILIO_VERIFY_SID");
        $twilio = new Client($twilio_sid, $token);
        $twilio->verify->v2->services($twilio_verify_sid)
            ->verifications
            ->create("+91".$request->other_mobile_number, "sms");

        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'address' => $request->address,
            'city' => $request->city,
            'pan_number' => $request->pan_number,
            'aadhar_number' => $request->aadhar_number,
            'provided_service' =>$request->provided_service,
            'place_of_practice' =>$request->place_of_practice,
            'price_for_service' =>$request->price_for_service,
            'law_firm_number' =>$request->law_firm_number,
            'practice_number' =>$request->practice_number,
            'other_mobile_number' => $request->other_mobile_number,
            'landline_number' => $request->landline_number,
            'profile_pic' => 'profile_image_file/'.$imageName,
            'usertype' => 4,
            'password' => bcrypt($request->password)
        ]);

        $user->save();
        eventtracker::create(['symbol_code' => '4', 'event' => $request->name.' created a new account as a Lawyer']);


        return response()->json([
            'data' => $user,
            'message' => 'Successfully created lawyer'
        ], 201);
    }

    public function crm_api_call(Request $request) {
        $user = User::where('id', $request['id'])->get();
    
        $request_time = now();
        $now = strtotime($request_time);
        
        $crmp_api = getenv("crmp_api");
        $response = Http::post($crmp_api, [
            'BuyerEmail' => $user[0]['email'],
            'PhoneNo' => $user[0]['other_mobile_number'],
            'BuyerName' => $user[0]['name'],
            'Source' => 'Web',
            'usertype'=> 'Existing'
        ]);
        
        $date = strtotime($response->headers()['Date'][0]);
        
        $crm_data = new crmApiCalls([
            'response_body' => $response->body(),
            'response_client_error' => $response->clientError(),
            'response_fail' => $response->failed(),
            'response_server_error' => $response->serverError(),
            'response_status' => $response->status(),
            'response_success' => $response->successful(),
            'request_time' => $request_time,
            'response_time' => date('Y-m-d H:i:s', $date),
            'user_email' => $user[0]['email'],
            'user_phone' => $user[0]['other_mobile_number'],
            'user_name' => $user[0]['name'],
            'source' => 'Web' 
        ]);

        $crm_data->save();

        return response()->json([
            'message' => 'Successfully added lead',
            'response_success' => $response->successful(),
            'response_fail' => $response->failed(),
            'response_client_error' => $response->clientError(),
            'response_server_error' => $response->serverError(),
            'response_body' => $response->body(),
            'response_json' => $response->json(),
            'response_status' => $response->status(),
            'response_headers' => date('Y-m-d H:i:s', $date),
            'response' => $response
        ], 201);
    }
    
    public function crm_call_appointment(Request $request) {
        $user = User::where('id', $request['id'])->get();
    
        $request_time = now();
        $now = strtotime($request_time);
        
        $crmp_api = getenv("crmp_api");
        $response = Http::post($crmp_api, [
            'BuyerEmail' => $user[0]['email'],
            'PhoneNo' => $user[0]['other_mobile_number'],
            'BuyerName' => $user[0]['name'],
            'Source' => 'Web',
            'Appointment'=>'Appointment Fixed',
            'AppointmentTime' => $request_time
        ]);
        
        $date = strtotime($response->headers()['Date'][0]);
        
        $crm_data = new crmApiCalls([
            'response_body' => $response->body(),
            'response_client_error' => $response->clientError(),
            'response_fail' => $response->failed(),
            'response_server_error' => $response->serverError(),
            'response_status' => $response->status(),
            'response_success' => $response->successful(),
            'request_time' => $request_time,
            'response_time' => date('Y-m-d H:i:s', $date),
            'user_email' => $user[0]['email'],
            'user_phone' => $user[0]['other_mobile_number'],
            'user_name' => $user[0]['name'],
            'source' => 'Web'
        ]);

        $crm_data->save();

        return response()->json([
            'message' => 'Successfully added lead',
            'response_success' => $response->successful(),
            'response_fail' => $response->failed(),
            'response_client_error' => $response->clientError(),
            'response_server_error' => $response->serverError(),
            'response_body' => $response->body(),
            'response_json' => $response->json(),
            'response_status' => $response->status(),
            'response_headers' => date('Y-m-d H:i:s', $date),
            'response' => $response
        ], 201);
    }

    public function verify(Request $request)
    {
        $data = $request->validate([
            'verification_code' => ['required', 'numeric'],
            'phone_number' => ['required', 'string'],
            'email_address' => ['required', 'string'],
            'name_first' => ['required', 'string']
        ]);
        /* Get credentials from .env */
        $token = getenv("TWILIO_AUTH_TOKEN");
        $twilio_sid = getenv("TWILIO_SID");
        $twilio_verify_sid = getenv("TWILIO_VERIFY_SID");
        $twilio = new Client($twilio_sid, $token);
        $verification = $twilio->verify->v2->services($twilio_verify_sid)
            ->verificationChecks
            ->create($data['verification_code'], array('to' => "+91".$data['phone_number']));

        if ($verification->valid) {
            User::where('other_mobile_number', $data['phone_number'])->update(['phone_number_verification_status' => 1]);

            $crmp_api = getenv("crmp_api");
            $response = Http::post($crmp_api, [
                'BuyerEmail' => $data['email_address'],
                'PhoneNo' => $data['phone_number'],
                'BuyerName' => $data['name_first'],
                'Source' => 'Web'
            ]);

            return response()->json([
                'message' => 'Successfully verified',
                'response_success' => $response->successful(),
                'response_fail' => $response->failed(),
                'response_client_error' => $response->clientError(),
                'response_server_error' => $response->serverError(),
                'response_body' => $response->body()
            ], 201);

        }
        return response()->json([
            'message' => 'verification error'
        ], 401);
    }

    /* Code added by Radhika Start */

    public function verify_mob(Request $request)
    {

        $data = $request->validate([
            'verification_code' => 'required|string',
            'other_mobile_number' => 'required|string',
            'user_id' => 'required|numeric'
        ]);
        /* Get credentials from .env */
        $token = getenv("TWILIO_AUTH_TOKEN");
        $twilio_sid = getenv("TWILIO_SID");
        $twilio_verify_sid = getenv("TWILIO_VERIFY_SID");
        $twilio = new Client($twilio_sid, $token);
        $verification = $twilio->verify->v2->services($twilio_verify_sid)
            ->verificationChecks
            ->create($data['verification_code'], array('to' => "+91".$data['other_mobile_number']));

        if ($verification->valid) {
            User::where('id', $data['user_id'])->update(['other_mobile_number' => $data['other_mobile_number'], 'phone_number_verification_status' => 1]);
            $user = User::where('id', $data['user_id'])->get();
            $request_time = now();
            $now = strtotime($request_time);
            $crmp_api = getenv("crmp_api");
            $response = Http::post($crmp_api, [
                'BuyerEmail' => $user[0]['email'],
                'PhoneNo' => $user[0]['other_mobile_number'],
                'BuyerName' => $user[0]['name'],
                'Source' => 'Web',
                'UserType'=>'New'
            ]);

            $date = strtotime($response->headers()['Date'][0]);

            $crm_data = new crmApiCalls([
                'response_body' => $response->body(),
                'response_client_error' => $response->clientError(),
                'response_fail' => $response->failed(),
                'response_server_error' => $response->serverError(),
                'response_status' => $response->status(),
                'response_success' => $response->successful(),
                'request_time' => $request_time,
                'response_time' => date('Y-m-d H:i:s', $date),
                'user_email' => $user[0]['email'],
                'user_phone' => $user[0]['other_mobile_number'],
                'user_name' => $user[0]['name'],
                'source' => 'Web'
            ]);
    
            $crm_data->save();

            return response()->json([
                'message' => 'Successfully verified',
                'response_success' => $response->successful(),
                'response_fail' => $response->failed(),
                'response_client_error' => $response->clientError(),
                'response_server_error' => $response->serverError(),
                'response_body' => $response->body()
            ], 201);
        }
        return response()->json([
            'message' => 'verification error'
        ], 401);
    }

    public function verify_profile_mob(Request $request)
    {
        $data = $request->validate([
            'verification_code' => 'required',
            'other_mobile_number' => 'required|string',
            'user_id' => 'required|numeric'
        ]);
        /* Get credentials from .env */
        $token = getenv("TWILIO_AUTH_TOKEN");
        $twilio_sid = getenv("TWILIO_SID");
        $twilio_verify_sid = getenv("TWILIO_VERIFY_SID");
        $twilio = new Client($twilio_sid, $token);
        $verification = $twilio->verify->v2->services($twilio_verify_sid)
            ->verificationChecks
            ->create($data['verification_code'], array('to' => "+91".$data['other_mobile_number']));

        if ($verification->valid) {
            User::where('id', $data['user_id'])->update(['other_mobile_number' => $data['other_mobile_number'], 'phone_number_verification_status' => 1]);
            return response()->json([
                'message' => 'Successfully verified'
            ], 201);
        }
        return response()->json([
            'message' => 'verification error'
        ], 401);
    }
     public function bank_verify_mobile(Request $request)
    {
        // return $request->phone_number;
       $data = $request->validate([
            'phone_number' => ['required', 'numeric']
        ]);

        $token = getenv("TWILIO_AUTH_TOKEN");
        $twilio_sid = getenv("TWILIO_SID");
        $twilio_verify_sid = getenv("TWILIO_VERIFY_SID");
        $twilio = new Client($twilio_sid, $token);
        $twilio->verify->v2->services($twilio_verify_sid)
            ->verifications
            ->create("+91".$data['phone_number'], "sms");

        return response()->json([
            'message' => 'OTP Sent',
            'status' => 'Success'
        ], 200);
    }
     public function bank_verify_OTP(Request $request)
    {
        try{
            $request->validate([
                'otp' => 'required|numeric',
            ]);
            $data=$request->data;
            $otp=$request->otp;
            /* Get credentials from .env */
            $token = getenv("TWILIO_AUTH_TOKEN");
            $twilio_sid = getenv("TWILIO_SID");
            $twilio_verify_sid = getenv("TWILIO_VERIFY_SID");
            $twilio = new Client($twilio_sid, $token);
            $verification = $twilio->verify->v2->services($twilio_verify_sid)
                ->verificationChecks
                ->create($otp, array('to' => "+91".$data['user_mobile_no']));
    
            if ($verification->valid) {
                $paytm_data=User::select('account_paytm_verify_id')->where(['id'=> $data['user_id'],'other_mobile_number'=>$data['user_mobile_no']])->first();
               
                $paytm_id=NULL;
                $updated_date=Carbon::now()->format('Y-m-d H:i:s');
                // return $updated_date;
                $user_data=User::where(['id'=> $data['user_id'],'other_mobile_number'=>$data['user_mobile_no']])->update(['bank_acount_no' => $data['account_no'], 'ifsc_code' =>  $data['ifsc_code'],'account_holder'=> $data['account_holder'],'account_paytm_verify_id'=>$paytm_id,'paytm_verify_status'=>'0','updated_at'=>$updated_date]);
                $paytm_id=$paytm_data->account_paytm_verify_id;
                 $user_bank_details = [
                        'user_id' =>$data['user_id'],
                        'mobile_no' => $data['user_mobile_no'],
                        'account_holder' => $data['account_holder'],
                        'bank_acount_no' => $data['account_no'],
                        'ifsc_code' => $data['ifsc_code'],
                        'account_paytm_verify_id' => $paytm_id,
                        ];
                    user_bank_details_history::create($user_bank_details);
               
                    return response()->json([
                    'message' => 'Bank Details Successfully',
                    'data'=>$user_bank_details
                ], 201);
            }
            return response()->json([
                'message' => 'verification error'
            ], 401);
        }catch(\Exception $e) {
            return $this->getExceptionResponse($e);
        }
    }
    public function get_userbank_details(){
        try{
            $data = User::whereNotNull('bank_acount_no')->orderBy('updated_at', 'desc')->paginate(15);
            return response()->json([
                'data' => $data
            ], 200);
        }catch(\Exception $e) {
            return $this->getExceptionResponse($e);
        }
    }

    public function get_all_internal_users() {
        try{
        $internal_users =  User::where('internal_user', 'Yes')->with('roles')->paginate(15);
        return $internal_users;
        }catch(\Exception $e) {
            return $this->getExceptionResponse($e);
        }
    }

    public function get_all_user(){
        try{
            $data = User::orderBy('id', 'desc')->where('usertype','!=', 11)->paginate(15);
            return response()->json([
                'data' => $data
            ], 200);
        }catch(\Exception $e) {
            return $this->getExceptionResponse($e);
        }
    }
    public function update_bank_paytm_id(Request $request)
    {        
        $request ->validate([
            'account_holder' => 'required',
            'account_no' => 'required',
            'ifsc_code' => 'required',
            'user_mobile_no'=>'required',
            'Paytm_unique_id'=>'required',
            'user_id'=>'required'
        ]);
        // validate

        try{
            $user_data=User::where(['id'=>$request->user_id,'bank_acount_no'=>$request->account_no,'other_mobile_number'=>$request->user_mobile_no])->update(['account_paytm_verify_id' => $request->Paytm_unique_id,'paytm_verify_status'=>'1']);
            return response()->json([
                'message' => 'Successfully verified',
                'status' =>200
            ]);
        }catch(\Exception $e) {
            return $this->getExceptionResponse($e);
        }
    }
    public function bank_details_delete(Request $request)
    {
        try{
             $request -> validate([
                    'user_id' => 'required|integer'
                ]);
            $data = User::where('id', $request->user_id)->update(['bank_acount_no' =>NULL,'ifsc_code'=>NULL,'account_paytm_verify_id'=>NULL,'paytm_verify_status'=>0]);
            return response() -> json ([
                'message' => 'The User Bank  details has been deleted.'
            ]); 
         }catch(\Exception $e) {
            return $this->getExceptionResponse($e);
        }
    }

    public function delete_user(Request $request)
    {
        try{
             $request -> validate([
                    'user_id' => 'required|integer'
                ]);
            $data = User::where('id', $request->user_id)->delete();
            return response() -> json ([
                'message' => 'The User  has been deleted.'
            ]); 
         }catch(\Exception $e) {
            return $this->getExceptionResponse($e);
        }
    }


    /* Code added by Radhika End */

    public function reverify(Request $request)
    {
        try{
        $data = $request->validate([
            'verification_code' => 'required|string',
        ]);
        /* Get credentials from .env */
        $token = getenv("TWILIO_AUTH_TOKEN");
        $twilio_sid = getenv("TWILIO_SID");
        $twilio_verify_sid = getenv("TWILIO_VERIFY_SID");

        $twilio = new Client($twilio_sid, $token);
        $twilio->verify->v2->services($twilio_verify_sid)
            ->verifications
            ->create(Auth::user()->other_mobile_number, "sms");

        $twilio = new Client($twilio_sid, $token);
        $verification = $twilio->verify->v2->services($twilio_verify_sid)
            ->verificationChecks
            ->create($data['verification_code'], array('to' => Auth::user()->other_mobile_number));

        if ($verification->valid) {
            User::where('other_mobile_number', Auth::user()->other_mobile_number)->update(['phone_number_verification_status' => 1]);
            return response()->json([
                'message' => 'Successfully verified'
            ], 201);
        }
        return response()->json([
            'message' => 'verification error'
        ], 201);
        }catch(\Exception $e) {
            return $this->getExceptionResponse($e);
        }
    }
    public function user_status_changes(Request $request){
        // return $request->user_id;
        try{
            $request -> validate([
                    'user_id' => 'required|integer'
                ]);
            $data= User::select('blocked')->where('id', $request->user_id)->first();
            if($data['blocked']=='1'){
                User::where('id', $request->user_id)->update(['blocked' =>'0']);
            }else{
                User::where('id',$request->user_id)->update(['blocked' =>'1']);
            }
            return response()->json([
                'message' => 'User Status Chages',
                'status'=> 200
            ]);

           
         }catch(\Exception $e) {
            return $this->getExceptionResponse($e);
        }
    }


    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean'
        ]);
        $credentials = request(['email', 'password']);
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid Username or Password',
                'status'=>200
            ], 401);
        }
        $user = $request->user();
        $user_misc = $request->user()->only(['phone_number_verification_status','user_role', 'profile_pic','userSelect_type','other_mobile_number']);
    

        if ($user->blocked == 1) {
            return response()->json([
                'message' => 'Your account is blocked',
                'status'=>404
            ], 403);
        }else{

        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        if ($request->remember_me) {
            $token->expires_at = Carbon::now()->addWeeks(20);
        }
        $token->save();
         $user_data=[
            'username' => $user->name,
            'email'    => $user->email,
            'usertype' => $user->usertype,
        ];
        $data = [
            'username' => $user->name,
            'id' => $user->id,
            'email'    => $user->email,
            'usertype' => $user->usertype,
            'internal_user'=>$user->internal_user,
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString(),
            'misc' => $user_misc,
            'user_data' => $user_data
        ];
        return response()->json([
            'data' => $data
        ]);

        }
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    public function user(Request $request)
    {
        $user_id = Auth::user()->id;
        $data = user::where(['id'=>$user_id,'blocked'=> '0'])->with('bank_details_history','productdetails')->first();
       
        return response()->json([
            'data' =>$data,
        ], 201);
    }

    public function verify_user(Request $request)
    {
        return response()->json($request->user()->only(['phone_number_verification_status', 'name', 'email']));
    }

    public function verify_user_mobile(Request $request)
    {
        $user = $request->user();
        return response()->json($user->phone_number_verification_status);
    }

    public function company_signup(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|string|unique:users',
            'profile_pic' => 'required',
            'usertype' => 'required',
            'other_mobile_number' => 'required|integer|between:1000000000,9999999999',
            'password' => 'required|string|confirmed'
        ]);

        $base64_image = $request->input('profile_pic'); // your base64 encoded
        @list($type, $file_data) = explode(';', $base64_image);
        @list(, $file_data) = explode(',', $file_data);
        $imageName = 'IMAGE'.Str::random(30).'.'.'png';
        Storage::disk('public')->put('profile_image_file/'.$imageName, base64_decode($file_data));

        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'profile_pic' => 'profile_image_file/'.$imageName,
            'usertype' => $request->usertype,
            'other_mobile_number' => $request->other_mobile_number,
            'password' => bcrypt($request->password)
        ]);

        $user->save();
        eventtracker::create(['symbol_code' => '6', 'event' => $request->name.' Company Member Created']);


        return response()->json([
            'data' => $user,
            'message' => 'Successfully created admin!'
        ], 201);
    }

    public function admin_signup(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|string|unique:users',
            'profile_pic' => 'required',
            'other_mobile_number' => 'required|integer|between:1000000000,9999999999',
            'password' => 'required|string|confirmed'
        ]);

        $base64_image = $request->input('profile_pic'); // your base64 encoded
        @list($type, $file_data) = explode(';', $base64_image);
        @list(, $file_data) = explode(',', $file_data);
        $imageName = 'IMAGE'.Str::random(30).'.'.'png';
        Storage::disk('public')->put('profile_image_file/'.$imageName, base64_decode($file_data));

        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'profile_pic' => 'profile_image_file/'.$imageName,
            'usertype' => 11,
            'other_mobile_number' => $request->other_mobile_number,
            'password' => bcrypt($request->password)
        ]);

        $user->save();
        eventtracker::create(['symbol_code' => '6', 'event' => $request->name.' Admin Created']);


        return response()->json([
            'data' => $user,
            'message' => 'Successfully created admin!'
        ], 201);
    }

    public function admin_login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
        $credentials = request(['email', 'password']);
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid Admin Credentials'
            ], 401);
        }
        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        $token->expires_at = Carbon::now()->addWeeks(20);
        $token->save();
        return response()->json([
            'username' => $user->name,
            'id' => $user->id,
            'usertype' => $user->usertype,
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString(),
            'misc' => $user
        ]);
    }

    public function forgot_password(Request $request)
    {
        $input = $request->all();
        $rules = array(
            'email' => "required|email",
        );
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $arr = array("status" => 400, "message" => $validator->errors()->first(), "data" => array());
        } else {
            try {
                $response = Password::sendResetLink($request->only('email'), function (Message $message) {
                    $message->subject($this->getEmailSubject());
                });
                switch ($response) {
                    case Password::RESET_LINK_SENT:
                        return \Response::json(array("status" => 200, "message" => trans($response), "data" => array()));
                    case Password::INVALID_USER:
                        return \Response::json(array("status" => 400, "message" => trans($response), "data" => array()));
                }
            } catch (\Swift_TransportException $ex) {
                $arr = array("status" => 400, "message" => $ex->getMessage(), "data" => []);
            } catch (Exception $ex) {
                $arr = array("status" => 400, "message" => $ex->getMessage(), "data" => []);
            }
        }
        return \Response::json($arr);
    }

    public function get_access_rights(Request $request) {

        $request->validate([
            'email' => 'required'			
        ]);

        /*$access_rights = User::where('email', $request->email)->get();
        return $access_rights; */
		$access_rights = DB::table('users')
		->join('user_roles', 'users.user_role', '=', 'user_roles.role')
        ->where('users.email', $request->email)
		->select('users.*', 'user_roles.*' )
		->get();

        return $access_rights;
    }

    public function get_roles(Request $request) {

        return $roles = DB::table('user_roles')->get();
    }




    /* Function to fetch Individual Role details */

    public function get_role_details($id) {

        return $role = DB::table('user_roles')->where('role_id', $id)->get();
    }

	public function upload_profile_pic(Request $request) {

        
        $request->validate([
            'profile_image' => 'required|mimes:jpg,png,jpeg',
            'id' => 'required'			
        ]); 

        $newPostImageName = uniqid() . '-' . $request->file('profile_image')->getClientOriginalName();
        $request->file('profile_image')->move(public_path('storage/images'), $newPostImageName);

        User::where('id', $request['id'])->update(['profile_pic' => $newPostImageName]);

        return response() -> json ([
            'message' => 'The profile picture has been updated',
			'data' => $newPostImageName
        ], 201); 
    }		
    
    public function check_email($email) {
        //$phone = DB::table('users')->where('email', $email)->value('other_mobile_number');
        if(DB::table('users')->where('email', $email)->exists()) {
            $phone = DB::table('users')->where('email', $email)->value('other_mobile_number');
            return $phone;
        }
        else {
            return 0;
        }
        
    }
    
    public function change_password(Request $request)
    {
        $input = $request->all();
        $userid = Auth::guard('api')->user()->id;
        $rules = array(
            'old_password' => 'required',
            'new_password' => 'required|min:6',
            'confirm_password' => 'required|same:new_password',
        );
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $arr = array("status" => 400, "message" => $validator->errors()->first(), "data" => array());
        } else {
            try {
                if ((Hash::check(request('old_password'), Auth::user()->password)) == false) {
                    $arr = array("status" => 400, "message" => "Check your old password.", "data" => array());
                } elseif ((Hash::check(request('new_password'), Auth::user()->password)) == true) {
                    $arr = array("status" => 400, "message" => "Please enter a password which is not similar then current password.", "data" => array());
                } else {
                    User::where('id', $userid)->update(['password' => Hash::make($input['new_password'])]);
                    $arr = array("status" => 200, "message" => "Password updated successfully.", "data" => array());
                }
            } catch (\Exception $ex) {
                if (isset($ex->errorInfo[2])) {
                    $msg = $ex->errorInfo[2];
                } else {
                    $msg = $ex->getMessage();
                }
                $arr = array("status" => 400, "message" => $msg, "data" => array());
            }
        }
        return \Response::json($arr);
    }

    public function googleredirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function googlecallback()
    {
        $user = Socialite::driver('google')->user();

        $finduser = User::where('email', $user->email)->first();

        if ($finduser) {
            Auth::login($finduser);


            $tokenResult = $finduser->createToken('Personal Access Token');
            $token = $tokenResult->token;
            $token->expires_at = Carbon::now()->addWeeks(20);
            $token->save();
			$user_string = http_build_query($finduser->toArray());
            //return redirect()->to('https://www.housingstreet.com/login?token='.$tokenResult->accessToken.'&data='.$finduser);
            return redirect()->to(env('APP_REDIRECT_URL').'/login?token='.$tokenResult->accessToken.'&'.$user_string);

        // return response()->json([

            //     'username' => $finduser->name,
            //     'id' => $finduser->id,
            //     'usertype' => $finduser->usertype,
            //     'access_token' => $tokenResult->accessToken,
            //     'token_type' => 'Bearer',
            //     'expires_at' => Carbon::parse(
            //         $tokenResult->token->expires_at
            //     )->toDateTimeString(),
            //     'misc' => $finduser
            //     ]);
        } else {
            $newUser = User::create([
                'name' => $user->name,
                'email' => $user->email,
                'usertype' => 3,
                'userSelect_type' => 3,
                'profile_pic' => $user->avatar_original,
                //'other_mobile_number' => 1234567890,
                'phone_number_verification_status' => 0,
                'id'=> $user->id,
                'internal_user' => "No",
                'password' => encrypt('123456dummy')
            ]);

            Auth::login($newUser);

            $datauser = User::where('email', $newUser->email)->first();

            $tokenResult = $datauser->createToken('Personal Access Token');
            $token = $tokenResult->token;
            $token->expires_at = Carbon::now()->addWeeks(20);
            $token->save();
            
			$user_string = http_build_query($datauser->toArray());
            //return redirect()->to('https://www.housingstreet.com/login?token='.$tokenResult->accessToken.'&data='.$datauser);
            return redirect()->to(env('APP_REDIRECT_URL').'/login?token='.$tokenResult->accessToken.'&data='.$user_string);
    
            // return response()->json([
            //     'username' => $datauser->name,
            //     'id' => $datauser->id,
            //     'usertype' => $datauser->usertype,
            //     'access_token' => $tokenResult->accessToken,
            //     'token_type' => 'Bearer',
            //     'expires_at' => Carbon::parse(
            //         $tokenResult->token->expires_at
            //     )->toDateTimeString(),
            //     'misc' => $datauser
            //     ]);
        }
    }
     public function user_fetch_details(Request $request){
        $mobile_no = $request->input('mobile_no');
        $email = $request->input('email');
        if($mobile_no != null){
            $request->validate([
                'mobile_no' => 'required|integer|digits:10',
            ]);
			$email_db = DB::table('users')->where('other_mobile_number', $mobile_no)->value('email');																						 
        }
        if($email != null){
            $request->validate([
                'email' => 'required|email|min:7',
            ]);
        }
        try{
            $token  = $request->header('authorization');
            $object = new Authicationcheck();
            if($object->authication_check($token) == true){
                $data=[];
                 if($mobile_no) {
                     // $invoices = DB::table('invoices')->where('user_email', $email_db)->get();

                     $let_out_plans =DB::table('invoices')->where(['user_email'=> $email_db,'plan_type'=>'Let Out'])->get();
                     $rent_plans = DB::table('invoices')->where(['user_email'=> $email_db,'plan_type'=>'Rent'])->get();																					
                     $data = user::where(['other_mobile_number'=>$mobile_no])->with('productdetails','product_wishlist')->get();		 
                 }
                 else if($email) {
                    // $invoices = DB::table('invoices')->where('user_email', $email)->get();
					
                     $let_out_plans =DB::table('invoices')->where(['user_email'=> $email,'plan_type'=>'Let Out'])->get();	
                     $rent_plans = DB::table('invoices')->where(['user_email'=> $email,'plan_type'=>'Rent'])->get(); 	

                     $rent_plans = DB::table('invoices')->where(['user_email'=> $email,'plan_type'=>'Rent'])->get(); 																	   
                   $data = user::where(['email'=>$email])->with('productdetails','product_wishlist')->get();
                 }
                 if(count($data)>0){
                    $properties = product::select('id','build_name')->where(['delete_flag'=> '0','draft'=> '0','order_status'=> '0', 'enabled' => 'yes'])->orderBy('id', 'desc')->get();
                    $user = user::select('id','name','email')->where(['phone_number_verification_status'=> '1',])->orderBy('id', 'desc')->get();
                    $static_data=$object->static_data();
                    return response()->json([
                       'data' =>$data,
					   'let_out_plans' => $let_out_plans,
                       'rent_plans' => $rent_plans,									 
                       'user'=>$user,
                       'properties'=>$properties,
                       'static_data'=>$static_data,
                       'status'=> 200
                     ]);
                 }else{ 
                    $static_data=NULL;
                    $user=NULL;
                    $properties=NULL;
                    $data=NULL;
                    return response()->json([
                       'message' => 'Fail',
                        'description'=>'This User Deatils  Inavalid!!!..',
                        'status'=> 404,
                     ]);
                 }
            }else{
                return response() -> json([
                    'message' => 'Failure',
                    'description'=>'Unauthication',
                    'status'=> 401,
                ]);
            }
        }catch(\Exception $e) {
            return $this->getExceptionResponse1($e);
        }
    }


    public function get_search_user(Request $request){
        try{
            if($request->searchtype=='email'){               
            $data = User::orderBy('id', 'desc')->where(['email'=>$request->email])->paginate(15);
                return response()->json([
                    'data' => $data
                ], 200);
                return response()->json([
                    'data' => $data
                ], 200);

            }if($request->searchtype=='mobile'){
            $data = User::orderBy('id', 'desc')->where(['other_mobile_number'=>$request->mobile])->paginate(15);
                return response()->json([
                    'data' => $data
                ], 200);
            }
        }catch(\Exception $e) {
            return $this->getExceptionResponse($e);
        }
    }
    public function get_invoice_data() {

        return $areas = DB::table('invoice_data')->first();
    }

    public function get_username($email) {
        $username = DB::table('users')->where('email', $email)->value('name');
        return response()->json([
            'data' =>$username,
          ], 201);
    }

}
