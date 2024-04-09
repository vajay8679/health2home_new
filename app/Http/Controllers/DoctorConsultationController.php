<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Carbon\Carbon;
use App\Models\Customer;
use App\Models\CustomerAppSetting;
use App\Models\CustomerPrescription;
use App\Models\CustomerPrescriptionItem;
use App\Models\ConsultationRequest;
use App\Models\ConsultationRequestStatus;
use App\Models\BookingRequest;
use App\Models\CustomerWalletHistory;
use App\Models\ConsultationRequestHistory;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\HospitalPatient;
use App\Models\HospitalPatientHistory;
use App\Models\DoctorCommission;
use App\Models\DoctorEarning;
use App\Models\HospitalEarning;
use App\Models\HospitalWalletHistory;
use App\Models\DoctorWalletHistory;
use App\Models\DoctorAppSetting;
use App\Models\CommissionSetting;
use App\Models\DoctorSpecialistCategory;
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
use Barryvdh\DomPDF\Facade\Pdf;

class DoctorConsultationController extends Controller
{
   
   public function create_consultation(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'patient_id' => 'required',
            'doctor_id' => 'required',
            'total' => 'required',
            'payment_mode' => 'required',
            'consultation_type' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $input['status'] = 1;
        if($input['consultation_type'] == 1){
            $input['date'] = date('Y-m-d');
            $input['time'] = date('H:i:s');
        }
        $data = ConsultationRequest::create($input);
        $payment_type = DB::table('payment_modes')->where('id',$input['payment_mode'])->value('payment_type_id');
        if($payment_type == 2){
            $this->deduct_wallet($input['patient_id'],$input['total']);
        }
        $consultation = ConsultationRequest::where('id',$data->id)->first();
        if($input['consultation_type'] == 1){
            $this->update_doctor_status($consultation->id,$consultation->status,$consultation->doctor_id);
            Doctor::where('id',$input['doctor_id'])->update([ 'c_id' => $data->id,'c_stat' => $data->status ]);
        }
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
        
    }
    
    public function deduct_wallet($customer_id,$amount){
        $old_wallet = Customer::where('id',$customer_id)->value('wallet');
        $new_wallet = $old_wallet - $amount;
        Customer::where('id',$customer_id)->update([ 'wallet' => $new_wallet ]);
        
        $data['customer_id'] = $customer_id;
        $data['type'] = 2;
        $data['message'] ="Amount debited from wallet";
        $data['amount'] = $amount;
        $data['transaction_type'] = 2;
        CustomerWalletHistory::create($data); 
   }
    
    public function start_call(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data = ConsultationRequest::where('id',$input['id'])->first();
        if(is_object($data)){
            if($data->status == 1){
                $is_updated = Doctor::where('id',$data->doctor_id)->value('c_id');
                if($is_updated != $data->id || $is_updated == 0){
                    $this->update_doctor_status($data->id,$data->status,$data->doctor_id);
                    Doctor::where('id',$data->doctor_id)->update([ 'c_id' => $data->id,'c_stat' => $data->status ]);
                }else{
                   return response()->json([
                        "message" => 'Sorry doctor in another call please wait..',
                        "status" => 3
                    ]); 
                }
            }else if($data->status == 3){
                return response()->json([
                    "message" => 'Sorry your booking unfortunately rejected',
                    "status" => 2
                ]); 
            }else{
                return response()->json([
                    "message" => 'Sorry something went wrong',
                    "status" => 0
                ]); 
            }
        }else{
            return response()->json([
                "message" => 'Sorry something went wrong',
                "status" => 0
            ]);
        }
        
        return response()->json([
            "message" => 'Success',
            "status" => 1
        ]);
        
    }
    
