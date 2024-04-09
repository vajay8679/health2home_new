<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vendor;
use App\Models\VendorRating;
use App\Models\Order;
use App\Models\Address;
use App\Models\Customer;
use App\Models\AppSetting;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Product;
use App\Models\Status;
use App\Models\ProductType;
use App\Models\VendorEarning;
use App\Models\VendorDocument;
use App\Models\VendorWalletHistory;
use App\Models\VendorWithdrawal;
use App\Models\CustomerWalletHistory;
use App\Models\Label;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Twilio\Rest\Client;
use Kreait\Firebase;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Database;

class VendorController extends Controller
{
    public function register(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'owner_name' => 'required',
            'store_name' => 'required',
            'email' => 'required|email|regex:/^[a-zA-Z]{1}/',
            'phone_number' => 'required|numeric|digits_between:9,20',
            'phone_with_code' => 'required',
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
        $input['profile_picture'] = 'static_images/avatar.png';
        $id = DB::table('admin_users')->insertGetId(
                ['username' => $input['phone_with_code'], 'password' => $input['password'], 'name' => $input['store_name'], 'avatar' => 'static_images/avatar.png']
            );

            DB::table('admin_role_users')->insert(
                ['role_id' => 3, 'user_id' => $id ]
            );
        
        $input['admin_user_id'] = $id;
            
        $vendor = Vendor::create($input);
        
        if (is_object($vendor)) {
            $this->update_status($vendor->id,$vendor->status);
            return response()->json([
                "result" => $vendor,
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
        $vendor = Vendor::where('phone_with_code',$input['phone_with_code'])->first();

        if(is_object($vendor)){
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
    
    public function login(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
            'phone_with_code' => 'required',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $credentials = request(['phone_with_code', 'password']);
        $vendor = Vendor::where('phone_with_code',$credentials['phone_with_code'])->first();

        if (!($vendor)) {
            return response()->json([
                "message" => 'Invalid Phone Number or password',
                "status" => 0
            ]);
        }
        
        if (Hash::check($credentials['password'], $vendor->password)) {
            if($vendor->status == 1){
                Vendor::where('id',$vendor->id)->update([ 'fcm_token' => $input['fcm_token']]);
                return response()->json([
                    "result" => $vendor,
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
                "message" => 'Invalid Phone Number or password',
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
            $admin_user_id = Vendor::where('id',$input['id'])->value('admin_user_id');
            DB::table('admin_users')->where('id',$admin_user_id)->update([ 'password' => $input['password']]);
        }else{
            unset($input['password']);
        }

        if (Vendor::where('id',$input['id'])->update($input)) {
            return response()->json([
                "result" => Vendor::select('id','email','phone_number','phone_with_code','store_name','profile_picture','status')->where('id',$input['id'])->first(),
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
            $destinationPath = public_path('/uploads/vendors');
            $image->move($destinationPath, $name);
            return response()->json([
                "result" => 'vendors/'.$name,
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
        
        if (Vendor::where('id',$input['id'])->update($input)) {
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
    

    public function forget_password(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'phone_with_code' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        $vendor = Vendor::where('phone_with_code',$input['phone_with_code'])->first();
        

        if(is_object($vendor)){
            $data['id'] = $vendor->id;
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

        if(Vendor::where('id',$input['id'])->update($input)){
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
    
    public function vendor_address(Request $request)
    {
        $input = $request->all();
        $id = $input['id'];
        $validator = Validator::make($input, [
            'address' =>'required',
            'longitude' => 'required',
            'latitude' => 'required',
            'manual_address' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
         $url = 'https://maps.googleapis.com/maps/api/staticmap?center='.$input['latitude'].','.$input['longitude'].'&zoom=16&size=600x300&maptype=roadmap&markers=color:red%7Clabel:L%7C'.$input['latitude'].','.$input['longitude'].'&key='.env('MAP_KEY');
            $img = 'static_map/'.md5(time()).'.png';
            file_put_contents('uploads/'.$img, file_get_contents($url));

        $input['static_map'] = $img;
        $input['address_update_status'] = 1;
        if (Vendor::where('id',$id)->update($input)) {
            return response()->json([
                "result" => Vendor::select('id', 'store_name','address','longitude','latitude', 'manual_address','address_update_status')->where('id',$id)->first(),
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
    

    public function vendor_earning(Request $request){
        
        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data['total_earnings'] = VendorEarning::where('vendor_id',$input['id'])->get()->sum("amount");
        $data['today_earnings'] = VendorEarning::where('vendor_id',$input['id'])->whereDay('created_at', now()->day)->sum("amount");
        $data['earnings'] = VendorEarning::where('vendor_id',$input['id'])->get();
        
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
    public function vendor_wallet(Request $request){
        
        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data['wallet_amount'] = Vendor::where('id',$input['id'])->value('wallet');
        
        $data['wallets'] = VendorWalletHistory::where('vendor_id',$input['id'])->get();
        
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
    
    public function upload(Request $request){

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
            $destinationPath = public_path('/uploads/vendor_images');
            $image->move($destinationPath, $name);
            return response()->json([
                "result" => 'vendor_images/'.$name,
                "message" => 'Success',
                "status" => 1
            ]);
            
        }
    }
    
    public function document_upload(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'vendor_id' => 'required',
            'document_name' => 'required',
            'document_path' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $input['status'] = 3;
        $exist_id = VendorDocument::where('vendor_id',$input['vendor_id'])->where('document_name',$input['document_name'])->value('id');
        if($exist_id){
             VendorDocument::where('vendor_id',$input['vendor_id'])->where('document_name',$input['document_name'])->update([ 'document_path' => $input['document_path'], 'status' => $input['status'] ]);
        }else{
             VendorDocument::create($input);
        }
       
        return response()->json([
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
    /*public function document_update(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'vendor_id' => 'required',
            'id_proof' => 'required',
            'certificate' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $old_document = VendorDocument::where('vendor_id',$input['vendor_id'])->first();
        if($old_document->id_proof != $input['id_proof']){
            $input['id_proof_status'] = 3;
        }
        
        if($old_document->certificate != $input['certificate']){
            $input['certificate_status'] = 3;
        }
        
        VendorDocument::where('vendor_id',$input['vendor_id'])->update($input);
        $vendor = Vendor::where('id',$input['vendor_id'])->update(['document_approved_status' => 3  ]);
        return response()->json([
            "message" => 'Success',
            "status" => 1
        ]);
    }*/
    
    public function document_details(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'vendor_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $documents = DB::table('vendor_documents')
                    ->leftJoin('statuses','statuses.id','vendor_documents.status')
                    ->select('vendor_documents.*','statuses.status_name')
                    ->where('vendor_id',$input['vendor_id'])->get();
        
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
    
    public function update_status($id,$status){
        
        $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
        $database = $factory->createDatabase();
        $newPost = $database
        ->getReference('vendors/'.$id)
        ->update([
            'status' => $status,
            'o_stat' => 0,
            'on_stat'=> 0,
        ]);
    }
    
    public function vendor_withdrawal_request(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'vendor_id' => 'required',
            'amount' => 'required'
            
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $input['status'] = 6;
         $input['message'] = "Your withdrawal request successfully submitted";
        $del_wallet = Vendor::where('id',$input['vendor_id'])->value('wallet');
        $new_wallet = $del_wallet-$input['amount'];
        $input['existing_wallet'] = $del_wallet;
        if($input['amount'] <= $del_wallet ){
          $vendor = VendorWithdrawal::create($input);  
          
        $status = VendorWithdrawal::where('vendor_id',$input['vendor_id'])->where('id',$vendor->id)->value('status');
            if($status==6){
                 Vendor::where('id',$input['vendor_id'])->update([ 'wallet' => $new_wallet]);
            }
        if (is_object($vendor)) {
            return response()->json([
                "result" => $vendor,
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
    
    public function vendor_withdrawal_history(Request $request){
        
        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data['wallet_amount'] = Vendor::where('id',$input['id'])->value('wallet');
        
        $data['withdraw'] =  DB::table('vendor_withdrawals')
                ->leftjoin('statuses', 'statuses.id', '=', 'vendor_withdrawals.status')
                ->select('vendor_withdrawals.*', 'statuses.status_name')
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
    
    public function vendor_dashboard(Request $request){
        
        $input = $request->all();
        $validator = Validator::make($input, [
            'id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $result['vendor'] = Vendor::select('id','address_update_status','document_approved_status','status')->where('id',$input['id'])->first();
        $result['earnings'] = VendorEarning::where('vendor_id',$input['id'])->count();
        $result['wallet'] = VendorWalletHistory::where('vendor_id',$input['id'])->count();
        $result['active_orders'] = DB::table('orders')
            ->leftJoin('order_statuses', 'order_statuses.id', '=', 'orders.status')
            ->select('orders.*','order_statuses.status','order_statuses.slug')
            ->where('orders.vendor_id',$input['id'])
            ->whereIn('order_statuses.slug',['ready_to_dispatch','reached_vendor','order_picked','at_point'])
            ->get()->count();
        $result['completed_orders'] = DB::table('orders')
            ->leftJoin('order_statuses', 'order_statuses.id', '=', 'orders.status')
            ->select('orders.*','order_statuses.status','order_statuses.slug')
            ->where('orders.vendor_id',$input['id'])
            ->where('order_statuses.slug','delivered')
            ->get()->count();
        $result['new_orders'] = DB::table('orders')
            ->leftJoin('customers', 'customers.id', '=', 'orders.customer_id')
            ->leftJoin('order_statuses', 'order_statuses.id', '=', 'orders.status')
            ->select('orders.*','order_statuses.status','order_statuses.slug','customers.customer_name')
            ->where('orders.vendor_id',$input['id'])
            ->where('order_statuses.slug','order_placed')
            ->get();

        if($result){
            return response()->json([
                "result" => $result,
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
    
    public function change_online_status(Request $request){
        $input = $request->all();
        Vendor::where('id',$input['id'])->update([ 'online_status' => $input['online_status']]);
         $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
        $database = $factory->createDatabase();
        $newPost = $database
        ->getReference('vendors/'.$input['id'])
        ->update([
            'on_stat' => (int) $input['online_status']
        ]);
        return response()->json([
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
