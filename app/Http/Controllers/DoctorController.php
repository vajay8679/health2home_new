<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Doctor;
use App\Models\DoctorEarning;
use App\Models\DoctorLanguage;
use App\Models\DoctorBankDetail;
use App\Models\DoctorWalletHistory;
use App\Models\DoctorBookingSetting;
use App\Models\DoctorAppSetting;
use App\Models\Language;
use App\Models\Symptom;
use App\Models\ClinicDetail;
use App\Models\ClinicTiming;
use App\Models\Status;
use App\Models\BookingRequest;
use App\Models\DoctorDocument;
use App\Models\DoctorWithdrawal;
use Validator;
use Illuminate\Support\Facades\Hash;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Kreait\Firebase;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Database;

class DoctorController extends Controller
{
    public function login(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
            'phone_with_code' => 'required',
            'password' => 'required',
            'fcm_token' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $credentials = request(['phone_with_code', 'password']);
        $doctor = Doctor::where('phone_with_code',$credentials['phone_with_code'])->first();

        if (!($doctor)) {
            return response()->json([
                "message" => 'Invalid phone number or password',
                "status" => 0
            ]);
        }
        
        if (Hash::check($credentials['password'], $doctor->password)) {
            if($doctor->status == 1){
                Doctor::where('id',$doctor->id)->update([ 'fcm_token' => $input['fcm_token']]);
                return response()->json([
                    "result" => $doctor,
                    "message" => 'Success',
                    "status" => 1
                ]);   
            }else{
                return response()->json([
                    "message" => 'Your account has been blocked',
                    "status" => 0
                ]);
            }
        }else{
            return response()->json([
                "message" => 'Invalid phone number or password',
                "status" => 0
            ]);
        }

    }

    public function register(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'doctor_name' => 'required',
            'qualification' => 'required',
            'additional_qualification' => 'required',
            'phone_number' => 'required|numeric|unique:doctors,phone_number',
            'phone_with_code' => 'required',
            'email' => 'required|email|regex:/^[a-zA-Z]{1}/|unique:doctors,email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $options = [
            'cost' => 12,
        ];
        $input['password'] = password_hash($input["password"], PASSWORD_DEFAULT, $options);
        $input['status'] = 1;
        $input['unique_code'] = '';

        $doctor = Doctor::create($input);
        $doc = Doctor::where('id', $doctor->id)->first();
        $credential['doctor_id'] = $doctor->id;
        $credential['online_booking_status'] = 1;
        $credential['online_booking_fee'] = 100;
        $credential['online_booking_time'] = 15;
        $credential['direct_appointment_status'] = 0;
        $credential['direct_appointment_fee'] = 100;
        $credential['direct_appointment_time'] = 15;

