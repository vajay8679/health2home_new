<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\BookingRequest;
use App\Models\BookingRequestStatus;
use App\Models\BookingStatus;
use App\Models\Booking;
use App\Models\Vendor;
use App\Models\LaboratoryAppSetting;
use App\Models\Customer;
use App\Models\CustomerAppSetting;
use App\Models\ConsultationRequest;
use App\Models\ConsultationRequestStatus;
use App\Models\CustomerWalletHistory;
use App\Models\Doctor;
use App\Models\HospitalPatient;
use App\Models\HospitalPatientHistory;
use App\Models\Hospital;
use App\Models\DoctorCommission;
use App\Models\DoctorEarning;
use App\Models\DoctorWalletHistory;
use App\Models\DoctorAppSetting;
use App\Models\DoctorSpecialistCategory;
use App\Models\HospitalEarning;
use App\Models\HospitalWalletHistory;
use App\Models\CommissionSetting;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Database;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VoiceGrant;
use Twilio\Jwt\Grants\VideoGrant;
use Twilio\Rest\Client;
use Carbon\CarbonPeriod;
class DoctorBookingController extends Controller
{
    public function create_booking(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'patient_id' => 'required',
            'doctor_id' => 'required',
            'start_time' => 'required',
            'title' => 'required',
            'description' => 'required',
            'total_amount' => 'required',
            'payment_mode' => 'required'
            
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $input['start_time'] = date('Y-m-d H:i:s', strtotime($input['start_time']));
        
        $find_reservation_id = BookingRequest::where('start_time', $input['start_time'])->value('id');
        if($find_reservation_id){
            $response['message'] = "Sorry already reserved this time, Please choose another time";
            $response['status'] = "0";
            return response()->json($response, 200);
        }
        $input['status'] = 1;
        
        $data = BookingRequest::create($input);
        $this->find_fcm_message_doctor('booking_status_'.$input['status'],$data->doctor_id,0);
        
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
        
   }
    
   public function accept_booking(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'booking_request_id' => 'required',
            'slug' => 'required'
            
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        
        $status = DB::table('booking_request_statuses')->where('slug',$input['slug'])->value('id');
        BookingRequest::where('id',$input['booking_request_id'])->update([ 'status' => $status]);
        $booking = BookingRequest::where('id',$input['booking_request_id'])->first();
        if($input['slug'] == "booking_rejected"){
            $this->find_fcm_message('booking_status_'.$status,$booking->patient_id,0,0);
            $old_wallet = Customer::where('id',$booking->patient_id)->value('wallet');
            $new_wallet = $old_wallet + $booking->total;
            Customer::where('id',$booking->patient_id)->update([ 'wallet' => $new_wallet ]);
            
            $data['customer_id'] = $booking->patient_id;
            $data['type'] = 1;
            $data['message'] ="Amount refunded to wallet";
            $data['amount'] = $booking->total_amount;
            $data['transaction_type'] = 2;
            CustomerWalletHistory::create($data); 
        }
        
        return response()->json([
            "message" => 'Success',
            "status" => 1
        ]);
        
   }
   
   public function get_doctor_bookings(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'doctor_id' => 'required',
            
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data = DB::table('booking_requests')
                ->leftJoin('customers','customers.id','booking_requests.patient_id')
                ->leftJoin('payment_modes','payment_modes.id','booking_requests.payment_mode')
                ->leftJoin('booking_request_statuses','booking_request_statuses.id','booking_requests.status')
                ->where('booking_requests.doctor_id',$input['doctor_id'])
                ->orderBy('booking_requests.start_time', 'ASC')
                ->select('booking_requests.*', 'customers.customer_name','customers.email','customers.phone_number','customers.profile_picture','booking_request_statuses.status_name','booking_request_statuses.status_for_doctor','booking_request_statuses.slug','payment_modes.payment_name')
                ->get();
        
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
        
   }
   
   
   public function get_doctor_categories(){
      
       $data = DB::table('doctor_specialist_categories')->where('status',1)->get();
       
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
        
   }
   
   public function doctor_booking_details(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required',
            
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
         $data = DB::table('booking_requests')
                ->leftJoin('customers','customers.id','booking_requests.patient_id')
                ->leftJoin('payment_modes','payment_modes.id','booking_requests.payment_mode')
                ->leftJoin('booking_request_statuses','booking_request_statuses.id','booking_requests.status')
                ->where('booking_requests.id',$input['id'])
                ->select('booking_requests.*', 'customers.customer_name','customers.phone_number','customers.phone_with_code','customers.profile_picture','booking_request_statuses.status_name','booking_request_statuses.slug','booking_request_statuses.status_for_doctor','booking_request_statuses.slug','payment_modes.payment_name')
                ->first();
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
        
   }
   
