<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Address;
use App\Models\Blog;
use App\Models\Vendor;
use App\Models\PaymentMode;
use App\Models\VendorPromoCode;
use App\Models\Tax;
use App\Models\CustomerPromoHistory;
use App\Models\CustomerAppSetting;
use App\Models\LaboratoryAppSetting;
use Validator;
use Illuminate\Support\Facades\Hash;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Database;
use Illuminate\Support\Facades\DB;
use Twilio\Rest\Client;
use Cartalyst\Stripe\Stripe;
use App\Models\PaymentResponse;



class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function login(Request $request)
    {

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
        $customer = Customer::where('phone_with_code',$credentials['phone_with_code'])->first();

        if (!($customer)) {
            return response()->json([
                "message" => 'Invalid phone number or password',
                "status" => 0
            ]);
        }
        
        if (Hash::check($credentials['password'], $customer->password)) {
            if($customer->status == 1){
                
                Customer::where('id',$customer->id)->update([ 'fcm_token' => $input['fcm_token']]);
                
                return response()->json([
                    "result" => $customer,
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
        $customer = Customer::where('phone_with_code',$input['phone_with_code'])->first();

        if(is_object($customer)){
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

    public function register(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'customer_name' => 'required',
            'phone_number' => 'required|numeric|unique:customers,phone_number',
            'phone_with_code' => 'required',
            'email' => 'required',
            'password' => 'required',
            'fcm_token' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $options = [
            'cost' => 12,
        ];
        $input['password'] = password_hash($input["password"], PASSWORD_DEFAULT, $options);
        $input['status'] = 1;
        

        $customer = Customer::create($input);
        $cus = Customer::where('id',$customer->id)->first();

        if (is_object($cus)) {
          
            return response()->json([
                "result" => $cus,
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

    public function forget_password(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'phone_with_code' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $customer = Customer::where('phone_with_code',$input['phone_with_code'])->first();
        

        if(is_object($customer)){
            $data['id'] = $customer->id;
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

        if(Customer::where('id',$input['id'])->update($input)){
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

    public function profile_picture(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $name = time().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/uploads/customers');
            $image->move($destinationPath, $name);
            return response()->json([
                "result" => 'customers/'.$name,
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
            'profile_picture' => 'required'
            
        ]);

        if ($validator->fails()) {
          return $this->sendError($validator->errors());
        }
        
        if (Customer::where('id',$input['id'])->update($input)) {
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

    public function get_profile(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $customer = Customer::where('id',$input['id'])->first();
        if(is_object($customer)){
            return response()->json([
                "result" => $customer,
                "message" => 'Success',
                "status" => 1
            ]);
        }
        else{
            return response()->json([
                "message" => 'Something went wrong',
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

        if (Customer::where('id',$input['id'])->update($input)) {
            return response()->json([
                "result" => Customer::select('id','email','phone_number','customer_name','profile_picture','status','pre_existing_desease','gender','blood_group')->where('id',$input['id'])->first(),
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
    
    public function get_payment_mode(Request $request){
        
        $input = $request->all();
        $validator = Validator::make($input, [
            'type' => 'required',
            'customer_id' => 'required'
            
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data = [];
        if($input['type'] == 0){
            $data['payment_modes'] = DB::table('payment_modes')
                ->leftJoin('payment_types','payment_types.id','payment_modes.payment_type_id')
                ->select('payment_modes.*', 'payment_types.type_name')
                ->get();
        }if($input['type'] == 1){
            $data['payment_modes'] = DB::table('payment_modes')
                ->leftJoin('payment_types','payment_types.id','payment_modes.payment_type_id')
                ->where('payment_modes.payment_type_id',1)
                ->select('payment_modes.*', 'payment_types.type_name')
                ->get();
        }
        if($input['type'] == 2){
            $data['payment_modes'] = DB::table('payment_modes')
                ->leftJoin('payment_types','payment_types.id','payment_modes.payment_type_id')
                ->where('payment_modes.payment_type_id',2)
                ->select('payment_modes.*', 'payment_types.type_name')
                ->get();
        }
        
        $data['wallet_balance'] = Customer::where('id',$input['customer_id'])->value('wallet');
        
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
    public function last_active_address(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
            'customer_id' => 'required',
            'address_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

       
        if(Customer::where('id',$input['customer_id'])->update([ 'last_active_address' => $input['address_id']])){
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
    
    public function get_last_active_address(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
            'customer_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $last_active_address = Customer::where('id',$input['customer_id'])->value('last_active_address');
        $address = "";
        if($last_active_address){
            $address = Address::where('id',$last_active_address)->first();
            return response()->json([
                "result" => $address,
                "message" => 'Success',
                "status" => 1
            ]);
        }
        else{
            return response()->json([
                "message" => 'Please add address',
                "status" => 0
            ]);
        }
    }
    
     public function get_promo(Request $request)
    {   
        $input = $request->all();
        $validator = Validator::make($input, [
            'customer_id' => 'required',
            'vendor_id' => 'required',
            
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $result = [];
        $data = VendorPromoCode::where('status',1)->whereIn('vendor_id',[ 0, $input['vendor_id']])->whereIn('customer_id',[ 0, $input['customer_id']])->get();
        
        foreach($data as $key => $value){
            $check_redemptions = CustomerPromoHistory::where('customer_id',$input['customer_id'])->where('promo_id',$value->id)->count();
            if($value->redemptions == 0 || $check_redemptions < $value->redemptions){
                array_push($result,$value);
            }
            
        }
        
        
        return response()->json([
            "result" => $result,
            "count" => count($result),
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
     public function check_promo(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
            'vendor_id' => 'required',
            'customer_id' => 'required',
            'promo_code' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $promo = VendorPromoCode::where('promo_code',$input['promo_code'])->where('status',1)->first();
        if(is_object($promo)){
            $using_count = CustomerPromoHistory::where('customer_id',$input['customer_id'])->where('promo_id',$promo->id)->count();
            if($promo->vendor_id == 0 || $promo->vendor_id == $input['vendor_id']){
                if($promo->customer_id == 0 || $promo->customer_id == $input['customer_id']){
                    if($promo->redemptions == 0 || $using_count < $promo->redemptions){
                        return response()->json([
                            "result" => $promo,
                            "message" => 'Success',
                            "status" => 1
                        ]);
                    }else{
                       return response()->json([
                            "message" => 'Sorry this promo count exceeded',
                            "status" => 0
                        ]); 
                    }
                }else{
                    return response()->json([
                        "message" => 'Sorry invalid promo code',
                        "status" => 0
                    ]); 
                }
            }else{
                return response()->json([
                    "message" => 'Sorry invalid promo code',
                    "status" => 0
                ]); 
            }
        }else{
            return response()->json([
                "message" => 'Sorry invalid promo code',
                "status" => 0
            ]);
        }

    }
    
    public function get_taxes(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'service_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $data = Tax::where('service_id',$input['service_id'])->where('status',1)->get();
        return response()->json([
            "result" => $data,
            "count" => count($data),
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
    public function get_blog(){
        $data = Blog::orderBy('id', 'DESC')->get();
        return response()->json([
            "result" => $data,
            "count" => count($data),
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
    public function get_module_banners(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'app_module' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data = DB::table('module_banners')->where('status',1)->where('app_module',$input['app_module'])->select('banner as url')->get(); 
        
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
        
    }
    
    public function home(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'lat' => 'required',
            'lng' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data['banners'] = DB::table('banners')->where('status',1)->where('app_module',1)->select('banners as url','link')->get();
        $data['services'] = DB::table('services')->where('status',1)->get();
        $symptoms_count = DB::table('symptoms')->where('status',1)->count();
        $offset = (int) $symptoms_count / 2;
        $data['symptoms_first'] = DB::table('symptoms')->skip(0)->take($offset)->where('status',1)->get();
        $data['symptoms_second'] = DB::table('symptoms')->skip($offset)->take($offset)->where('status',1)->get();
        $data['vendors'] = $this->get_recommended_vendors($input);
        $data['labs'] = $this->get_recommended_labs($input);
        $data['hospitals'] = $this->get_recommended_hospitals($input);
        $data['recommended_doctors'] = $this->get_recommended_doctors();
        $data['top_rated_doctors'] = $this->get_top_rated_doctors();
       
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
   }
   
   public function get_recommended_doctors(){
        $data = DB::table('doctors')
                    ->join('hospital_fee_settings','hospital_fee_settings.hospital_id','=','doctors.hospital_id')
                    ->join('doctor_booking_settings','doctor_booking_settings.doctor_id','=','doctors.id')
                    ->join('doctor_specialist_categories','doctor_specialist_categories.id','doctors.specialist')
                    ->where('doctors.profile_status',1)
                    ->where('doctors.status',1)
                    ->where('doctors.is_recommended',1)
                    ->select('doctors.*','doctor_specialist_categories.category_name as specialist','doctor_specialist_categories.id as specialist_id', 'doctor_booking_settings.direct_appointment_status as appointment_status','hospital_fee_settings.appointment_fee')->take(10)->get();
        foreach($data as $key => $val){
            $data[$key]->languages = DB::table('doctor_languages')
                            ->join('languages','languages.id','=','doctor_languages.language_id')
                            ->select('languages.language','languages.id')
                            ->where('doctor_languages.doctor_id',$val->id)->get();
        }
        return $data;
   }
   
    public function get_top_rated_doctors(){
        $data = DB::table('doctors')
                    ->join('hospital_fee_settings','hospital_fee_settings.hospital_id','=','doctors.hospital_id')
                    ->join('doctor_booking_settings','doctor_booking_settings.doctor_id','=','doctors.id')
                    ->join('doctor_specialist_categories','doctor_specialist_categories.id','doctors.specialist')
                    ->where('doctors.profile_status',1)
                    ->where('doctors.status',1)
                    ->select('doctors.*','doctor_specialist_categories.category_name as specialist','doctor_specialist_categories.id as specialist_id', 'doctor_booking_settings.direct_appointment_status as appointment_status','hospital_fee_settings.appointment_fee')->take(10)->orderBy('doctors.overall_ratings', 'desc')->get();
        foreach($data as $key => $val){
            $data[$key]->languages = DB::table('doctor_languages')
                            ->join('languages','languages.id','=','doctor_languages.language_id')
                            ->select('languages.language','languages.id')
                            ->where('doctor_languages.doctor_id',$val->id)->get();
        }
        
        return $data;
    }
   
    public function get_recommended_vendors($input){
       $data = Vendor::where('status',1)->where('document_approved_status',1)->where('address_update_status',1)->orderBy('online_status', 'DESC')->get();
        $count = count($data);
        $vendor_radius = CustomerAppSetting::where('id',1)->value('vendor_radius');
        $recommended = [];
        if($count){
            if(env('MODE') != 'DEMO'){
                foreach($data as $key => $value){
                     $distance =  $this->distance($value->latitude,$value->longitude,$input['lat'], $input['lng'],'K');
                     if($distance <= $vendor_radius){
                         if($value->is_recommended){
                             array_push($recommended,$value);
                         }
                     }
                }
            }else{
                foreach($data as $key => $value){
                    if($value->is_recommended){
                         array_push($recommended,$value);
                     }
                }
            }
        }
        
        return $recommended;
   }
   
   public function get_recommended_hospitals($input){
        $recommended = [];
        $query = DB::table('hospitals')
                ->join('hospital_doctors','hospital_doctors.hospital_id','hospitals.id')
                ->join('doctors','doctors.id','hospital_doctors.doctor_id')
                ->join('doctor_specialist_categories','doctor_specialist_categories.id','doctors.specialist')
                ->where('hospitals.status',1)
                ->select('hospitals.*');
        $hospitals = $query->get();
        $count = count($hospitals);
        
        $max_distance = CustomerAppSetting::where('id',1)->value('doctor_searching_radius');
        
        if($count){
            foreach($hospitals as $key => $value){
                $hospitals[$key]->insurances = DB::table('insurances')
                                                ->join('hospital_insurances','hospital_insurances.insurance_id','=','insurances.id')
                                                ->where('hospital_insurances.hospital_id',$value->id)
                                                ->select('insurances.*')->get();
                
                $galleries = DB::table('hospital_galleries')->where('hospital_id',$value->id)->select('image_path as src','id')->get();
                foreach($galleries as $gkey => $gvalue){
                    $galleries[$gkey]->src = env('IMG_URL').$gvalue->src;
                }
                
                $hospitals[$key]->galleries = $galleries;
                $hospitals[$key]->departments = DB::table('hospital_departments')->where('hospital_id',$value->id)->get();
                $hospitals[$key]->facilities = DB::table('hospital_facilities')->where('hospital_id',$value->id)->get();
                $hospitals[$key]->hospital_services = DB::table('hospital_services')->where('hospital_id',$value->id)->get();
                $hospitals[$key]->our_labs = $this->get_hospital_labs($value->id);
                $hospitals[$key]->our_vendors = $this->get_hospital_vendors($value->id);
                $hospitals[$key]->doctors = DB::table('hospital_doctors')
                    ->join('doctors','doctors.id','=','hospital_doctors.doctor_id')
                    ->join('doctor_booking_settings','doctor_booking_settings.doctor_id','=','doctors.id')
                    ->join('doctor_specialist_categories','doctor_specialist_categories.id','doctors.specialist')
                    ->where('doctors.profile_status',1)
                    ->where('doctors.status',1)
                    ->where('hospital_doctors.hospital_id','=',$value->id)
                    ->select('doctors.*','doctor_specialist_categories.category_name as specialist', 'doctor_booking_settings.direct_appointment_status as appointment_status')->get();
                    foreach($hospitals[$key]->doctors as $key2 => $val){
                        $hospitals[$key]->doctors[$key2]->languages = DB::table('doctor_languages')
                                        ->join('languages','languages.id','=','doctor_languages.language_id')
                                        ->select('languages.language','languages.id')
                                        ->where('doctor_languages.doctor_id',$val->id)->get();
                    }
                    if(env('MODE') != 'DEMO'){
                        $distance = $this->distance($value->clinic_lat, $value->clinic_lng, $input['lat'], $input['lng'], "K");
                        if($distance <= $max_distance){
                            $value->appointment_fee =  DB::table('hospital_fee_settings')->where('hospital_id',$value->id)->value('appointment_fee'); 
                            $value->waiting_time =  DB::table('hospital_fee_settings')->where('hospital_id',$value->id)->value('waiting_time'); 
                            if($value->is_recommended){
                                array_push($recommended,$value);
                            }
                            $result[] = $value;
                        }
                    }else{
                        $value->appointment_fee =  DB::table('hospital_fee_settings')->where('hospital_id',$value->id)->value('appointment_fee'); 
                        $value->waiting_time =  DB::table('hospital_fee_settings')->where('hospital_id',$value->id)->value('waiting_time'); 
                        if($value->is_recommended){
                            array_push($recommended,$value);
                        }
                        $result[] = $value;
                    }
                   
            }
        }
        
        return $recommended;
        
   }
   
   public function get_hospital_labs($hospital_id){
        $data = DB::table('laboratories')
            ->join('lab_services', 'lab_services.lab_id', '=', 'laboratories.id')
            ->join('hospital_laboratories', 'hospital_laboratories.hospital_id', '=', 'laboratories.hospital_id')
            ->select('laboratories.*')
            ->where('laboratories.status',1)
            ->where('hospital_laboratories.hospital_id',$hospital_id)
            ->get();
        return $data;
   }
   
   public function get_hospital_vendors($hospital_id){
       $data = Vendor::where('status',1)->where('document_approved_status',1)->where('hospital_id',$hospital_id)->where('address_update_status',1)->orderBy('online_status', 'DESC')->get();
        
        return $data;
   }
   
    public function get_recommended_labs($input){
        $recommended = [];
        $lab_radius = LaboratoryAppSetting::where('id',1)->value('lab_radius');
       
        $data = DB::table('laboratories')
            ->leftJoin('lab_services', 'lab_services.lab_id', '=', 'laboratories.id')
            ->leftJoin('services', 'services.id', '=', 'lab_services.service_id')
            ->select('laboratories.*','lab_services.service_id')
            ->where('laboratories.status',1)
            ->get();
        foreach($data as $key => $value){
            $distance =  $this->distance($value->lat,$value->lng,$input['lat'],$input['lng'],'K');
            if($distance <= $lab_radius){
                $data[$key]->providing_service = DB::table('services')->where('id',$value->service_id)->first();
                if($value->is_recommended){
                    $recommended[] = $value;
                }     
            }
        }
        return $recommended;
   }
   
   public function distance($lat1, $lon1, $lat2, $lon2, $unit) {
      $theta = $lon1 - $lon2;
      $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
      $dist = acos($dist);
      $dist = rad2deg($dist);
      $miles = $dist * 60 * 1.1515;
      $unit = strtoupper($unit);
    
      if ($unit == "K") {
          return ($miles * 1.609344);
      } else if ($unit == "N") {
          return ($miles * 0.8684);
      } else {
          return $miles;
      }
    }
    
    public function get_patient_histories(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'customer_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data['history'] = DB::table('booking_requests')
                             ->join('doctors','doctors.id','=','booking_requests.doctor_id')
                             ->join('customers','customers.id','=','booking_requests.patient_id')
                             ->join('hospitals','hospitals.id','=','doctors.hospital_id')
                             ->where('booking_requests.patient_id',$input['customer_id'])
                             ->select('booking_requests.*','doctors.doctor_name','customers.customer_name','hospitals.hospital_name')
                             ->get();
        $data['patient_details'] = DB::table('customers')->where('id',$input['customer_id'])->first();
        
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
    public function stripe_payment(Request $request){
        $input = $request->all();
        $stripe = new Stripe();
        $currency_code = CustomerAppSetting::value('currency_short_code');
        
        try {
            $charge = $stripe->charges()->create([
                'source' => $input['token'],
                'currency' => $currency_code,
                'amount'   => $input['amount'],
                'description' => 'For consultation booking'
            ]);
            
            $data['order_id'] = 0;
            $data['customer_id'] = $input['customer_id'];
            $data['payment_mode'] = 2;
            $data['payment_response'] = $charge['id'];
            
            if(PaymentResponse::create($data)){
                return response()->json([
                    "result" => $charge['id'],
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
        catch (customException $e) {
            return response()->json([
                "message" => 'Sorry something went wrong',
                "status" => 0
            ]);
        }
    }
    
    public function create_stripe($email,$name){
        
    }
    
    public function test_stripe(){
        $stripe = new Stripe();
        
        try {
            $charge = $stripe->charges()->create([
                'customer' => 'cus_IaSYKvrlPKks0m',
                'currency' => 'INR',
                'amount'   => '100',
            ]);
        } catch (Cartalyst\Stripe\Exception\CardErrorException $e) {
            // Get the status code
            $code = $e->getCode();
        
            // Get the error message returned by Stripe
            $message = $e->getMessage();
        
            // Get the error type returned by Stripe
            $type = $e->getErrorType();
            
            echo $message;
        }
        
        echo "<pre>";
        //print_r($charge);
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
   

    