        if (is_object($doctor)) {
            DoctorBookingSetting::create($credential);
            $doctor->unique_code = 'C2H'.str_pad($doctor->id,5,"0",STR_PAD_LEFT);
            Doctor::where('id',$doctor->id)->update(['unique_code' => $doctor->unique_code]);
            $this->update_status($doctor->id,$doctor->doctor_name,$doctor->online_status);
            return response()->json([
                "result" => $doc,
                "message" => 'Registered Successfully',
                "status" => 1
            ]);
        } else {
            return response()->json([
                "message" => 'Sorry, something went wrong !',
                "status" => 0
            ]);
        }

    }
    
    public function update_status($id,$doc_nme,$on_stat){
        
        $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
        $database = $factory->createDatabase();
        $newPost = $database
        ->getReference('doctors/'.$id)
        ->update([
            'doc_nme' => $doc_nme,
            'c_id' => 0,
            'c_stat' => 0,
            'on_stat' => 0
        ]);
    }

    public function check_phone(Request $request)
    {

        $input = $request->all();
        $validator = Validator::make($input, [
            'phone_with_code' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $data = array();
        $doctor = Doctor::where('phone_with_code',$input['phone_with_code'])->first();

        if(is_object($doctor)){
            $data['is_available'] = 1;
            $data['otp'] = "";
            return response()->json([
                "result" => $data,
                "message" => 'Success',
                "status" => 1
            ]);
        }else{
            $data['is_available'] = 0;
            $data['otp'] = rand(1000,9999);
             if(env('MODE') != 'DEMO'){
                $message = "Hi, from ".env('APP_NAME'). "  , Your OTP code is:".$data['otp'];
                $this->sendSms($input['phone_with_code'],$message);
            }
            return response()->json([
                "result" => $data,
                "message" => 'Success',
                "status" => 1
            ]);
        }
    }
    
    public function forget_password(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'phone_with_code' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $doctor = Doctor::where('phone_with_code',$input['phone_with_code'])->first();
        

        if(is_object($doctor)){
            $data['id'] = $doctor->id;
            $data['otp'] = rand(1000,9999);
            if(env('MODE') != 'DEMO'){
                $message = "Hi".env('APP_NAME'). "  , Your OTP code is:".$data['otp'];
                $this->sendSms($input['phone_with_code'],$message);
            }
            return response()->json([
                "result" => $data,
                "message" => 'Success',
                "status" => 1
            ]);
        }else{
            return response()->json([
                "result" => 'Please enter valid phone number',
                "status" => 0
            ]);
            
        }

    }
    
    public function reset_password(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $options = [
            'cost' => 12,
        ];
        $input['password'] = password_hash($input["password"], PASSWORD_DEFAULT, $options);

        if(Doctor::where('id',$input['id'])->update($input)){
            return response()->json([
                "message" => 'Success',
                "status" => 1
            ]);
        }else{
            return response()->json([
                "message" => 'Sorry something went wrong',
                "status" => 0
            ]);
        }
    }
    
    public function profile_update(Request $request)
    {

        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        if($request->password){
            $options = [
                'cost' => 12,
            ];
            $input['password'] = password_hash($input["password"], PASSWORD_DEFAULT, $options);
            $input['status'] = 1;
        }else{
            unset($input['password']);
        }

        if(Doctor::where('id',$input['id'])->update($input)) {
            return response()->json([
                "result" => Doctor::select('id','email','phone_number','phone_with_code','doctor_name','profile_image','status')->where('id',$input['id'])->first(),
                "message" => 'Success',
                "status" => 1
            ]);
        }else{
            return response()->json([
                "message" => 'Sorry, something went wrong...',
                "status" => 0
            ]);
        }

    }
    
    public function profile_picture(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
            'image' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $name = time().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/uploads/doctors');
            $image->move($destinationPath, $name);
            return response()->json([
                "result" => 'doctors/'.$name,
                "message" => 'Success',
                "status" => 1
            ]);
            
        }
    }

    public function profile_picture_update(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required',
            'profile_image' => 'required'
            
        ]);

        if ($validator->fails()) {
          return $this->sendError($validator->errors());
        }
        
        if (Doctor::where('id',$input['id'])->update($input)) {
            return response()->json([
                "message" => 'Success',
                "status" => 1
            ]);
        } else {
            return response()->json([
                "message" => 'Sorry, something went wrong...',
                "status" => 0
            ]);
        }

    }
    
    public function get_profile(Request $request)
        {
            $input = $request->all();
            $validator = Validator::make($input, [
                'doctor_id' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->errors());
            }

            $result = DB::table('doctors')
                ->leftJoin('doctor_specialist_categories','doctor_specialist_categories.id','doctors.specialist')
                ->where('doctors.id',$input['doctor_id'])
                ->select('doctors.*','doctor_specialist_categories.id','doctor_specialist_categories.category_name as specialist_name')
                ->first();
            $result->languages = DB::table('doctor_languages')
                                    ->join('languages','languages.id','=','doctor_languages.language_id')
                                    ->select('languages.language','languages.id')
                                    ->where('doctor_languages.doctor_id',$input['doctor_id'])->get();
            $result->language_ids = DB::table('doctor_languages')->where('doctor_id',$input['doctor_id'])->pluck('language_id')->toArray();
            if (is_object($result)) {
                return response()->json([
                    "result" => $result,
                    "message" => 'Success',
                    "status" => 1
                ]);
            } else {
                return response()->json([
                    "message" => 'Sorry, something went wrong...',
                    "status" => 0
                ]);
            }
        }
    public function upload(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
            'image' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $name = time().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/uploads/doctor_images');
            $image->move($destinationPath, $name);
            return response()->json([
                "result" => 'doctor_images/'.$name,
                "message" => 'Success',
                "status" => 1
            ]);
            
        }
    }
    public function document_upload(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'doctor_id' => 'required',
            'document_name' => 'required',
            'document_path' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $input['status'] = 3;
        $exist_id = DoctorDocument::where('doctor_id',$input['doctor_id'])->where('document_name',$input['document_name'])->value('id');
        if($exist_id){
             DoctorDocument::where('doctor_id',$input['doctor_id'])->where('document_name',$input['document_name'])->update([ 'document_path' => $input['document_path'], 'status' => $input['status'] ]);
        }else{
             DoctorDocument::create($input);
        }
       
        return response()->json([
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
    /*public function document_update(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'doctor_id' => 'required',
            'id_proof' => 'required',
            'certificate' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $old_document = DoctorDocument::where('doctor_id',$input['doctor_id'])->first();
        if($old_document->id_proof != $input['id_proof']){
            $input['id_proof_status'] = 3;
        }
        
        if($old_document->certificate != $input['certificate']){
            $input['certificate_status'] = 3;
        }
        
        DoctorDocument::where('doctor_id',$input['doctor_id'])->update($input);
        $doctor = Doctor::where('id',$input['doctor_id'])->update([ 'document_update_status' => 1, 'document_approved_status' => 3  ]);
        
        return response()->json([
            "message" => 'Success',
            "status" => 1
        ]);
    }*/
    
    public function document_details(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'doctor_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $documents = DB::table('doctor_documents')
                    ->leftJoin('statuses','statuses.id','doctor_documents.status')
                    ->select('doctor_documents.*','statuses.status_name')
                    ->where('doctor_id',$input['doctor_id'])->get();
        
        if($documents){
            return response()->json([
                "result" => $documents,
                "message" => 'Success',
                "status" => 1
            ]);
        }else{
            return response()->json([
                "message" => 'Sorry document not uploaded',
                "status" => 0
            ]);
        }
    }
    
    public function doctor_earning(Request $request){
        
        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data['total_earnings'] = DoctorEarning::where('doctor_id',$input['id'])->get()->sum("amount");
        $data['today_earnings'] = DoctorEarning::where('doctor_id',$input['id'])->whereDay('created_at', now()->day)->sum("amount");
        $data['earnings'] = DoctorEarning::where('doctor_id',$input['id'])->get();
        
        if($data){
            return response()->json([
                "result" => $data,
                "count" => count($data),
                "message" => 'Success',
                "status" => 1
            ]);
        }else{
            return response()->json([
                "message" => 'Something went wrong',
                "status" => 0
            ]);
        }

    }
    
    public function doctor_withdrawal(Request $request){
        
        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data['wallet_amount'] = Doctor::where('id',$input['id'])->value('wallet');
        
        $data['withdraw'] =  DB::table('doctor_withdrawals')
                ->leftjoin('statuses', 'statuses.id', '=', 'doctor_withdrawals.status')
                ->select('doctor_withdrawals.*', 'statuses.status_name')
                ->get();
        
        if($data){
            return response()->json([
                "result" => $data,
                "count" => count($data),
                "message" => 'Success',
                "status" => 1
            ]);
        }else{
            return response()->json([
                "message" => 'Something went wrong',
                "status" => 0
            ]);
        }
    }
    
    public function doctor_withdrawal_request(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'doctor_id' => 'required',
            'amount' => 'required'
            
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $input['status'] = 6;
         $input['message'] = "Your withdrawal request successfully submitted";
        $del_wallet = Doctor::where('id',$input['doctor_id'])->value('wallet');
        $new_wallet = $del_wallet-$input['amount'];
        $input['existing_wallet'] = $del_wallet;
        if($input['amount'] <= $del_wallet ){
          $doctor = DoctorWithdrawal::create($input);  
          
        $status = DoctorWithdrawal::where('doctor_id',$input['doctor_id'])->where('id',$doctor->id)->value('status');
            if($status==6){
                 Doctor::where('id',$input['doctor_id'])->update([ 'wallet' => $new_wallet]);
            }
        if (is_object($doctor)) {
            return response()->json([
                "result" => $doctor,
                "message" => 'success',
                "status" => 1
            ]);
        } else {
            return response()->json([
                "message" => 'Sorry, something went wrong !',
                "status" => 0
            ]);
        }
        }else{
             return response()->json([
                "message" => 'Sorry, something went wrong !',
                "status" => 0
            ]);
        }
        
        
    }
    
    public function doctor_wallet_histories(Request $request){
        
        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $data['wallet_amount'] = Doctor::where('id',$input['id'])->value('wallet');
        
        $data['wallets'] = DoctorWalletHistory::where('doctor_id',$input['id'])->get();
        
        if($data){
            return response()->json([
                "result" => $data,
                "count" => count($data),
                "message" => 'Success',
                "status" => 1
            ]);
        }else{
            return response()->json([
                "message" => 'Something went wrong',
                "status" => 0
            ]);
        }

    }
    
    public function qualification_update(Request $request)
    {
        $input = $request->all();
        $id = $input['id'];
        $validator = Validator::make($input, [
            'specialist' => 'required',
            'sub_specialist' => 'required',
            'experience' => 'required',
            'gender' => 'required',
            'languages' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $language = $input['languages'];
        unset($input['languages']);
        if (Doctor::where('id',$id)->update($input)) {
            $doctor = Doctor::where('id',$id)->update([ 'profile_status' => 1 ]);
            $lang = explode(",",$language);
            DoctorLanguage::where('doctor_id',$id)->delete();
            foreach($lang as $key => $value){
                DoctorLanguage::create([ 'doctor_id' => $id, 'language_id' => $value]);
            }
            return response()->json([
                "result" => Doctor::select('id','specialist','sub_specialist','qualification','experience','gender', 'description')->where('id',$id)->first(),
                "message" => 'Success',
                "status" => 1
            ]);
        } else {
            return response()->json([
                "message" => 'Sorry, something went wrong...',
                "status" => 0
            ]);
        }

    }
    
    public function change_online_status(Request $request){
        $input = $request->all();
        Doctor::where('id',$input['id'])->update([ 'online_status' => $input['online_status']]);
         $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
        $database = $factory->createDatabase();
        $newPost = $database
        ->getReference('doctors/'.$input['id'])
        ->update([
            'on_stat' => (int) $input['online_status']
        ]);
        return response()->json([
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
    public function add_clinic_detail(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'doctor_id' => 'required',
            'clinic_name' => 'required',
            'opening_time' => 'required',
            'closing_time' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $clinic_details = ClinicDetail::where('doctor_id',$input['doctor_id'])->first();
        if(is_object($clinic_details)){
            ClinicDetail::where('doctor_id',$input['doctor_id'])->update($input);
            $detail = ClinicDetail::where('doctor_id',$input['doctor_id'])->first();
        }else{
            $detail = ClinicDetail::create($input);    
        }
        
        if (is_object($detail)) {
            return response()->json([
                "result" => $detail,
                "message" => 'Registered Successfully',
                "status" => 1
            ]);
        } else {
            return response()->json([
                "message" => 'Sorry, something went wrong !',
                "status" => 0
            ]);
        }
    }
    
    public function add_clinic_address(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'doctor_id' => 'required',
            'clinic_address' => 'required',
            'clinic_lat' => 'required',
            'clinic_lng' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $url = 'https://maps.googleapis.com/maps/api/staticmap?center='.$input['clinic_lat'].','.$input['clinic_lng'].'&zoom=16&size=600x300&maptype=roadmap&markers=color:red%7Clabel:S%7C'.$input['clinic_lat'].','.$input['clinic_lng'].'&key='.env('MAP_KEY');
        $img = 'static_map/'.md5(time()).'.png';
        file_put_contents('uploads/'.$img, file_get_contents($url));

        $input['static_map'] = $img;
        
        ClinicDetail::where('doctor_id',$input['doctor_id'])->update($input);
        return response()->json([
            "message" => 'Updated Successfully',
            "status" => 1
        ]);

    }
    
    public function clinic_image_upload(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
            'image' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $name = time().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/uploads/clinic_images');
            $image->move($destinationPath, $name);
            return response()->json([
                "result" => 'clinic_images/'.$name,
                "message" => 'Success',
                "status" => 1
            ]);
            
        }
    }
    
    public function clinic_image_update(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required',
            'clinic_image' => 'required'
            
        ]);

        if ($validator->fails()) {
          return $this->sendError($validator->errors());
        }
        
        if (ClinicDetail::where('id',$input['id'])->update($input)) {
            return response()->json([
                "message" => 'Success',
                "status" => 1
            ]);
        } else {
            return response()->json([
                "message" => 'Sorry, something went wrong...',
                "status" => 0
            ]);
        }

    }
    
    public function dashboard(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data['total_booking'] = BookingRequest::where('doctor_id',$input['id'])->count();
        $data['today_booking'] = DB::table('booking_requests')
            ->leftJoin('booking_request_statuses', 'booking_request_statuses.id', '=', 'booking_requests.status')
            ->select('booking_requests.*','booking_request_statuses.status_name','booking_request_statuses.slug')
            ->whereDay('booking_requests.created_at', Carbon::today())
            ->where('booking_requests.doctor_id',$input['id'])
            ->whereIn('booking_request_statuses.slug',['waiting_for_confirmation','booking_confirmed'])
            ->get()->count();
        $data['pending_booking'] = DB::table('booking_requests')
            ->leftJoin('booking_request_statuses', 'booking_request_statuses.id', '=', 'booking_requests.status')
            ->select('booking_requests.*','booking_request_statuses.status_name','booking_request_statuses.slug')
            ->where('booking_requests.doctor_id',$input['id'])
            ->whereIn('booking_request_statuses.slug',['waiting_for_confirmation','booking_confirmed'])
            ->get()->count();
        $data['completed_booking'] = DB::table('booking_requests')
            ->leftJoin('booking_request_statuses', 'booking_request_statuses.id', '=', 'booking_requests.status')
            ->select('booking_requests.*','booking_request_statuses.status_name','booking_request_statuses.slug')
            ->where('booking_requests.doctor_id',$input['id'])
            ->where('booking_request_statuses.slug','booking_completed')
            ->get()->count();
        $data['booking_requests'] = DB::table('booking_requests')
                                    ->leftJoin('customers','customers.id','booking_requests.patient_id')
                                    ->leftJoin('booking_request_statuses','booking_request_statuses.id','booking_requests.status')
                                    ->select('booking_requests.*','customers.customer_name','booking_request_statuses.status_name','booking_request_statuses.slug','customers.profile_picture','customers.email','customers.phone_number','customers.phone_with_code')
                                    ->where('booking_requests.doctor_id',$input['id'])
                                    ->where('booking_request_statuses.slug','waiting_for_confirmation')
                                    ->get();
        $data['booking_requests_count'] = count($data['booking_requests']);
        
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
        
    }
    
    public function doctor_booking_setting_update(Request $request)
    {
        $input = $request->all();
        $id = $input['doctor_id'];

        if (DoctorBookingSetting::where('doctor_id',$id)->update($input)) {
            $data = DoctorBookingSetting::where('doctor_id',$id)->first();
            return response()->json([
                "result" => $data,
                "message" => 'Success',
                "status" => 1
            ]);
        } else {
            return response()->json([
                "message" => 'Sorry, something went wrong...',
                "status" => 0
            ]);
        }

    }
    
    public function get_clinic_details(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'doctor_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data = ClinicDetail::where('doctor_id',$input['doctor_id'])->first();
        if(!is_object($data)){
            return response()->json([
                "message" => 'Success',
                "status" => 0
            ]); 
        }else{
            if($data->clinic_address){
                $data->clinic_address_status = 1;
            }else{
                $data->clinic_address_status = 0;
            }
            
            return response()->json([
                "result" => $data,
                "message" => 'Success',
                "status" => 1
            ]);
        }
    }
    
    public function get_booking_settings(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'doctor_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $booking = DoctorBookingSetting::where('doctor_id',$input['doctor_id'])->first();
        
        return response()->json([
            "result" => $booking,
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
     public function get_hospital_details(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'doctor_id' => 'required',
            
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data = DB::table('doctors')
                ->leftJoin('hospital_doctors','hospital_doctors.doctor_id','doctors.id')
                ->leftJoin('hospitals','hospitals.id','doctors.hospital_id')
                ->leftJoin('doctor_specialist_categories','doctor_specialist_categories.id','doctors.specialist')
                ->where('doctors.id',$input['doctor_id'])
                ->select('doctors.id','doctors.doctor_name','doctors.profile_image','hospitals.hospital_name','hospitals.hospital_logo','hospitals.description','hospitals.phone_number','hospitals.phone_with_code','hospital_doctors.join_date','doctor_specialist_categories.category_name as specialist')
                ->first();
        $data->booking_count = DB::table('booking_requests')
                 ->where('booking_requests.doctor_id',$input['doctor_id'])->count();
        $data->online_consultation_count = DB::table('consultation_requests')
                 ->where('consultation_requests.doctor_id',$input['doctor_id'])->count();
        
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
        
   }
   
   public function add_bank_detail(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'doctor_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $input['status'] = 1;
        $is_details_exist = DoctorBankDetail::where('doctor_id', $input['doctor_id'])->first();
        if(is_object($is_details_exist)){
            $update = DoctorBankDetail::where('doctor_id', $input['doctor_id'])->update($input);  
        }else{
             $update =  DoctorBankDetail::create($input);   
        }
        if ($update) {   
           return response()->json([
                "result" => DoctorBankDetail::select('id','doctor_id', 'bank_name', 'bank_account_number','swift_code','beneficiary_name')->where('doctor_id', $input['doctor_id'])->first(),
                "message" => 'Success',
                "status" => 1
            ]);
        } else {
            return response()->json([
                "message" => 'Sorry, something went wrong !',
                "status" => 0
            ]);
        }

    }
    
    public function get_bank_details(Request $request){
        $input = $request->all();
        $kyc_details = DoctorBankDetail::where('doctor_id', $input['doctor_id'])->first();
        if(is_object($kyc_details)){
            return response()->json([
                "result" => $kyc_details,
                "message" => 'Success',
                "status" => 1
            ]);
        }else{
            return response()->json([
                "message" => 'Still not updated',
                "status" => 0
            ]);
        }
    }
    
    public function get_languages(){
        $languages = DB::table('languages')->select('language as name','id')->get();
        return response()->json([
            "result" => $languages,
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
    public function sendSms($phone_number,$message)
    {
        $sid    = env( 'TWILIO_SID' );
        $token  = env( 'TWILIO_TOKEN' );
        $client = new Client( $sid, $token );
        $client->messages->create($phone_number,[ 'from' => env( 'TWILIO_FROM' ),'body' => $message,]);
        return true;
    }
    
    public function sendError($message) {
        $message = $message->all();
        $response['error'] = "validation_error";
        $response['message'] = implode('',$message);
        $response['status'] = "0";
        return response()->json($response, 200);
    } 
}


    
   