   public function get_online_doctors(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'specialist' => 'required',
            
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $search = $input['search'];
        $data = DB::table('doctors')
                    ->leftJoin('doctor_specialist_categories','doctor_specialist_categories.id','doctors.specialist')
                    ->where('doctors.profile_status',1)
                    ->where('doctors.specialist',$input['specialist'])
                    ->where('doctor_name', 'LIKE', $search."%")
                    ->orderBy('doctors.overall_ratings', 'DESC')
                    ->select('doctors.*','doctor_specialist_categories.category_name as specialist')
                    ->get();
                    
        foreach($data as $key => $value){
            $data[$key]->providing_services = DB::table('symptoms')
                                              ->leftJoin('doctor_specialist_categories','doctor_specialist_categories.id','symptoms.specialist_id')
                                              ->where('symptoms.specialist_id',$input['specialist'])
                                              ->select('symptoms.*')
                                              ->get();
            $data[$key]->languages = DB::table('doctor_languages')
                                    ->join('languages','languages.id','=','doctor_languages.language_id')
                                    ->select('languages.language','languages.id')
                                    ->where('doctor_languages.doctor_id',$value->id)->get();
                                    
            if($value->hospital_id == 0){
                $value->consultation_fee = DB::table('doctor_booking_settings')->where('doctor_id',$value->id)->value('online_booking_fee');
                $data[$key]->hospital_details = DB::table('hospitals')->where('id',$value->hospital_id)->first();
            }else{
                $value->consultation_fee =  DB::table('hospital_fee_settings')->where('hospital_id',$value->hospital_id)->value('consultation_fee'); 
            }   
      
        }
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
        
   }
   
