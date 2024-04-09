<?php

namespace App\Admin\Controllers;

use App\Models\Order;
use App\Models\Customer;
use App\Models\DeliveryBoy;
use App\Models\Address;
use App\Models\FcmNotification;
use App\Models\PaymentMode;
use App\Models\Promo;
use App\Models\PartnerRejection;
use App\Models\OrderStatus;
use App\Models\Vendor;
use App\Models\OrderHistory;
use App\Models\FcmNotificationMessage;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;
use Kreait\Firebase;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Database;
use Illuminate\Support\MessageBag;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use FCM;
use Illuminate\Support\Facades\DB;

class OrderController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Orders';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Order);
        
        if(!Admin::user()->isAdministrator()){
            $grid->model()->where('vendor_id', Vendor::where('admin_user_id',Admin::user()->id)->value('id'));
        }
        /*if(!Admin::user()->isAdministrator()){
            $grid->model()
                ->join('vendors', 'vendors.id', '=', 'orders.vendor_id')
                ->join('hospitals', 'hospitals.id', '=', 'vendors.hospital_id')
                ->select('orders.*')
                ->where('hospitals.admin_user_id',Admin::user()->id)
                ->orderBy('orders.id','desc');
        }*/
        $grid->model()->orderBy('id','desc');
        $grid->column('id', __('Id'));
        $grid->column('vendor_id', __('Vendor'))->display(function($vendor){
            $vendor = Vendor::where('id',$vendor)->value('store_name');
                return $vendor;
        });
        $grid->column('customer_id', __('Customer'))->display(function($customer_id){
            return Customer::where('id',$customer_id)->value('customer_name');
        });
        $grid->column('delivered_by', __('Delivered By'))->display(function($delivered_by){
            if($delivered_by){
                return DeliveryBoy::where('id',$delivered_by)->value('delivery_boy_name');
            }else{
                return '---';
            }
            
        });
        $grid->column('rating', __('Rating'))->display(function($rating){
            if($rating){
                return $rating;
            }else{
                return '---';
            }
            
        });
        $grid->column('comments', __('Comments'))->display(function($comments){
            if($comments){
                return $comments;
            }else{
                return '---';
            }
            
        });
        $grid->column('status', __('Status'))->display(function($status){
            $label_name = OrderStatus::where('id',$status)->value('status');
                return "$label_name";
            
        });
        $grid->column('Add Products')->display(function () {
            if($this->prescription != NULL && $this->total == 0){
                return "<a href='/admin/prescription'><span class='label label-info'>Add Products</span></a>";
            }else if($this->prescription_id != NULL && $this->total == 0){
                return "<a href='/admin/prescription'><span class='label label-info'>Add Products</span></a>";
            }else{
                return '------';
            }
            
        });
        $grid->column('View Orders')->display(function () {
            return "<a href='/admin/view_orders/".$this->id."'><span class='label label-info'>View Orders</span></a>";
        });
        $grid->disableExport();
        $grid->disableCreateButton();
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableDelete();
        });
        $grid->filter(function ($filter) {
            //Get All status
            $labels = OrderStatus::where('id','<',9)->pluck('status', 'id');
            $customers = Customer::pluck('customer_name', 'id');
            $vendors = Vendor::pluck('store_name', 'id');
            $delivery_boys = DeliveryBoy::pluck('delivery_boy_name', 'id');
            
            if(Admin::user()->isAdministrator()){
                $filter->equal('customer_id', 'Customer')->select($customers);
                $filter->equal('vendor_id', 'Vendor')->select($vendors);
                $filter->equal('delivered_by', 'Delivered By')->select($delivery_boys);
                $filter->equal('status', 'Status')->select($labels);
            }else{
                $filter->equal('delivered_by', 'Delivered By')->select($delivery_boys);
                $filter->equal('status', 'Status')->select($labels);
            }
            
        });
        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Order::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('order_id', __('Order id'));
        $show->field('customer_id', __('Customer id'));
        $show->field('address_id', __('Address id'));
        $show->field('expected_delivery_date', __('Expected delivery date'));
        $show->field('total', __('Total'));
        $show->field('discount', __('Discount'));
        $show->field('sub_total', __('Sub total'));
        $show->field('promo_id', __('Promo id'));
        $show->field('delivered_by', __('Delivered by'));
        $show->field('tax', __('Tax'));
        $show->field('payment_mode', __('Payment mode'));
        $show->field('items', __('Items'));
        $show->field('status', __('Status'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Order);
        $statuses = OrderStatus::where('id','<',9)->pluck('status', 'id');
        $delivery_boys = DeliveryBoy::where('status',1)->pluck('delivery_boy_name', 'id');
        $vendors = Vendor::pluck('store_name', 'id');
        
        if(!Admin::user()->isAdministrator()){
            $form->hidden('vendor_id')->value(Vendor::where('admin_user_id',Admin::user()->id)->value('id'));
        }else{
            $form->select('vendor_id', __('Vendor'))->options($vendors)->rules(function ($form) {
            return 'required';
        });
        }
        $form->select('delivered_by', __('Delivered By'))->options($delivery_boys);
        $form->select('status', __('Status'))->options($statuses)->default(1)->rules(function ($form) {
            return 'required';
        });
        $form->saving(function (Form $form) {
           if($form->delivered_by > 0 && $form->status ==1){
                $error = new MessageBag([
                    'title'   => 'Warning',
                    'message' => 'Please change order status...',
                ]);

                return back()->with(compact('error'));
           }
        });
        $form->saved(function (Form $form) {
            
            $ven_id = DB::table('vendors')->where('id',$form->vendor_id)->value('id');
            $slug = DB::table('order_statuses')->where('id',$form->status)->value('slug');
            $payment_type = DB::table('payment_modes')->where('id',$form->model()->payment_mode)->value('slug');
            
            if($slug == "cancelled_by_customer"){
            if($payment_type != "cash" && $order_slug == "order_placed"){
                $old_wallet = Customer::where('id',$form->model()->customer_id)->value('wallet');
                $new_wallet = $old_wallet + $form->model()->total;
                Customer::where('id',$form->model()->customer_id)->update([ 'wallet' => $new_wallet ]);
                
                $data['customer_id'] = $form->model()->customer_id;
                $data['type'] = 1;
                $data['message'] ="Amount refunded to wallet";
                $data['amount'] = $form->model()->total;
                $data['transaction_type'] = 2;
                CustomerWalletHistory::create($data);  
            }else if($slug != "order_placed"){
                $old_wallet = Customer::where('id',$form->model()->customer_id)->value('wallet');
                $new_wallet = $old_wallet - $form->model()->total;
                Customer::where('id',$form->model()->customer_id)->update([ 'wallet' => $new_wallet ]);
                
                $data['customer_id'] = $form->model()->customer_id;
                $data['type'] = 2;
                $data['message'] ="Cancellation charge deducted from your wallet";
                $data['amount'] = $form->model()->total;
                $data['transaction_type'] = 3;
                CustomerWalletHistory::create($data); 
            }
        } 
        if($slug == "cancelled_by_vendor" || $slug == "cancelled_by_deliveryboy"){
            if($payment_type != "cash"){
                $old_wallet = Customer::where('id',$form->model()->customer_id)->value('wallet');
                $new_wallet = $old_wallet + $form->model()->total;
                Customer::where('id',$form->model()->customer_id)->update([ 'wallet' => $new_wallet ]);
                
                $data['customer_id'] = $form->model()->customer_id;
                $data['type'] = 1;
                $data['message'] ="Amount refunded to wallet";
                $data['amount'] = $form->model()->total;
                $data['transaction_type'] = 2;
                CustomerWalletHistory::create($data);  
            }
        } 
        
        if($slug == "ready_to_dispatch"){
            $this->find_partner($form->model()->id,$ven_id);
        }   
        if($slug == "delivered"){
            $this->commission_calculations($form->model()->id);
            $this->update_deliveryboy_status($form->delivered_by);
        }
         
        $this->update_status($form->model()->id,$form->status);
        //$this->find_fcm_message('order_status_'.$old_label->id,$order->customer_id,0,0);
           
        });
        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete(); 
            $tools->disableView();
        });
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });
        return $form;
    }
    
    public function update_status($id,$status){
        
            $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
                $database = $factory->createDatabase();
                $database->getReference('orders/'.$id)
                ->update([
                    'status' => $status
                ]);
                
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
    
    public function commission_calculations($order_id){
        $order = Order::where('id',$order_id)->first();
        $cus_address = DB::table('addresses')->where('id',$order->address_id)->first();
        $res = DB::table('vendors')->where('id',$order->vendor_id)->first();
        $distance =  $this->distance($cus_address->lat,$cus_address->lng,$res->lat,$res->lng,'K');
        $admin_percent = VendorAppSetting::where('id',1)->value('order_commission');
        
        $admin_commission = ($order->amount / 100) * $admin_percent; 
        $admin_commission = number_format((float)$admin_commission, 2, '.', '');
        
        $vendor_commission = $order->amount - $vendor_commission;
        $vendor_commission = number_format((float)$vendor_commission, 2, '.', '');
        
        $order_commission['order_id'] = $order_id;
        $order_commission['role'] = 'vendor';
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
    
    public function find_partner($order_id,$ven_id)
    {
        $ven_lat = DB::table('vendors')->where('id',$ven_id)->value('latitude');
        $ven_lng = DB::table('vendors')->where('id',$ven_id)->value('longitude');
        
        $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
        $database = $factory->createDatabase();
        
        $partners = $database->getReference('/delivery_partners')
                    ->getSnapshot()->getValue();
        //print_r($partners);exit;
        $rejected_partners = PartnerRejection::where('order_id',$order_id)->pluck('partner_id')->toArray();
        $min_partner_id = 0;
        $min_distance = 0;
        $booking_searching_radius = DB::table('delivery_boy_app_settings')->where('id',1)->value('booking_searching_radius');
        
        $i=0;
        foreach($partners as $key => $value){
            if(is_array($value)){
                if($value['o_stat'] == 0 && $value['on_stat'] == 1){
                    if(!in_array($value['p_id'], $rejected_partners)){
                        $distance = $this->distance($ven_lat, $ven_lng, $value['lat'], $value['lng'], 'K') ;
                        //$driver_wallet = Driver::where('id',$value['driver_id'])->value('wallet');
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

    public function send_fcm($title,$description,$token){
        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60*20);
        $optionBuilder->setPriority("high");
        $notificationBuilder = new PayloadNotificationBuilder($title);
        $notificationBuilder->setBody($description)
        				    ->setSound('default')->setBadge(1);
        
        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData(['a_data' => 'my_data']);
        
        $option = $optionBuilder->build();
        $notification = $notificationBuilder->build();
        $data = $dataBuilder->build();
        
        $downstreamResponse = FCM::sendTo($token, $option, $notification, $data);
        
        return $downstreamResponse->numberSuccess();
    }
    
    
    public function find_fcm_message($slug,$customer_id,$vendor_id,$partner_id){
        $message = FcmNotification::where('slug',$slug)->first();
        if($customer_id){
            $fcm_token = Customer::where('id',$customer_id)->value('fcm_token');
            if($fcm_token){
                $this->send_fcm($message->customer_title, $message->customer_description, $fcm_token);
            }
        }
        
        if($vendor_id){
            $fcm_token = Vendor::where('id',$vendor_id)->value('fcm_token');
            if($fcm_token){
                $this->send_fcm($message->vendor_title, $message->vendor_description, $fcm_token);
            }
        }
        
        if($partner_id){
            $fcm_token = DeliveryBoy::where('id',$partner_id)->value('fcm_token');
            if($fcm_token){
                $this->send_fcm($message->partner_title, $message->partner_description, $fcm_token);
            }
        }
    }
} 