    public function get_time_slots(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'doctor_id' => 'required',
            'date' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $app_settings = DB::table('doctor_app_settings')->first();
        
        $period = new CarbonPeriod($app_settings->consultation_request_start_time, '30 minutes', $app_settings->consultation_request_end_time);
        $existing_slots = DB::table('consultation_requests')->where('doctor_id',$input['doctor_id'])->where('date',$input['date'])->pluck('time')->toArray();
        $slots = [];
        foreach($period as $item){
            if(!in_array($item->format("h:i:s"), $existing_slots)){
                $slot['key'] = $item->format("h:i A");
                $slot['value'] = $item->format("h:i:s");
                 array_push($slots,$slot);
            }
        }
        
        return response()->json([
            "result" => $slots,
            "message" => 'Success',
            "status" => 1
        ]);
   }
   
    public function check_consultation(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'patient_id' => 'required',
            'doctor_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $last_row = ConsultationRequest::where('patient_id',$input['patient_id'])->where('doctor_id',$input['doctor_id'])->where('status','!=',3)->orderBy('id','DESC')->first();
        if(is_object($last_row)){
            $to_time = strtotime(date('Y-m-d H:i:s'));
            $from_time = strtotime($last_row->created_at);
            $diff = round(abs($to_time - $from_time) / 60,2);
            $customer_app_settings = CustomerAppSetting::first();
            if($diff < $customer_app_settings->instant_consultation_duration){
                return response()->json([
                    "result" => $last_row->id,
                    "message" => 'Success',
                    "status" => 1
                ]);
            }else{
                return response()->json([
                    "result" => 0,
                    "message" => 'Success',
                    "status" => 1
                ]);
            }
        }else{
            return response()->json([
                "result" => 0,
                "message" => 'Success',
                "status" => 1
            ]);
        }
    }
    
    public function continue_consultation(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'patient_id' => 'required',
            'doctor_id' => 'required',
            'consultation_request_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        ConsultationRequest::where('id',$input['consultation_request_id'])->update([ 'status' => 1 ]);
        $consultation = ConsultationRequest::where('id',$input['consultation_request_id'])->first();
        $this->update_doctor_status($consultation->id,$consultation->status,$consultation->doctor_id);
        Doctor::where('id',$input['doctor_id'])->update([ 'c_id' => $consultation->id,'c_stat' => $consultation->status ]);
        return response()->json([
            "result" => $consultation,
            "message" => 'Success',
            "status" => 1
        ]);
    }