   public function get_nearest_doctors(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'lat' => 'required',
            'lng' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $search = $input['search'];
        $recommended = [];
        /*$query = DB::table('doctors')
                    ->join('doctor_booking_settings','doctor_booking_settings.doctor_id','=','doctors.id')
                    ->join('doctor_specialist_categories','doctor_specialist_categories.id','doctors.specialist')
                    ->join('hospitals','hospitals.id','doctors.hospital_id')
                    ->where('doctor_booking_settings.direct_appointment_status',1)
                    ->where('doctors.profile_status',1)
                    ->where('doctors.status',1)
                    ->where('doctors.hospital_id','!=',0)
                    ->select('doctors.*','doctor_specialist_categories.category_name as specialist');*/
        
        if(@$input['specialist']) {
            $query = DB::table('hospitals')
                    ->join('hospital_doctors','hospital_doctors.hospital_id','hospitals.id')
                    ->join('doctors','doctors.id','hospital_doctors.doctor_id')
                    ->join('doctor_specialist_categories','doctor_specialist_categories.id','doctors.specialist')
                    ->where('hospitals.status',1)
                    ->where('doctors.specialist',$input['specialist'])
                    ->select('hospitals.*');
            if (@$input['search']) {
                $query->where('hospitals.hospital_name', 'LIKE', $search."%");
            }
        }else{
            $query = DB::table('hospitals')->where('status',1);
            if (@$input['search']) {
                $query->where('hospital_name', 'LIKE', $search."%");
            }
        }
        
        $hospitals = $query->get();
        
        $count = count($hospitals);
        
        $max_distance = CustomerAppSetting::where('id',1)->value('doctor_searching_radius');
        $result = [];
        if($count){
            foreach($hospitals as $key => $value){
                //$hospital_details = DB::table('hospitals')->where('id',$value->hospital_id)->first();

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
                foreach($hospitals[$key]->doctors as $key1 => $val){
                        $hospitals[$key]->doctors[$key1]->languages = DB::table('doctor_languages')
                                        ->join('languages','languages.id','=','doctor_languages.language_id')
                                        ->select('languages.language','languages.id')
                                        ->where('doctor_languages.doctor_id',$val->id)->get();
                }   
                //if(is_object($hospital_details)){
                    if(env('MODE') != 'DEMO'){
                        $distance = $this->distance($value->clinic_lat, $value->clinic_lng, $input['lat'], $input['lng'], "K");
                        if($distance <= $max_distance){
                            //$value->hospital_details = $hospital_details;
                            $value->appointment_fee =  DB::table('hospital_fee_settings')->where('hospital_id',$value->id)->value('appointment_fee'); 
                            $value->waiting_time =  DB::table('hospital_fee_settings')->where('hospital_id',$value->id)->value('waiting_time'); 
                            $result[] = $value;
                            if($value->is_recommended){
                                array_push($recommended,$value);
                            }
                        }
                    }else{
                        //$value->hospital_details = $hospital_details;
                        $value->appointment_fee =  DB::table('hospital_fee_settings')->where('hospital_id',$value->id)->value('appointment_fee'); 
                        $value->waiting_time =  DB::table('hospital_fee_settings')->where('hospital_id',$value->id)->value('waiting_time'); 
                        $result[] = $value;
                        if($value->is_recommended){
                            array_push($recommended,$value);
                        }
                    }
                    
                //}
            }
            
            $results['doctor_list'] = $result;
            $results['recommended'] = $recommended;
            if(count($result) > 0){
                return response()->json([
                    "result" => $results,
                    "message" => 'Success',
                    "status" => 1
                ]); 
            }else{
                return response()->json([
                    "message" => 'Sorry, hospitals not available at your location',
                    "status" => 0
                ]);
            }
            
        }else{
            return response()->json([
                "message" => 'Sorry, hospitals not available at your location',
                "status" => 0
            ]);
        }
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
    
    public function doctor_specialist_category(){
        
        $data = DoctorSpecialistCategory::where('status',1)->get();
    
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
    
    public function get_customer_booking_requests(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'customer_id' => 'required',
            
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data['upcoming'] = DB::table('booking_requests')
                ->leftJoin('doctors','doctors.id','booking_requests.doctor_id')
                ->leftJoin('hospitals','hospitals.id','doctors.hospital_id')
                ->leftJoin('payment_modes','payment_modes.id','booking_requests.payment_mode')
                ->leftJoin('booking_request_statuses','booking_request_statuses.id','booking_requests.status')
                ->where('booking_requests.patient_id',$input['customer_id'])
                ->whereIn('booking_requests.status',[1,2])
                ->orderBy('booking_requests.start_time', 'ASC')
                ->select('booking_requests.*', 'doctors.doctor_name','hospitals.address','hospitals.latitude','hospitals.longitude','hospitals.hospital_name','hospitals.hospital_logo','doctors.email','doctors.phone_number','doctors.profile_image','booking_request_statuses.status_name','booking_request_statuses.slug','booking_request_statuses.status_for_customer','payment_modes.payment_name')
                ->get();
        $data['completed'] = DB::table('booking_requests')
                ->leftJoin('doctors','doctors.id','booking_requests.doctor_id')
                ->leftJoin('hospitals','hospitals.id','doctors.hospital_id')
                ->leftJoin('payment_modes','payment_modes.id','booking_requests.payment_mode')
                ->leftJoin('booking_request_statuses','booking_request_statuses.id','booking_requests.status')
                ->where('booking_requests.patient_id',$input['customer_id'])
                ->whereIn('booking_requests.status',[4])
                ->orderBy('booking_requests.start_time', 'ASC')
                ->select('booking_requests.*', 'doctors.doctor_name','hospitals.address','hospitals.latitude','hospitals.longitude','hospitals.hospital_name','hospitals.hospital_logo','doctors.email','doctors.phone_number','doctors.profile_image','booking_request_statuses.status_name','booking_request_statuses.slug','booking_request_statuses.status_for_customer','payment_modes.payment_name')
                ->get();
        $data['cancelled'] = DB::table('booking_requests')
                ->leftJoin('doctors','doctors.id','booking_requests.doctor_id')
                ->leftJoin('hospitals','hospitals.id','doctors.hospital_id')
                ->leftJoin('payment_modes','payment_modes.id','booking_requests.payment_mode')
                ->leftJoin('booking_request_statuses','booking_request_statuses.id','booking_requests.status')
                ->where('booking_requests.patient_id',$input['customer_id'])
                ->whereIn('booking_requests.status',[3])
                ->orderBy('booking_requests.start_time', 'ASC')
                ->select('booking_requests.*', 'doctors.doctor_name','hospitals.address','hospitals.latitude','hospitals.longitude','hospitals.hospital_name','hospitals.hospital_logo','doctors.email','doctors.phone_number','doctors.profile_image','booking_request_statuses.status_name','booking_request_statuses.slug','booking_request_statuses.status_for_customer','payment_modes.payment_name')
                ->get();
        
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
        
   }
   
   public function get_customer_booking_details(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'booking_request_id' => 'required',
            
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data = DB::table('booking_requests')
                ->leftJoin('doctors','doctors.id','booking_requests.doctor_id')
                ->leftJoin('payment_modes','payment_modes.id','booking_requests.payment_mode')
                ->leftJoin('booking_request_statuses','booking_request_statuses.id','booking_requests.status')
                ->where('booking_requests.id',$input['booking_request_id'])
                ->orderBy('booking_requests.start_time', 'ASC')
                ->select('booking_requests.*', 'doctors.doctor_name','doctors.email','doctors.phone_number','doctors.profile_image','booking_request_statuses.status_name','booking_request_statuses.slug','booking_request_statuses.status_for_customer','payment_modes.payment_name')
                ->first();
        
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
        
   }
   
   public function booking_status_change(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'booking_request_id' => 'required',
            'slug' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $status = DB::table('booking_request_statuses')->where('slug',$input['slug'])->value('id');
        BookingRequest::where('id',$input['booking_request_id'])->update([ 'status' => $status ]);
        $booking = BookingRequest::where('id',$input['booking_request_id'])->first();
        
        $this->booking_commission_calculations($input['booking_request_id'],$booking->doctor_id);
        $hospital_id = Doctor::where('id',$booking->doctor_id)->value('hospital_id');
        if($hospital_id){
            $this->store_patient_history($booking->patient_id,$hospital_id);
        }
        //$this->update_customer_status($booking->id,$booking->status,$booking->patient_id);
        //$booking_status = BookingRequest::where('id',$input['booking_request_id'])->value('status');
        $this->find_fcm_message('booking_status_'.$status,$booking->patient_id,0,0);
        return response()->json([
                "message" => 'Success',
                "status" => 1
            ]);
            
    }
    
    public function store_patient_history($patient_id,$hospital_id){
        $customer = Customer::where('id',$patient_id)->first();
        $patient_id = HospitalPatient::where('phone_number',$customer->phone_number)->where('hospital_id',$hospital_id)->value('id');
        if($patient_id){
             HospitalPatientHistory::create([
                "hospital_patient_id" => $patient_id, "date" => date("Y-m-d H:i:s"), "purpose_of_visit" => "For direct appointment"
            ]);
        }else{
            $id = HospitalPatient::create([ "hospital_id" => $hospital_id, "patient_name" => $customer->customer_name, "phone_number" => $customer->phone_number ])->id;
            HospitalPatientHistory::create([
                "hospital_patient_id" => $id, "date" => date("Y-m-d H:i:s"), "purpose_of_visit" => "For direct appointment"
            ]);
        }
        
    }
    
    public function booking_commission_calculations($booking_id,$doctor_id){
        $hospital_id = DB::table('doctors')->where('id',$doctor_id)->value('hospital_id');
        $commission_type = DoctorAppSetting::value('commission_type');
        if($commission_type == 1){
            $admin_percent = DoctorAppSetting::where('id',1)->value('booking_commission');
        }else{
            $admin_percent = Doctor::where('id',$doctor_id)->value('booking_commission');
        }
        $booking = BookingRequest::where('id',$booking_id)->first();
        
        $admin_commission = ($booking->total_amount / 100) * $admin_percent; 
        $admin_commission = number_format((float)$admin_commission, 2, '.', '');
        
        $doctor_commission = $booking->total_amount - $admin_commission;
        $doctor_commission = number_format((float)$doctor_commission, 2, '.', '');
        
        HospitalEarning::create([ 'hospital_id' => $hospital_id, 'type' => 2, 'ref_id' => $booking_id, 'source_id' => $doctor_id, 'amount' => $doctor_commission]);
        HospitalWalletHistory::create([ 'hospital_id' => $hospital_id, 'type' => 1, 'message' => 'Your earnings credited for this appointment #'.$booking_id, 'amount' => $doctor_commission]);
        
        $wallet = Hospital::where('id',$hospital_id)->value('wallet');
        $new_wallet = $wallet + $doctor_commission;
        $new_wallet = number_format((float)$new_wallet, 2, '.', '');
        
        Hospital::where('id',$hospital_id)->update([ 'wallet' => $new_wallet]);
        
    }
    
    public function hospital_rating(Request $request){
        
        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required',
            'rating' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        BookingRequest::where('id',$input['id'])->update([ 'rating' => $input['rating'], 'comments' => $input['comments']]);
        $doctor_id = BookingRequest::where('id',$input['id'])->value('doctor_id');

        $this->calculate_overall_rating($doctor_id);
        
        return response()->json([
            "message" => 'Success',
            "status" => 1
        ]);
             

    }
    
    public function calculate_overall_rating($doctor_id)
    {
        $hospital_id = Doctor::where('id',$doctor_id)->value('hospital_id');
        $ratings_data = BookingRequest::where('doctor_id',$doctor_id)->where('rating','!=', '0')->count();
        $data_sum = BookingRequest::where('doctor_id',$doctor_id)->get()->sum("rating");
        $data = $data_sum / $ratings_data;
        if($data){
            Hospital::where('id',$hospital_id)->update(['overall_ratings'=>number_format((float)$data, 1, '.', ''), 'no_of_ratings'=> $ratings_data ]);
        }
        
    }
    
    public function doctor_create_booking(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'doctor_id' => 'required',
            'phone_number' => 'required',
            'start_time' => 'required',
            'title' => 'required',
            'description' => 'required',
            'total_amount' => 'required',
            'payment_mode' => 'required'
            
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $input['start_time'] = date('Y-m-d H:i:s', strtotime($input['start_time']));
        $find_reservation_id = BookingRequest::where('start_time', $input['start_time'])->value('id');
        
        $customer = Customer::where('phone_number', $input['phone_number'])->value('id');
        if($customer){
            $input['patient_id'] = $customer;
        }else{
            $response['message'] = "Sorry this customer not registered";
            $response['status'] = "0";
            return response()->json($response, 200);
        }
        $input['status'] = 1;
        
        $data = BookingRequest::create($input);
        $this->find_fcm_message('booking_status_'.$input['status'],$data->patient_id,0,0);
        
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
        
   }

   public function get_time_slots(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'hospital_id' => 'required',
            'date' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $hospital = DB::table('hospitals')->where('id',$input['hospital_id'])->first();
        
        $period = new CarbonPeriod($hospital->opening_time, '30 minutes', $hospital->closing_time);
        $existing_slots = DB::table('booking_requests')->where('doctor_id',$input['doctor_id'])->where('date',$input['date'])->pluck('time')->toArray();
        //print_r($existing_slots);exit;
        $slots = [];
        foreach($period as $item){
            if(!in_array($item->format("h:i:s"), $existing_slots)){
                $slot['key'] = $item->format("h:i A");
                $slot['value'] = $item->format("h:i:s");
                 array_push($slots,$slot);
            }
            //array_push($slots,$item->format("h:i A"));
        }
        
        return response()->json([
            "result" => $slots,
            "message" => 'Success',
            "status" => 1
        ]);
   }
   
   public function customer_booking_rating(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required',
            'rating' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        BookingRequest::where('id',$input['id'])->update([ 'customer_rating' => $input['rating'], 'customer_comments' => $input['comments']]);
        $customer_id = BookingRequest::where('id',$input['id'])->value('patient_id');

        $this->calculate_customer_overall_rating($customer_id);
        
        return response()->json([
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
    public function calculate_customer_overall_rating($customer_id)
    {
        $con_data = ConsultationRequest::where('patient_id',$customer_id)->where('customer_rating','!=', '0')->count();
        $con_sum = ConsultationRequest::where('patient_id',$customer_id)->get()->sum("customer_rating");
        $book_data = BookingRequest::where('patient_id',$customer_id)->where('customer_rating','!=', '0')->count();
        $book_sum = BookingRequest::where('patient_id',$customer_id)->get()->sum("customer_rating");
        $no_of_ratings = ($con_data + $book_data);
        $sum_of_ratings = ($book_sum + $con_sum);
        $data = $sum_of_ratings / $no_of_ratings;
        if($data){
            Customer::where('id',$customer_id)->update(['overall_ratings'=>number_format((float)$data, 1, '.', ''), 'no_of_ratings'=> $no_of_ratings ]);
        }
        
    }
   public function time_slots($from_time,$to_time,$interval){
        $period = new CarbonPeriod($from_time,$interval, $to_time);
        $slots = [];
        foreach($period as $item){
            array_push($slots,$item->format("h:i A"));
        }
        return $slots;
   }
   
   public function sendError($message) {
        $message = $message->all();
        $response['error'] = "validation_error";
        $response['message'] = implode('',$message);
        $response['status'] = "0";
        return response()->json($response, 200);
    }
}
