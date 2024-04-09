<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Laboratory;
use App\Models\LabPromoCode;
use App\Models\LaboratoryAppSetting;
use App\Models\CustomerAppSetting;
use App\Models\Customer;
use App\Models\PaymentMode;
use App\Models\Address;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\XrayOrder;
use App\Models\LabBanner;
use App\Models\CustomerWalletHistory;
use App\Models\CustomerPromoHistory;
use App\Models\LabPackage;
use Validator;
use Mail;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use FCM;
use Carbon\Carbon;
use App\FcmNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Database;
use Twilio\Rest\Client;


class LabOrderController extends Controller
{

    public function get_lab_list(Request $request)
    {   
        $input = $request->all();
        $validator = Validator::make($input, [
            'lat' => 'required',
            'lng' => 'required'
        ]);

        if ($validator->fails()) {
          return $this->sendError($validator->errors());
        }
        $search = $input['search'];
        $recommended = [];
        $nearest = [];
        $lab_radius = LaboratoryAppSetting::where('id',1)->value('lab_radius');
        $result = [];
        
        
        $data = DB::table('laboratories')
            ->leftJoin('lab_services', 'lab_services.lab_id', '=', 'laboratories.id')
            ->leftJoin('services', 'services.id', '=', 'lab_services.service_id')
            ->select('laboratories.*','lab_services.service_id')
            ->where('laboratories.lab_name', 'LIKE', "%$search%")
            ->where('laboratories.status',1)
            ->get();
            foreach($data as $key => $value){
                $distance =  $this->distance($value->lat,$value->lng,$input['lat'],$input['lng'],'K');
                if($distance <= $lab_radius){
                    $data[$key]->providing_service = DB::table('services')->where('id',$value->service_id)->first();
                     array_push($nearest,$value);
                    if($search == ''){
                        if($value->is_recommended){
                            $recommended[] = $value;
                        }     
                    }
                }
                
            }
        if($search == ''){
            $lab_banners = DB::table('banners')->where('status',1)->where('app_module',4)->select('banners as url')->get();
            $result['banners'] = $lab_banners;
            $result['recommended'] = $recommended;
        }
        
        
        $result['nearest'] = $nearest;
        
        return response()->json([
            "result" => $result,
            "count" => count($result),
            "message" => 'Success',
            "status" => 1
        ]);
    
    }
    
    public function distance($lat1, $lon1, $lat2, $lon2, $unit) {

      $theta = $lon1 - $lon2;
      $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
      $dist = acos($dist);
      $dist = rad2deg($dist);
      $miles = $dist * 60 * 1.1515;
      $unit = strtoupper($unit);
    
      if ($unit == "K") {
         $km = ($miles * 1.609344);
         if($km < 1){
            return 1;
         }else{
            return (int) $km;
         }
      } else if ($unit == "N") {
         return ($miles * 0.8684);
      } else {
         return $miles;
      }
    }
    
