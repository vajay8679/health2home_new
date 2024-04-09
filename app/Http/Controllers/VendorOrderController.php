<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\Customer;
use App\Models\Address;
use App\Models\Vendor;
use App\Models\ProductType;
use App\Models\SubCategory;
use App\Models\CustomerAppSetting;
use App\Models\VendorAppSetting;
use App\Models\Order;
use App\Models\Hospital;
use App\Models\OrderStatus;
use App\Models\OrderItem;
use App\Models\CustomerWalletHistory;
use App\Models\CustomerPromoHistory;
use App\Models\HospitalPatient;
use App\Models\HospitalPatientHistory;
use App\Models\PaymentMode;
use App\Models\PartnerRejection;
use App\Models\OrderCommission;
use App\Models\VendorWalletHistory;
use App\Models\VendorEarning;
use App\Models\HospitalEarning;
use App\Models\HospitalWalletHistory;
use App\Models\CommissionSetting;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Database;

class VendorOrderController extends Controller
{
    
   public function vendor_list(Request $request)
    {   
        $input = $request->all();
        $validator = Validator::make($input, [
            'lat' => 'required',
            'lng' => 'required',
            
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $search = $input['search'];
        $data = Vendor::where('status',1)->where('document_approved_status',1)->where('address_update_status',1)->where('store_name', 'LIKE', "%$search%")->orderBy('online_status', 'DESC')->get();
        $count = count($data);
        $vendor_radius = CustomerAppSetting::where('id',1)->value('vendor_radius');
        $result = [];
        $recommended = [];
        if($count){
            if(env('MODE') != 'DEMO'){
                foreach($data as $key => $value){
                     $distance =  $this->distance($value->latitude,$value->longitude,$input['lat'], $input['lng'],'K');
                     if($distance <= $vendor_radius){
                         array_push($result,$value);
                         if($value->is_recommended){
                             array_push($recommended,$value);
                         }
                     }
                     
                }
            }else{
                $result = $data;
                foreach($data as $key => $value){
                    if($value->is_recommended){
                         array_push($recommended,$value);
                     }
                }
            }
        }
        
        $results['vendor_list'] = $result;
        $results['recommended'] = $recommended;
        return response()->json([
            "result" => $results,
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
          return ($miles * 1.609344);
      } else if ($unit == "N") {
          return ($miles * 0.8684);
      } else {
          return $miles;
      }
    }
    
    public function vendor_detail(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
            'vendor_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $vendor = Vendor::where('id',$input['vendor_id'])->first();
        if(is_object($vendor)){
            return response()->json([
                "result" => $vendor,
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
    
    public function vendor_category(Request $request){
        
        $input = $request->all();
        $validator = Validator::make($input, [
            'vendor_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data = DB::table('categories')
            ->where('status', 1)
            ->where('vendor_id', $input['vendor_id'])
            ->get();
        
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
    public function vendor_sub_category(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
            'vendor_id' => 'required',
            'category_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $data = SubCategory::where('vendor_id',$input['vendor_id'])->where('status',1)->where('category_id',$input['category_id'])->get();
        
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
    public function vendor_products(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
            'vendor_id' => 'required',
            'sub_category_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $data = DB::table('products')
                            ->join('unit_measurements', 'unit_measurements.id', '=', 'products.unit_id')
                            ->where('products.vendor_id',$input['vendor_id'])
                            ->where('products.sub_category_id',$input['sub_category_id'])
                            ->select('products.*','unit_measurements.unit')
                            ->get();
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
            'vendor_id' => 'required',
            'total' => 'required',
            'discount' => 'required',
            'sub_total' => 'required',
            'promo_id' => 'required',
            'payment_mode' => 'required',
            'items' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
       
        $input['status'] = 1;
        //$input['address_id'] = Customer::where('id',$input['customer_id'])->value('last_active_address');
        $items = json_decode(stripslashes($input['items']), true);
        $input['vendor_percent'] = VendorAppSetting::where('id',1)->value('order_commission');
        if($input['payment_mode'] == 3){
            $payment = $this->deduct_wallet($input['customer_id'],$input['total']);
            if($payment == 0){
                return response()->json([
                    "message" => 'Your wallet balance is low!',
                    "status" => 0
                ]);
            }
        }
        $order = Order::create($input);
        if (is_object($order)) {
            foreach ($items as $key => $value) {
                $value['order_id'] = $order->id;
                unset($value['price_per_item'], $value['image']);
                OrderItem::create($value);
            }
            
            //$this->find_fcm_message('order_status_'.$order->status,$order->customer_id,0,0);
            //$this->order_registers($order->id);
            $this->check_vendor_booking($order->vendor_id);
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
    
    public function deduct_wallet($customer_id,$amount){
        
        $old_wallet = Customer::where('id',$customer_id)->value('wallet');
        if($old_wallet < $amount){
            return 0;
        }
        $data['customer_id'] = $customer_id;
        $data['type'] = 1;
        $data['message'] ="Paid by wallet";
        $data['amount'] = $amount;
        $data['transaction_type'] = 2;
        CustomerWalletHistory::create($data);
    
        $new_wallet = $old_wallet - $amount;
        Customer::where('id',$customer_id)->update([ 'wallet' => $new_wallet ]);
        
        return 1;
    }
    
    /*public function update_vendor_booking($vendor_id){
        $order = Order::where('vendor_id',$vendor_id)->where('status',1)->first();
        $customer_name = Customer::where('id',$order->customer_id)->value('customer_name');
        if(is_object($order)){
            $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
            $database = $factory->createDatabase();
            //$database = $firebase->getDatabase();
            $database->getReference('vendors/'.$vendor_id)
                ->update([
                    'o_stat' => 1,
                    'o_id' => $order->id,
                    'cus_name' => $customer_name
                ]);
        }
    }*/
    
    public function order_registers($id){
        
        $app_setting = AppSetting::where('id',1)->first();
        
        $data = array();
        $orders = Order::where('id',$id)->first();
        $customer = Customer::where('id',$orders->customer_id)->first();
        $data['order_id'] = $orders->order_id;
        $data['logo'] = $app_setting->logo;
        $data['name'] = $customer->customer_name;
        $data['admin_address'] = $app_setting->address;
        $data['address'] = Address::where('id',$orders->address_id)->value('address');
        $data['items'] = json_decode($orders->items, TRUE);
        $data['total'] = $orders->total;
        $data['discount'] = $orders->discount;
        $data['delivery_charge'] = $orders->delivery_charge;
        $data['sub_total'] = $orders->sub_total;
        $data['tax'] = $orders->tax;
        $data['payment_mode'] = PaymentMode::where('id',$orders->payment_mode)->value('payment_name');
        $mail_header = array("data" => $data);
        //$this->send_order_mail($mail_header,'Order Placed Successfully',$customer->email,'mail_templates.invoice');
        //$this->send_order_mail($mail_header,'New order Received',$app_setting->email,'mail_templates.new_order');
    }
    
    public function send_order_mail($mail_header,$subject,$to_mail,$template){
    	Mail::send($template, $mail_header, function ($message)
		 use ($subject,$to_mail) {
			$message->from(env('MAIL_USERNAME'), env('APP_NAME'));
			$message->subject($subject);
			$message->to($to_mail);
		});
    }
    
    public function get_vendor_order_list(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'vendor_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $orders = DB::table('orders')
            ->leftJoin('addresses', 'addresses.id', '=', 'orders.address_id')
            ->leftJoin('customers', 'customers.id', '=', 'orders.customer_id')
            ->leftJoin('order_statuses', 'order_statuses.id', '=', 'orders.status')
            ->leftJoin('taxes', 'taxes.id', '=', 'orders.tax')
            ->leftJoin('payment_modes', 'payment_modes.id', '=', 'orders.payment_mode')
            ->select('orders.id','orders.payment_mode','orders.delivery_charge','addresses.address','orders.total','orders.vendor_percent','orders.discount','orders.tax','orders.sub_total','orders.status','orders.items','order_statuses.type as status_type','order_statuses.slug','order_statuses.status','payment_modes.payment_name','orders.created_at','orders.updated_at', 'orders.customer_id', 'customers.phone_number','customers.phone_with_code', 'customers.customer_name', 'taxes.tax','order_statuses.id as status_id')
            ->where('orders.vendor_id',$input['vendor_id'])
            ->orderBy('orders.created_at', 'desc')
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
    
    public function get_vendor_order_detail(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'order_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $orders = DB::table('orders')
            ->leftJoin('addresses', 'addresses.id', '=', 'orders.address_id')
            ->leftJoin('customers', 'customers.id', '=', 'orders.customer_id')
            ->leftJoin('order_statuses', 'order_statuses.id', '=', 'orders.status')
            ->leftJoin('taxes', 'taxes.id', '=', 'orders.tax')
            ->leftJoin('payment_modes', 'payment_modes.id', '=', 'orders.payment_mode')
            ->select('orders.id','orders.payment_mode','orders.delivery_charge','addresses.address','orders.total','orders.vendor_percent','orders.discount','orders.tax','orders.sub_total','orders.status','orders.items','order_statuses.type as status_type','order_statuses.slug','order_statuses.status','payment_modes.payment_name','orders.created_at','orders.updated_at', 'orders.customer_id', 'customers.phone_number','customers.phone_with_code', 'customers.customer_name', 'taxes.tax','order_statuses.id as status_id')
            ->where('orders.id',$input['order_id'])
            ->first();
        
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
    
    public function vendor_order_accept(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'order_id' => 'required',
            'type' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        if($input['type'] == "accept"){
            $status = Db::table('order_statuses')->where('slug','vendor_approved')->value('id');
        }else{
            $status = Db::table('order_statuses')->where('slug','vendor_rejected')->value('id');
        }
        Order::where('id',$input['order_id'])->update([ 'status' => $status]);
        $order = Order::where('id',$input['order_id'])->first();
        $payment_type = PaymentMode::where('id',$order->payment_mode)->value('slug');
        if($input['type']  == "reject" && $payment_type != "cash"){
            $old_wallet = Customer::where('id',$order->customer_id)->value('wallet');
            $new_wallet = $old_wallet + $order->total;
            Customer::where('id',$order->customer_id)->update([ 'wallet' => $new_wallet ]);
            
            $data['customer_id'] = $order->customer_id;
            $data['type'] = 1;
            $data['message'] ="Amount refunded to wallet";
            $data['amount'] = $order->total;
            $data['transaction_type'] = 2;
            CustomerWalletHistory::create($data); 
        }
        $this->check_vendor_booking($order->vendor_id);
        $this->update_status($input['order_id'],$status);
        //$this->find_fcm_message('order_status_'.$order->status,$order->customer_id,0,0);
        return response()->json([
            "message" => 'Success',
            "status" => 1
        ]);
    }

   public function check_vendor_booking($id){
        $ven_data = DB::table('vendors')->where('id',$id)->first();
        
            $count = DB::table('orders')->where('vendor_id',$id)->where('status',1)->count();
            $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
            $database = $factory->createDatabase();
            if($count == 0){
                DB::table('vendors')->where('id',$id)->update(['order_status' => 0]);
                $database->getReference('vendors/'.$id)
                ->update([
                    'o_stat' => 0,
                ]);
            }else{
                if(!DB::table('vendors')->where('id',$id)->value('order_status')){
                    DB::table('vendors')->where('id',$id)->update(['order_status' => 1]);
                    $database->getReference('vendors/'.$id)
                    ->update([
                        'o_stat' => 1,
                    ]);
                }
            }
    }
    
    public function update_status($id,$status){
        $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
                $database = $factory->createDatabase();
                $database->getReference('pharm_orders/'.$id)
                ->update([
                    'status' => $status
                ]);
                
    }
    
    public function order_status_change(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'order_id' => 'required',
            'slug' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $order = Order::where('id',$input['order_id'])->first();
        $payment_type = PaymentMode::where('id',$order->payment_mode)->value('slug');
        $order_slug = DB::table('order_statuses')->where('id',$order->status)->value('slug');
        $status = DB::table('order_statuses')->where('slug',$input['slug'])->value('id');
        if(is_object($order)){
            Order::where('id',$input['order_id'])->update([ 'status' => $status ]);
        }
        
        if($input['slug'] == "ready_to_dispatch"){
            $this->find_partner($input['order_id']);
        }else if($input['slug'] == "delivered"){
            $this->commission_calculations($input['order_id'],$order->vendor_id);
            $hospital_id = Vendor::where('id',$order->vendor_id)->value('hospital_id');
            if($hospital_id){
                $this->store_patient_history($order->customer_id,$hospital_id);
            }
            $this->update_deliveryboy_status($order->delivered_by);
            $this->update_promo_histories($order->promo_id,$order->id);
            
        }else if($input['slug'] == "cancelled_by_customer"){
            if($payment_type != "cash" && $order_slug == "order_placed"){
                $this->customer_wallet_update($order->customer_id,$order->total,'Amount refunded to wallet for order cancellation #'.$input['order_id'],1,2);
            }else if($order_slug != "order_placed"){
                $this->customer_wallet_update($order->customer_id,$order->total,'Cancellation charge deducted from your wallet #'.$input['order_id'],2,3);
            }
        }else if($input['slug'] == "cancelled_by_vendor" || $input['slug'] == "cancelled_by_deliveryboy"){
            if($payment_type != "cash"){
                $this->customer_wallet_update($order->customer_id,$order->total,'Amount refunded to wallet for order cancellation #'.$input['order_id'],1,2);
            }
        }
        $this->update_status($input['order_id'],$status);
        $this->find_fcm_message('order_status_'.$status,$order->customer_id,0,0);
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
                "hospital_patient_id" => $patient_id, "date" => date("Y-m-d H:i:s"), "purpose_of_visit" => "For medicine order"
            ]);
        }else{
            $id = HospitalPatient::create([ "hospital_id" => $hospital_id, "patient_name" => $customer->customer_name, "phone_number" => $customer->phone_number ])->id;
            HospitalPatientHistory::create([
                "hospital_patient_id" => $id, "date" => date("Y-m-d H:i:s"), "purpose_of_visit" => "For medicine order"
            ]);
        }
        
    }
    
    public function customer_wallet_update($customer_id,$amount,$message,$type,$transaction_type){
        $old_wallet = Customer::where('id',$customer_id)->value('wallet');
        if($type == 1){
            $new_wallet = $old_wallet + $amount;
        }else{
            $new_wallet = $old_wallet - $amount;
        }
        
        Customer::where('id',$customer_id)->update([ 'wallet' => $new_wallet ]);
        
        $data['customer_id'] = $customer_id;
        $data['type'] = $type;
        $data['message'] = $message;
        $data['amount'] = $amount;
        $data['transaction_type'] = $transaction_type;
        CustomerWalletHistory::create($data); 
    }
    
    public function update_promo_histories($promo_id,$customer_id){
        if($promo_id){
            CustomerPromoHistory::create([ 'customer_id' => $customer_id, "promo_id" =>$promo_id ]);
        }
    }
    
    public function update_deliveryboy_status($del_id){
        $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
            $database = $factory->createDatabase();
            $database->getReference('delivery_partners/'.$del_id)
                ->update([
                    'o_id' => 0,
                    'o_stat' => 0
                ]);
                
    }
    
    public function find_partner($order_id)
    {
        $order = DB::table('orders')
                 ->leftjoin('vendors','vendors.id','orders.vendor_id')
                 ->select('orders.*','vendors.latitude','vendors.longitude')
                 ->where('orders.id',$order_id)->first();
        
        $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
        $database = $factory->createDatabase();
        
        $partners = $database->getReference('/delivery_partners')
                    ->getSnapshot()->getValue();
        
        $rejected_partners = PartnerRejection::where('order_id',$order_id)->pluck('partner_id')->toArray();
        $min_partner_id = 0;
        $min_distance = 0;
        $booking_searching_radius = DB::table('delivery_boy_app_settings')->where('id',1)->value('booking_searching_radius');
        
        $i=0;
        foreach($partners as $key => $value){
            if(is_array($value)){
                if($value['o_stat'] == 0 && @$value['on_stat'] == 1){
                    if(!in_array($value['p_id'], $rejected_partners)){
                        $distance = $this->distance($order->latitude, $order->longitude, $value['lat'], $value['lng'], 'K') ;
                        
                            if($distance <= $booking_searching_radius){
                             
                                if($min_distance == 0 && $i == 0){
                                    $min_distance = $distance;
                                    $min_partner_id = $value['p_id'];
                                }else if($distance < $min_distance){
                                    $min_distance = $distance;
                                    $min_partner_id = $value['p_id'];
                                }
                                $i++;
                            }
                    }   
                }
            }
            
        }    
        
        if($min_partner_id != 0){
            $newPost = $database
            ->getReference('delivery_partners/'.$min_partner_id)
            ->update([
                'o_stat' => 1,
                'o_id' => $order_id
            ]);
        }
    }
    
    public function commission_calculations($order_id,$vendor_id){
        $hospital_id = DB::table('vendors')->where('id',$vendor_id)->value('hospital_id');
        $order = Order::where('id',$order_id)->first();
        $commission_type = VendorAppSetting::value('commission_type');
        if($commission_type == 1){
            $admin_percent = VendorAppSetting::where('id',1)->value('order_commission');
        }else{
            $admin_percent = Vendor::where('id',$vendor_id)->value('order_commission');
        }
        
        $admin_commission = ($order->total / 100) * $admin_percent; 
        $admin_commission = number_format((float)$admin_commission, 2, '.', '');
        
        $vendor_commission = $order->total - $admin_commission;
        $vendor_commission = number_format((float)$vendor_commission, 2, '.', '');
        
        if($hospital_id){
            HospitalEarning::create([ 'hospital_id' => $hospital_id, 'type' => 3, 'ref_id' => $order_id, 'source_id' => $vendor_id, 'amount' => $vendor_commission]);
            HospitalWalletHistory::create([ 'hospital_id' => $hospital_id, 'type' => 1, 'message' => 'Your earnings credited for this order #'.$order->id, 'amount' => $vendor_commission]);
            
            $wallet = Hospital::where('id',$hospital_id)->value('wallet');
            $new_wallet = $wallet + $vendor_commission;
            $new_wallet = number_format((float)$new_wallet, 2, '.', '');
            
            Hospital::where('id',$hospital_id)->update([ 'wallet' => $new_wallet]);
        }else{
            $order_commission['order_id'] = $order_id;
            $order_commission['role'] = 'restaurant';
            $order_commission['user_id'] = $order->vendor_id;
            $order_commission['amount'] = $vendor_commission;
            OrderCommission::create($order_commission);
            
            
            $order_commission['order_id'] = $order_id;
            $order_commission['role'] = 'admin';
            $order_commission['user_id'] = 1;
            $order_commission['amount'] = $admin_commission;
            OrderCommission::create($order_commission);
            
            VendorEarning::create([ 'order_id' => $order_id, 'vendor_id' => $order->vendor_id, 'amount' => $vendor_commission]);
            VendorWalletHistory::create([ 'vendor_id' => $order->vendor_id, 'type' => 1, 'message' => 'Your earnings credited for this order #'.$order->id, 'amount' => $vendor_commission]);
            
            $wallet = Vendor::where('id',$order->vendor_id)->value('wallet');
            $new_wallet = $wallet + $vendor_commission;
            $new_wallet = number_format((float)$new_wallet, 2, '.', '');
            
            Vendor::where('id',$order->vendor_id)->update([ 'wallet' => $new_wallet]);
        }
        
        
    }
    
    public function get_customer_order_list(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'customer_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $orders = DB::table('orders')
            ->leftJoin('addresses', 'addresses.id', '=', 'orders.address_id')
            ->leftJoin('vendors', 'vendors.id', '=', 'orders.vendor_id')
            ->leftJoin('order_statuses', 'order_statuses.id', '=', 'orders.status')
            ->leftJoin('payment_modes', 'payment_modes.id', '=', 'orders.payment_mode')
            ->leftJoin('taxes', 'taxes.id', '=', 'orders.tax')
            ->select('orders.id','orders.vendor_id','orders.customer_id','orders.payment_mode','orders.delivery_charge','addresses.address','orders.total','orders.discount','orders.rating','orders.tax','orders.sub_total','orders.status','orders.items','order_statuses.type as status_type','order_statuses.slug','order_statuses.status','payment_modes.payment_name','orders.created_at','orders.updated_at', 'vendors.store_name', 'vendors.manual_address', 'vendors.store_image','taxes.tax','order_statuses.id as status_id')
            ->where('orders.customer_id',$input['customer_id'])
            ->orderBy('orders.created_at', 'desc')
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
    
    public function get_customer_order_detail(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'order_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $orders = DB::table('orders')
            ->leftJoin('addresses', 'addresses.id', '=', 'orders.address_id')
            ->leftJoin('vendors', 'vendors.id', '=', 'orders.vendor_id')
            ->leftJoin('order_statuses', 'order_statuses.id', '=', 'orders.status')
            ->leftJoin('payment_modes', 'payment_modes.id', '=', 'orders.payment_mode')
            ->leftJoin('taxes', 'taxes.id', '=', 'orders.tax')
            ->select('orders.id','orders.vendor_id','orders.customer_id','orders.payment_mode','orders.delivery_charge','addresses.address','orders.total','orders.discount','orders.rating_update_status','orders.tax','orders.sub_total','orders.status','orders.items','order_statuses.type as status_type','order_statuses.slug','order_statuses.status','payment_modes.payment_name','orders.created_at','orders.updated_at', 'vendors.store_name', 'vendors.manual_address', 'taxes.tax','order_statuses.id as status_id')
            ->where('orders.id',$input['order_id'])
            ->first();
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
    
    public function get_new_status(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'status' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $status = OrderStatus::select('id','status')->get();
        //print_r($data);exit;
        if($input['status'] < 6 ){
            $new_status = OrderStatus::where('id',$input['status']+1)->first();
            //$this->find_fcm_message('booking_confirm_status_'.$status->id=2,$data->patient_id,0,0);
            return response()->json([
                "result" => $new_status,
                "message" => 'Success',
                "status" => 1
            ]);
        }else {
            //$this->find_fcm_message('booking_confirm_status_'.$status->id=3,$data->patient_id,0,0);
            return response()->json([
                "message" => 'New Status Not Available',
                "status" => 0
            ]);
        }
    
    }
    
    public function get_deliveryboy_order_list(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'delivery_boy_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $orders = DB::table('orders')
            ->leftJoin('addresses', 'addresses.id', '=', 'orders.address_id')
            ->leftJoin('customers', 'customers.id', '=', 'orders.customer_id')
            ->leftJoin('delivery_boys', 'delivery_boys.id', '=', 'orders.delivered_by')
            ->leftJoin('vendors', 'vendors.id', '=', 'orders.vendor_id')
            ->leftJoin('order_statuses', 'order_statuses.id', '=', 'orders.status')
            ->leftJoin('payment_modes', 'payment_modes.id', '=', 'orders.payment_mode')
            ->leftJoin('taxes', 'taxes.id', '=', 'orders.tax')
            ->select('customers.customer_name','orders.id','orders.vendor_id','orders.delivered_by','orders.customer_id','orders.payment_mode','orders.delivery_charge','addresses.address as cus_address','addresses.lat as cus_lat','addresses.lng as cus_lng','orders.total','orders.discount','orders.tax','orders.sub_total','orders.status','orders.items','order_statuses.type as status_type','order_statuses.slug','order_statuses.status','payment_modes.payment_name','orders.created_at','orders.updated_at', 'vendors.store_name', 'vendors.manual_address','vendors.latitude','vendors.longitude', 'taxes.tax','order_statuses.id as status_id')
            ->where('orders.delivered_by',$input['delivery_boy_id'])
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
    
    public function get_deliveryboy_order_detail(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'order_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $orders = DB::table('orders')
            ->leftJoin('addresses', 'addresses.id', '=', 'orders.address_id')
            ->leftJoin('delivery_boys', 'delivery_boys.id', '=', 'orders.delivered_by')
            ->leftJoin('vendors', 'vendors.id', '=', 'orders.vendor_id')
            ->leftJoin('order_statuses', 'order_statuses.id', '=', 'orders.status')
            ->leftJoin('payment_modes', 'payment_modes.id', '=', 'orders.payment_mode')
            ->leftJoin('taxes', 'taxes.id', '=', 'orders.tax')
            ->select('orders.id','orders.vendor_id','orders.delivered_by','orders.customer_id','orders.payment_mode','orders.delivery_charge','addresses.address as cus_address','addresses.lat as cus_lat','addresses.lng as cus_lng','orders.total','orders.discount','orders.tax','orders.sub_total','orders.status','orders.items','order_statuses.type as status_type','order_statuses.slug','order_statuses.status','payment_modes.payment_name','orders.created_at','orders.updated_at', 'vendors.store_name', 'vendors.manual_address','vendors.latitude','vendors.longitude', 'taxes.tax','order_statuses.id as status_id')
            ->where('orders.id',$input['order_id'])
            ->first();
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
    
    public function partner_accept(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'order_id' => 'required',
            'partner_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        DB::table('orders')->where('id',$input['order_id'])->update(['delivered_by' => $input['partner_id']]);
        
        $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
        $database = $factory->createDatabase();
        
        $newPost = $database
        ->getReference('delivery_partners/'.$input['partner_id'])
        ->update([
            'o_stat' => 2
        ]);
  
        return response()->json([
            "message" => 'Success',
            "status" => 1
        ]);
        
    }
    
    public function partner_reject(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'order_id' => 'required',
            'partner_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
         DB::table('orders')->where('id',$input['order_id'])->update(['delivered_by' => 0 ]);
        
        $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
        $database = $factory->createDatabase();
        
        $newPost = $database
        ->getReference('delivery_partners/'.$input['partner_id'])
        ->update([
            'o_stat' => 0,
            'o_id' => 0
        ]);
        
        
        $data['partner_id'] = $input['partner_id'];
        $data['order_id'] = $input['order_id'];
        PartnerRejection::create($data);
        
        $this->find_partner($input['order_id']);
        
        return response()->json([
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
    public function partner_cron()
    {
       
        $orders = DB::table('orders')
            ->leftJoin('addresses', 'addresses.id', '=', 'orders.address_id')
            ->leftJoin('customers', 'customers.id', '=', 'orders.customer_id')
            ->leftJoin('order_statuses', 'order_statuses.id', '=', 'orders.status')
            ->leftJoin('payment_modes', 'payment_modes.id', '=', 'orders.payment_mode')
            ->select('orders.*','order_statuses.status','order_statuses.slug','payment_modes.payment_name','orders.created_at','orders.updated_at', 'customers.phone_number', 'customers.customer_name','customers.profile_picture','addresses.address')
            ->where('order_statuses.slug','ready_to_dispatch')
            ->where('orders.delivered_by',0)
            ->get();
            
        foreach($orders as $key => $value){
                $order_id = $value->id;
                //print_r($order_id);exit;
                $this->find_partner($order_id);
        }
        
    }
    
    public function vendor_rating(Request $request){
        
        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required',
            'rating' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        Order::where('id',$input['id'])->update([ 'rating' => $input['rating'], 'comments' => $input['comments']]);
        $vendor_id = Order::where('id',$input['id'])->value('vendor_id');

        $this->calculate_overall_rating($vendor_id);
        
        return response()->json([
            "message" => 'Success',
            "status" => 1
        ]);
             

    }
    
    public function calculate_overall_rating($vendor_id)
    {
        $ratings_data = Order::where('vendor_id',$vendor_id)->where('rating','!=', '0')->count();
        $data_sum = Order::where('vendor_id',$vendor_id)->get()->sum("rating");
        $data = $data_sum / $ratings_data;
        if($data){
            Vendor::where('id',$vendor_id)->update(['overall_ratings'=>number_format((float)$data, 1, '.', ''), 'no_of_ratings'=> $ratings_data ]);
        }
        
    }
    
    public function upload_prescription(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
            'image' => 'required',
            'customer_id' => 'required',
            'vendor_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $name = time().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/uploads/prescriptions');
            $image->move($destinationPath, $name);
            
            $data['customer_id'] = $input['customer_id'];
            $data['vendor_id'] = $input['vendor_id'];
            $data['prescription'] = 'prescriptions/'.$name;
            $data['address_id'] = $input['address_id'];
            $data['total'] = 0;
            $data['discount'] = 0;
            $data['sub_total'] = 0;
            $data['promo_id'] = 0;
            $data['payment_mode'] = 1;
            $data['status'] = 1;
            $data['vendor_percent'] = VendorAppSetting::where('id',1)->value('order_commission');
            

            $order = Order::create($data);
            if (is_object($order)) {
                $this->check_vendor_booking($order->vendor_id);
                $this->update_status($order->id,$data['status']);
                
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
            /*return response()->json([
                "result" => 'prescriptions/'.$name,
                "message" => 'Success',
                "status" => 1
            ]);*/
            
        }
    }
    
    public function upload_doctor_prescription(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
            'prescription_id' => 'required',
            'customer_id' => 'required',
            'vendor_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        if ($input['prescription_id']) {
            $data['customer_id'] = $input['customer_id'];
            $data['vendor_id'] = $input['vendor_id'];
            $data['prescription'] = '';
            $data['prescription_id'] = $input['prescription_id'];
            $data['address_id'] = $input['address_id'];
            $data['total'] = 0;
            $data['discount'] = 0;
            $data['sub_total'] = 0;
            $data['promo_id'] = 0;
            $data['payment_mode'] = 1;
            $data['status'] = 1;
            $data['vendor_percent'] = VendorAppSetting::where('id',1)->value('order_commission');
            
            $order = Order::create($data);
            if (is_object($order)) {
                $this->check_vendor_booking($order->vendor_id);
                $this->update_status($order->id,$data['status']);
                
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
    }
    
    public function sendError($message) {
        $message = $message->all();
        $response['error'] = "validation_error";
        $response['message'] = implode('',$message);
        $response['status'] = "0";
        return response()->json($response, 200);
    }
}