    public function consultation_status_change(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'consultation_request_id' => 'required',
            'slug' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $old_status =  ConsultationRequest::where('id',$input['consultation_request_id'])->value('status');
        $history = ConsultationRequestHistory::where('cr_id',$input['consultation_request_id'])->count();
        if(in_array($old_status, [1,3]) && $input['slug'] == "completed" && $history == 0){
            $input['slug'] = "rejected";
        }

        $status = DB::table('consultation_request_statuses')->where('slug',$input['slug'])->value('id');
        
        ConsultationRequest::where('id',$input['consultation_request_id'])->update([ 'status' => $status ]);
        $consultation = ConsultationRequest::where('id',$input['consultation_request_id'])->first();
       
        if($input['slug'] == "completed" || $input['slug'] == "rejected"){
            Doctor::where('id',$consultation->doctor_id)->update([ 'c_id' => 0,'c_stat' => 0 ]);
            $this->update_doctor_status(0,0,$consultation->doctor_id);
            if($input['slug'] == "completed"){
                $this->consultation_commission_calculations($input['consultation_request_id'],$consultation->doctor_id);
                $hospital_id = Doctor::where('id',$consultation->doctor_id)->value('hospital_id');
                if($hospital_id){
                    $this->store_patient_history($consultation->patient_id,$hospital_id);
                }
            }else{
                if($old_status != 3){
                    $old_wallet = Customer::where('id',$consultation->patient_id)->value('wallet');
                    $new_wallet = $old_wallet + $consultation->total;
                    Customer::where('id',$consultation->patient_id)->update([ 'wallet' => $new_wallet ]);
                    
                    $data['customer_id'] = $consultation->patient_id;
                    $data['type'] = 1;
                    $data['message'] ="Amount refunded to wallet";
                    $data['amount'] = $consultation->total;
                    $data['transaction_type'] = 2;
                    CustomerWalletHistory::create($data); 
                }
            }
        }else{
            ConsultationRequestHistory::create([ 'cr_id' => $consultation->id ]);
            Doctor::where('id',$consultation->doctor_id)->update([ 'c_id' => $consultation->id,'c_stat' => $consultation->status ]);
            $this->update_doctor_status($consultation->id,$consultation->status,$consultation->doctor_id);
        }
        
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
                "hospital_patient_id" => $patient_id, "date" => date("Y-m-d H:i:s"), "purpose_of_visit" => "For doctor video consultation"
            ]);
        }else{
            $id = HospitalPatient::create([ "hospital_id" => $hospital_id, "patient_name" => $customer->customer_name, "phone_number" => $customer->phone_number ])->id;
            HospitalPatientHistory::create([
                "hospital_patient_id" => $id, "date" => date("Y-m-d H:i:s"), "purpose_of_visit" => "For doctor consultation"
            ]);
        }
        
    }
    
    public function update_doctor_status($id,$status,$doctor_id){
        $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
        $database = $factory->createDatabase();
        $newPost = $database
        ->getReference('doctors/'.$doctor_id)
        ->update([
            'c_id' => $id,
            'c_stat' => $status
        ]);
    }
    
    public function consultation_commission_calculations($booking_id,$doctor_id){
        $hospital_id = DB::table('doctors')->where('id',$doctor_id)->value('hospital_id');
        $commission_type = DoctorAppSetting::value('commission_type');
        if($commission_type == 1){
            $admin_percent = DoctorAppSetting::where('id',1)->value('booking_commission');
        }else{
            $admin_percent = Doctor::where('id',$doctor_id)->value('booking_commission');
        }
        $booking = ConsultationRequest::where('id',$booking_id)->first();
        
        $admin_commission = ($booking->total / 100) * $admin_percent; 
        $admin_commission = number_format((float)$admin_commission, 2, '.', '');
        
        $doctor_commission = $booking->total - $admin_commission;
        $doctor_commission = number_format((float)$doctor_commission, 2, '.', '');
        
        if($hospital_id){
            HospitalEarning::create([ 'hospital_id' => $hospital_id, 'type' => 1, 'ref_id' => $booking_id, 'source_id' => $doctor_id, 'amount' => $doctor_commission]);
            HospitalWalletHistory::create([ 'hospital_id' => $hospital_id, 'type' => 1, 'message' => 'Your earnings credited for this consultation #'.$booking_id, 'amount' => $doctor_commission]);
            
            $wallet = Hospital::where('id',$hospital_id)->value('wallet');
            $new_wallet = $wallet + $doctor_commission;
            $new_wallet = number_format((float)$new_wallet, 2, '.', '');
            
            Hospital::where('id',$hospital_id)->update([ 'wallet' => $new_wallet]);
        }else{
            $order_commission['booking_id'] = $booking_id;
            $order_commission['role'] = 'doctor';
            $order_commission['user_id'] = $doctor_id;
            $order_commission['amount'] = $doctor_commission;
            DoctorCommission::create($order_commission);
            
            $order_commission['booking_id'] = $booking_id;
            $order_commission['role'] = 'admin';
            $order_commission['user_id'] = 1;
            $order_commission['amount'] = $admin_commission;
            DoctorCommission::create($order_commission);
            
            DoctorEarning::create([ 'booking_id' => $booking_id, 'doctor_id' => $doctor_id, 'amount' => $doctor_commission]);
            DoctorWalletHistory::create([ 'doctor_id' => $doctor_id, 'type' => 1, 'message' => 'Your earnings credited for this consultation #'.$booking_id, 'amount' => $doctor_commission]);
            
            $wallet = Doctor::where('id',$doctor_id)->value('wallet');
            $new_wallet = $wallet + $doctor_commission;
            $new_wallet = number_format((float)$new_wallet, 2, '.', '');
            
            Doctor::where('id',$doctor_id)->update([ 'wallet' => $new_wallet]);
        }

    }

    public function get_doctor_consultations(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'doctor_id' => 'required',
            
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data = DB::table('consultation_requests')
                ->leftJoin('customers','customers.id','consultation_requests.patient_id')
                ->leftJoin('payment_modes','payment_modes.id','consultation_requests.payment_mode')
                ->leftJoin('consultation_request_statuses','consultation_request_statuses.id','consultation_requests.status')
                ->where('consultation_requests.doctor_id',$input['doctor_id'])
                ->orderBy('consultation_requests.created_at', 'desc')
                ->select('consultation_requests.*', 'customers.customer_name','customers.email','customers.phone_number','customers.profile_picture','consultation_request_statuses.status_name','consultation_request_statuses.status_for_doctor','consultation_request_statuses.slug','payment_modes.payment_name')
                ->get();
        foreach($data as $key => $value){
            $startdate = $value->date.' '.$value->time;
            $expire = strtotime($startdate. ' + 7 days');
            $today = strtotime("today midnight");
            
            if($today < $expire && $value->status != 3){
                $data[$key]->chat_status = 1;
            } else {
                $data[$key]->chat_status = 0;
            }
        }
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
        
   }
   
   public function doctor_consultation_details(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required',
            
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
         $data = DB::table('consultation_requests')
                ->leftJoin('customers','customers.id','consultation_requests.patient_id')
                ->leftJoin('payment_modes','payment_modes.id','consultation_requests.payment_mode')
                ->leftJoin('consultation_request_statuses','consultation_request_statuses.id','consultation_requests.status')
                ->where('consultation_requests.id',$input['id'])
                ->select('consultation_requests.*', 'customers.customer_name','customers.phone_number','customers.phone_with_code','customers.profile_picture','consultation_request_statuses.status_name','consultation_request_statuses.slug','consultation_request_statuses.status_for_doctor','consultation_request_statuses.slug','payment_modes.payment_name')
                ->first();
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
        
   }
    public function cr_expiring(){
        $data = ConsultationRequest::where('status',1)->where('consultation_type',2)->get();
        foreach($data as $key => $value){
            $expiry_time = date("H:i:s", strtotime(date("H:i:s"))+(60*15)); //15 minutes
            if($expiry_time > $value->time){
                ConsultationRequest::where('id',$value->id)->update([
                    'status' => 3
                ]);
            }
        }
        
        return response()->json([
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
    public function cr_reminder(){
        $data = ConsultationRequest::where('status',2)->where('consultation_type',2)->get();
        
        foreach($data as $key => $value){
            $expire = strtotime($value->date.' '.$value->time);
            $today = strtotime(date('Y-m-d H:i:s'));
            
            if($today < $expire){
                $to_time = strtotime(date('Y-m-d H:i:s'));
                $from_time = strtotime($value->date.' '.$value->time);
                $time = round(abs($to_time - $from_time) / 60);
                if($time <= 15){
                    $message = "Message";
                    $fcm_token = DB::table('customers')->where('id',$value->patient_id)->value('fcm_token');
                    $this->send_fcm('Consultation Reminder',$message,$fcm_token);
                }
            }
        }
        return response()->json([
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
    public function get_customer_consultation_requests(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'customer_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data = DB::table('consultation_requests')
                ->leftJoin('doctors','doctors.id','consultation_requests.doctor_id')
                ->leftJoin('payment_modes','payment_modes.id','consultation_requests.payment_mode')
                ->leftJoin('consultation_request_statuses','consultation_request_statuses.id','consultation_requests.status')
                ->where('consultation_requests.patient_id',$input['customer_id'])
                ->orderBy('consultation_requests.created_at', 'desc')
                ->select('consultation_requests.*', 'doctors.doctor_name','doctors.email','doctors.phone_number','doctors.profile_image','consultation_request_statuses.status_name','consultation_request_statuses.slug','consultation_request_statuses.status_for_customer','payment_modes.payment_name')
                ->get();
        
        foreach($data as $key => $value){
            $startdate = $value->date.' '.$value->time;
            $expire = strtotime($startdate. ' + 7 days');
            $today = strtotime("today midnight");
            
            if($today < $expire && $value->status != 3){
                $data[$key]->chat_status = 1;
            } else {
                $data[$key]->chat_status = 0;
            }

            if($value->status == 1 && $value->consultation_type == 2){
                $data[$key]->btn_status = 1;   
            }else{
                $data[$key]->btn_status = 0;
            }
        }
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
        
    }
   
    public function get_customer_consultation_details(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required',
            
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data = DB::table('consultation_requests')
                ->leftJoin('doctors','doctors.id','consultation_requests.doctor_id')
                ->leftJoin('payment_modes','payment_modes.id','consultation_requests.payment_mode')
                ->leftJoin('consultation_request_statuses','consultation_request_statuses.id','consultation_requests.status')
                ->where('consultation_requests.id',$input['id'])
                ->select('consultation_requests.*', 'doctors.doctor_name','doctors.email','doctors.phone_number','doctors.profile_image','consultation_request_statuses.status_name','consultation_request_statuses.slug','consultation_request_statuses.status_for_customer','payment_modes.payment_name')
                ->first();
        
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
        
   }
   
    public function doctor_rating(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required',
            'rating' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        ConsultationRequest::where('id',$input['id'])->update([ 'rating' => $input['rating'], 'comments' => $input['comments']]);
        $doctor_id = ConsultationRequest::where('id',$input['id'])->value('doctor_id');

        $this->calculate_overall_rating($doctor_id);
        
        return response()->json([
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
    public function customer_consultation_rating(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required',
            'rating' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        ConsultationRequest::where('id',$input['id'])->update([ 'customer_rating' => $input['rating'], 'customer_comments' => $input['comments']]);
        $customer_id = ConsultationRequest::where('id',$input['id'])->value('patient_id');

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
    
    public function calculate_overall_rating($doctor_id)
    {
        $ratings_data = ConsultationRequest::where('doctor_id',$doctor_id)->where('rating','!=', '0')->count();
        $data_sum = ConsultationRequest::where('doctor_id',$doctor_id)->get()->sum("rating");
        $data = $data_sum / $ratings_data;
        if($data){
            Doctor::where('id',$doctor_id)->update(['overall_ratings'=>number_format((float)$data, 1, '.', ''), 'no_of_ratings'=> $ratings_data ]);
        }
        
    }
    
    public function get_prescription(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'booking_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $prescription = CustomerPrescription::where('booking_id',$input['booking_id'])->first();
        if(is_object($prescription)) {
            $data['items'] = CustomerPrescriptionItem::where('customer_prescription_id',$prescription->id)->get();
            $data['prescription_id'] = $prescription->id;
            return response()->json([
                "result" => $data,
                "message" => 'Success',
                "status" => 1
            ]);
        }else{
            return response()->json([
                "message" => 'Sorry, no data found !',
                "status" => 0
            ]);
        }
   }
   
    public function create_prescription(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'booking_id' => 'required',
            'subjective_information' => 'required',
            'objective_information' => 'required',
            'assessment' => 'required',
            'plan' => 'required',
            'doctor_notes' => 'required'
        ]);
        
        $booking = ConsultationRequest::where('id',$input['booking_id'])->first();
        
        //Create prescription
        $prescription_r['booking_id'] = $booking->id;
        $prescription_r['doctor_id'] = $booking->doctor_id;
        $prescription_r['patient_id'] = $booking->patient_id;
        $prescription_r['subjective_information'] = $input['subjective_information'];
        $prescription_r['objective_information'] = $input['objective_information'];
        $prescription_r['assessment'] = $input['assessment'];
        $prescription_r['plan'] = $input['plan'];
        $prescription_r['doctor_notes'] = $input['doctor_notes'];
        $prescription_r['date'] = Carbon::today();
        $prescription = CustomerPrescription::create($prescription_r);
        
        return response()->json([
            "data" => $prescription,
            "result" => 'Success',
            "status" => 1
        ]);
    }
    
    public function create_prescription_items(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'prescription_id' => 'required',
            'medicine_name' => 'required',
            'morning' => 'required',
            'afternoon' => 'required',
            'evening' => 'required',
            'night' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $prescription = CustomerPrescription::where('id',$input['prescription_id'])->first();
        //$booking = ConsultationRequest::where('id',$input['booking_id'])->first();
        if(is_object($prescription)){
            //Insert items
            $data['customer_prescription_id'] = $prescription->id;
            $data['medicine_name'] = $input['medicine_name'];
            $data['morning'] = $input['morning'];
            $data['afternoon'] = $input['afternoon'];
            $data['evening'] = $input['evening'];
            $data['night'] = $input['night'];
            $prescription_items = CustomerPrescriptionItem::create($data);
        }
        
        /*else{
            $prescription_r['booking_id'] = $booking->id;
            $prescription_r['doctor_id'] = $booking->doctor_id;
            $prescription_r['patient_id'] = $booking->patient_id;
            $prescription_r['date'] = Carbon::today();
            $prescriptions = CustomerPrescription::create($prescription_r);
            $prescription = CustomerPrescription::where('booking_id',$input['booking_id'])->first();
            $data['customer_prescription_id'] = $prescription->id;
            $data['medicine_name'] = $input['medicine_name'];
            $data['morning'] = $input['morning'];
            $data['afternoon'] = $input['afternoon'];
            $data['evening'] = $input['evening'];
            $data['night'] = $input['night'];
            $prescription_items = CustomerPrescriptionItem::create($data);
        }
        $result['prescription'] = $prescription;
        $result['prescription_item'] = CustomerPrescriptionItem::where('customer_prescription_id',$prescription->id)->get();
        $booking = ConsultationRequest::where('id',$input['booking_id'])->first();
        $this->find_fcm_message('prescription_status',$booking->patient_id,0,0);*/
        
        //response
        return response()->json([
            "result" => 'Success',
            "status" => 1
        ]);
        
   }
   
    public function e_prescription_download($id){
        $customer_prescription = DB::table('customer_prescriptions')->where('id',$id)->first();
        $doctor = DB::table('doctors')->where('id',$customer_prescription->doctor_id)->first();
        $items = DB::table('customer_prescription_items')->where('id',$customer_prescription->id)->get();
        $customer = DB::table('customers')->where('id',$customer_prescription->patient_id)->first();
        $data['doctor_name'] = $doctor->doctor_name;
        $data['doctor_qualification'] = $doctor->qualification;
        $data['doctor_specialist'] = $doctor->sub_specialist;
        $data['doctor_experience'] = $doctor->experience;
        $data['doctor_image'] = $doctor->profile_image;
        
        $data['customer_name'] = $customer->customer_name;
        $data['customer_blood_group'] = $customer->blood_group;
        $data['customer_phone_number'] = $customer->phone_number;
        
        $data['items'] = $items;
        $param['data'] = $data;
        $pdf = Pdf::loadView('mail_templates.prescription', $param);
        return $pdf->stream('download.pdf');
    }
   
   public function sendError($message) {
        $message = $message->all();
        $response['error'] = "validation_error";
        $response['message'] = implode('',$message);
        $response['status'] = "0";
        return response()->json($response, 200);
    }
}