     public function lab_detail(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
            'lab_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data['relevances'] = DB::table('lab_packages')
                            ->join('lab_relevances', 'lab_relevances.id', '=', 'lab_packages.lab_relevance_id')
                            ->where('lab_packages.lab_id',$input['lab_id'])
                            ->where('lab_packages.status',1)
                            ->select('lab_relevances.id','lab_relevances.relevance_name','lab_relevances.relevance_icon')
                            ->groupBy('lab_relevances.id','lab_relevances.relevance_name','lab_relevances.relevance_icon')
                            ->get();
                            
        $data['popular_packages'] = DB::table('lab_packages')
                            ->leftJoin('lab_relevances', 'lab_relevances.id', '=', 'lab_packages.lab_relevance_id')
                            ->leftJoin('lab_tags', 'lab_tags.id', '=', 'lab_packages.tag')
                            ->where('lab_packages.lab_id',$input['lab_id'])
                            ->where('lab_packages.status',1)
                            ->where('lab_packages.is_popular',1)
                            ->select('lab_packages.*','lab_relevances.relevance_name','lab_tags.tag_name')
                            ->get();

        $tags = DB::table('lab_tags')->where('status',1)->select('id','tag_name')->get();
        
        $data['common_packages']= [];
        foreach($tags as $key => $value){
            $tags[$key]->data = DB::table('lab_packages')
                            ->leftJoin('lab_relevances', 'lab_relevances.id', '=', 'lab_packages.lab_relevance_id')
                            ->leftJoin('lab_tags', 'lab_tags.id', '=', 'lab_packages.tag')
                            ->where('lab_packages.lab_id',$input['lab_id'])
                            ->where('lab_packages.status',1)
                            ->where('lab_packages.tag',$value->id)
                            ->select('lab_packages.*','lab_relevances.relevance_name','lab_tags.tag_name')
                            ->get();
        }
                            
           $data['common_packages'] = $tags;     
            return response()->json([
                "result" => $data,
                "message" => 'Success',
                "status" => 1
            ]);
    }
    
    
    public function get_lab_packages(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
            'lab_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        if(!$input['relevance_id']){
            $data = DB::table('lab_packages')
                                ->leftJoin('lab_relevances', 'lab_relevances.id', '=', 'lab_packages.lab_relevance_id')
                                ->where('lab_packages.lab_id',$input['lab_id'])
                                ->where('lab_packages.status',1)
                                ->select('lab_packages.*','lab_relevances.relevance_name')
                                ->get();
        }else{
            $data = DB::table('lab_packages')
                                ->leftJoin('lab_relevances', 'lab_relevances.id', '=', 'lab_packages.lab_relevance_id')
                                ->where('lab_packages.lab_id',$input['lab_id'])
                                ->where('lab_packages.status',1)
                                ->where('lab_packages.lab_relevance_id',$input['relevance_id'])
                                ->select('lab_packages.*','lab_relevances.relevance_name')
                                ->get();
        }
            return response()->json([
                "result" => $data,
                "count" => count($data),
                "message" => 'Success',
                "status" => 1
            ]);
    }
    
     public function lab_package_detail(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
            'package_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $data = DB::table('lab_packages')
                            ->leftJoin('lab_relevances', 'lab_relevances.id', '=', 'lab_packages.lab_relevance_id')
                            ->where('lab_packages.id',$input['package_id'])
                            ->select('lab_packages.*','lab_relevances.relevance_name')
                            ->first();
                            
                    $data->lab_process_steps = DB::table('lab_process_steps')->get();
                
            return response()->json([
                "result" => $data,
                "message" => 'Success',
                "status" => 1
            ]);
    }
    
    public function place_order(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'customer_id' => 'required',
            'patient_name' => 'required',
            'patient_dob' => 'required',
            'patient_gender' => 'required',
            'lab_id' => 'required',
            'address_id' => 'required',
            'promo_id' => 'required',
            'discount' => 'required',
            'tax' => 'required',
            'sub_total' => 'required',
            'total' => 'required',
            'payment_mode' => 'required',
            'items' =>'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
       
        $input['status'] = 1;
        $items = json_decode(stripslashes($input['items']), true);
        $date = explode('/',$input['patient_dob']);
        $input['patient_dob'] = date('Y-m-d', strtotime($input['patient_dob']));
        $order = LabOrder::create($input);
        Customer::where('id',$input['customer_id'])->update([ 'last_active_address'=>$input['address_id']]);
        if (is_object($order)) {
            foreach ($items as $key => $value) {
                if($value){
                   $value['order_id'] = $order->id;
                    LabOrderItem::create($value); 
                }
                
            }
            //$this->find_fcm_message('lab_order_status_'.$order->status,$order->customer_id,0,0);
            $this->order_registers($order->id);
            $this->update_status($order->id,$input['status']);
            return response()->json([
                "message" => 'Order Placed Successfully',
                "status" => 1
            ]);
        } else {
            return response()->json([
                "message" => 'Sorry, something went wrong !',
                "status" => 0
            ]);
        }
    }

    
    public function update_status($id,$status){
        
            $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
                $database = $factory->createDatabase();
                $database->getReference('lab_orders/'.$id)
                ->update([
                    'status' => $status
                ]);
                
    }
    
     public function order_registers($id){
        
        $app_setting = CustomerAppSetting::where('id',1)->first();
        
        $data = array();
        $orders = LabOrder::where('id',$id)->first();
        $customer = Customer::where('id',$orders->customer_id)->first();
        $data['order_id'] = $orders->id;
        $data['logo'] = $app_setting->app_logo;
        $data['name'] = $customer->customer_name;
        $data['admin_address'] = $app_setting->address;
        $data['address'] = Address::where('id',$orders->address_id)->value('address');
        $data['items'] = json_decode($orders->items, TRUE);
        $data['discount'] = $orders->discount;
        $data['tax'] = $orders->tax;
        $data['sub_total'] = $orders->sub_total;
        $data['total'] = $orders->total;
        $data['special_instruction'] = $orders->special_instruction;
        $data['payment_mode'] = PaymentMode::where('id',$orders->payment_mode)->value('payment_name');
        $data['package'] = LabPackage::where('id',$orders->package_id)->value('package_name');
        $mail_header = array("data" => $data);
        //$this->send_order_mail($mail_header,'Order Placed Successfully',$customer->email,'mail_templates.lab_invoice');
        //$this->send_order_mail($mail_header,'New order Received',$app_setting->email,'mail_templates.lab_new_order');
    }
    
    public function send_order_mail($mail_header,$subject,$to_mail,$template){
    	Mail::send($template, $mail_header, function ($message)
		 use ($subject,$to_mail) {
			$message->from(env('MAIL_USERNAME'), env('APP_NAME'));
			$message->subject($subject);
			$message->to($to_mail);
		});
    }
    
    public function get_lab_orders(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'customer_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $orders = DB::table('lab_orders')
            ->leftJoin('addresses', 'addresses.id', '=', 'lab_orders.address_id')
            ->leftJoin('laboratories', 'laboratories.id', '=', 'lab_orders.lab_id')
            ->leftJoin('lab_collective_people', 'lab_collective_people.id', '=', 'lab_orders.collective_person')
            ->leftJoin('lab_order_statuses', 'lab_order_statuses.id', '=', 'lab_orders.status')
            ->leftJoin('payment_modes', 'payment_modes.id', '=', 'lab_orders.payment_mode')
            ->select('lab_orders.*','addresses.address','lab_order_statuses.slug','lab_order_statuses.status','lab_order_statuses.status_for_customer','payment_modes.payment_name', 'laboratories.lab_name as lab_name', 'laboratories.address as lab_address','laboratories.lab_image','laboratories.phone_number as lab_phone_number','laboratories.phone_with_code as lab_phone_with_code','lab_collective_people.name as collective_person_name',)
            ->where('lab_orders.customer_id',$input['customer_id'])
            ->orderBy('lab_orders.created_at', 'desc')
            ->get();
            
            foreach($orders as $key => $value){
                $orders[$key]->item_list = DB::table('lab_order_items')
                ->leftJoin('lab_packages', 'lab_packages.id', '=', 'lab_order_items.item_id')
                ->select('lab_order_items.*','lab_packages.short_description','lab_packages.package_image')
                ->where('order_id',$value->id)
                ->get();
            }
        if ($orders) {
            return response()->json([
                "result" => $orders,
                "count" => count($orders),
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
    
    
     public function get_order_detail(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'order_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $orders = DB::table('lab_orders')
            ->leftJoin('addresses', 'addresses.id', '=', 'lab_orders.address_id')
            ->leftJoin('laboratories', 'laboratories.id', '=', 'lab_orders.lab_id')
            ->leftJoin('lab_collective_people', 'lab_collective_people.id', '=', 'lab_orders.collective_person')
            ->leftJoin('lab_order_statuses', 'lab_order_statuses.id', '=', 'lab_orders.status')
            ->leftJoin('payment_modes', 'payment_modes.id', '=', 'lab_orders.payment_mode')
            ->select('lab_orders.*','addresses.address','lab_order_statuses.slug','lab_order_statuses.status','lab_order_statuses.status_for_customer','payment_modes.payment_name', 'laboratories.lab_name as lab_name', 'laboratories.address as lab_address','laboratories.lab_image','laboratories.phone_number as lab_phone_number','laboratories.phone_with_code as lab_phone_with_code')
            ->where('lab_orders.id',$input['order_id'])
            ->first();
            
            $orders->item_list = DB::table('lab_order_items')
                ->leftJoin('lab_packages', 'lab_packages.id', '=', 'lab_order_items.item_id')
                ->select('lab_order_items.*','lab_packages.short_description','lab_packages.package_image')
                ->where('order_id',$orders->id)
                ->get();
            if($orders->collective_person){
            $collective_person = DB::table('lab_collective_people')->where('id',$orders->collective_person)->first();
                if(is_object($collective_person)){
                    $orders->collective_person_name = $collective_person->name;
                    $orders->collective_person_phone_number = $collective_person->phone_number;
                }
            }
        if ($orders) {
            return response()->json([
                "result" => $orders,
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
    
     public function get_xray_list(Request $request)
    {   
        $input = $request->all();
        $validator = Validator::make($input, [
            'lat' => 'required',
            'lng' => 'required'
            
        ]);

        if ($validator->fails()) {
          return $this->sendError($validator->errors());
        }
        $lab_radius = LaboratoryAppSetting::where('id',1)->value('lab_radius');
        $result = [];
        
        $data = DB::table('laboratories')
            ->leftJoin('lab_services', 'lab_services.lab_id', '=', 'laboratories.id')
            ->leftJoin('services', 'services.id', '=', 'lab_services.service_id')
            ->select('laboratories.*','lab_services.service_id')
            ->where('laboratories.status',1)
            ->where('lab_services.service_id','!=',1)
            ->get();
            foreach($data as $key => $value){
                $distance =  $this->distance($value->lat,$value->lng,$input['lat'],$input['lng'],'K');
                if($distance <= $lab_radius){
                    $data[$key]->providing_service = DB::table('services')->where('id',$value->service_id)->first();
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
    
    public function place_xray_order(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'customer_id' => 'required',
            'patient_name' => 'required',
            'patient_age' => 'required',
            'patient_gender' => 'required',
            'lab_id' => 'required',
            'address_id' => 'required',
            'appointment_date' => 'required',
            'appointment_time' => 'required',
            'special_instruction' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
       
        $input['status'] = 1;
        $input['appointment_time'] = date('H:i:s', strtotime($input['appointment_time']));
        $date = explode('/',$input['appointment_date']);
        $input['appointment_date'] = date('Y-m-d', strtotime($input['appointment_date']));
        $xray_order = XrayOrder::create($input);
        //Customer::where('id',$input['customer_id'])->update([ 'last_active_address'=>$input['address_id']]);
        if (is_object($xray_order)) {
            //$this->find_fcm_message('lab_order_status_'.$order->status,$order->customer_id,0,0);
            //$this->order_registers($order->id);
            //$this->update_status($order->id,$input['status']);
            return response()->json([
                "message" => 'Requset sent successfully',
                "status" => 1
            ]);
        } else {
            return response()->json([
                "message" => 'Sorry, something went wrong !',
                "status" => 0
            ]);
        }
    }
    
    public function get_xray_orders(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'customer_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $orders = DB::table('xray_orders')
            ->leftJoin('addresses', 'addresses.id', '=', 'xray_orders.address_id')
            ->leftJoin('laboratories', 'laboratories.id', '=', 'xray_orders.lab_id')
            ->leftJoin('xray_order_statuses', 'xray_order_statuses.id', '=', 'xray_orders.status')
            ->select('xray_orders.*','addresses.address','xray_order_statuses.slug','xray_order_statuses.status','xray_order_statuses.status_for_customer','laboratories.lab_name', 'laboratories.address as lab_address','laboratories.lab_image','laboratories.phone_number as lab_phone_number','laboratories.phone_with_code as lab_phone_with_code')
            ->where('xray_orders.customer_id',$input['customer_id'])
            ->orderBy('xray_orders.created_at', 'desc')
            ->get();
        if ($orders) {
            return response()->json([
                "result" => $orders,
                "count" => count($orders),
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
    
    public function get_lab_promo(Request $request)
    {   
        $input = $request->all();
        $validator = Validator::make($input, [
            'customer_id' => 'required',
            'lab_id' => 'required',
            
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $data = LabPromoCode::where('status',1)->whereIn('lab_id',[ 0, $input['lab_id']])->whereIn('customer_id',[ 0, $input['customer_id']])->get();
        
        foreach($data as $key => $value){
            if($value->redemptions){
                $check_redemptions = CustomerPromoHistory::where('customer_id',$input['customer_id'])->where('promo_id',$value->promo_id)->count();
                if($check_redemptions >= $value->redemptions){
                    unset($data[$key]);
                }
            }    
        }
        
        return response()->json([
            "result" => $data,
            "count" => count($data),
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
     public function check_lab_promo(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
            'lab_id' => 'required',
            'customer_id' => 'required',
            'promo_code' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $promo = LabPromoCode::where('promo_code',$input['promo_code'])->where('status',1)->first();
        if(is_object($promo)){
            $using_count = CustomerPromoHistory::where('customer_id',$input['customer_id'])->where('promo_id',$promo->id)->count();
            if($promo->lab_id == 0 || $promo->lab_id == $input['lab_id']){
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
    public function sendError($message) {
        $message = $message->all();
        $response['error'] = "validation_error";
        $response['message'] = implode('',$message);
        $response['status'] = "0";
        return response()->json($response, 200);
    } 
}

