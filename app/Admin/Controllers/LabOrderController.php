<?php

namespace App\Admin\Controllers;

use App\Models\LabOrder;
use App\Models\Customer;
use App\Models\Laboratory;
use App\Models\LabPackage;
use App\Models\PaymentMode;
use App\Models\LabPromoCode;
use App\Models\Address;
use App\Models\LabOrderStatus;
use App\Models\LabCollectivePeople;
use App\Models\OrderCommission;
use App\Models\LabEarning;
use App\Models\LabWalletHistory;
use App\Models\LaboratoryAppSetting;
use App\Models\CommissionSetting;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Database;
use Illuminate\Support\MessageBag;
use Admin;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use FCM;
use App\Models\FcmNotification;
use Request;

class LabOrderController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Lab Orders';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new LabOrder());
        
        $grid->model()->orderBy('id','desc');
        $grid->column('id', __('Id'));
        if(!Admin::user()->isAdministrator()){
            $grid->model()->where('lab_id', Laboratory::where('admin_user_id',Admin::user()->id)->value('id'));
        }
        $grid->column('lab_id', __('Lab'))->display(function($laboratories){
            $laboratories = Laboratory::where('id',$laboratories)->value('lab_name');
            return $laboratories;
        }); 
        $grid->column('customer_id', __('Customer'))->display(function($customers){
            $customers = Customer::where('id',$customers)->value('customer_name');
            return $customers;
        });
        $grid->column('patient_name', __('Patient Name'));
        $grid->column('patient_dob', __('Patient Date of Birth'));
        $grid->column('patient_gender', __('Patient Gender'))->display(function($gender){
            if ($gender == 1) {
                return "<span class='label label-success'>Male</span>";
            }if($gender == 2) {
                return "<span class='label label-info'>Female</span>";
            }else {
                return "<span class='label label-warning'>Female</span>";
            }
        });
        //$grid->column('total', __('Total'));
        //$grid->column('sub_total', __('Sub Total'));
        //$grid->column('discount', __('Discount'));
        //$grid->column('tax', __('Tax'));
        /*$grid->column('promo_id', __('Promo'))->display(function($promo_codes){
            if($promo_codes == 0){
                return "NILL";
            }else{
                $promo_codes = LabPromoCode::where('id',$promo_codes)->value('promo_name');
                return $promo_codes;
            }
        });*/
        $grid->column('special_instruction', __('Special Instruction'));
        //$grid->column('collective_person', __('Collective Person'));
        /*$grid->column('payment_mode', __('Payment Mode'))->display(function($payment_modes){
            $payment_modes = PaymentMode::where('id',$payment_modes)->value('payment_name');
            return $payment_modes;
        });*/
        $grid->column('booking_type', __('Booking Type'))->display(function($booking_type){
           if($booking_type == 1){
              return "Collect From Home";
           }else{
              return "Direct Appointment";
           }
        });
        $grid->column('status', __('Status'))->display(function($laboratory_order_statuses){
            $laboratory_order_statuses = LabOrderStatus::where('id',$laboratory_order_statuses)->value('status');
            return $laboratory_order_statuses;
        });
        
        $grid->column('View Orders')->display(function () {
            return "<a href='/admin/view_lab_orders/".$this->id."'><span class='label label-info'>View Orders</span></a>";
        });

        $grid->disableExport();
        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        $grid->filter(function ($filter) {

        $customers = Customer::pluck('customer_name','id');
        $laboratories = Laboratory::pluck('lab_name','id');
        $lab_packages = LabPackage::pluck('package_name','id');
        $payment_modes = PaymentMode::pluck('payment_type_id','id');
        $statuses = LabOrderStatus::pluck('status','id');
             //Get All status
        if(!Admin::user()->isAdministrator()){
            $filter->equal('status', __('Status'))->select($statuses);
            $filter->equal('payment_mode', __('Payment Mode'))->select($payment_modes);
        }else{
            $filter->equal('customer_id', __('Customer'))->select($customers);
            $filter->equal('laboratory_id', __('Laboratory'))->select($laboratories);
            $filter->equal('payment_method', __('Payment Method'))->select($payment_modes);
            $filter->equal('lab_package', __('Lab Package'))->select($lab_packages);
            $filter->equal('status', __('Status '))->select($statuses);
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
        $show = new Show(LabOrder::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('customer_id', __('Customer id'));
        $show->field('address_id', __('Address id'));
        $show->field('lab_id', __('Lab id'));
        $show->field('package_id', __('Package id'));
        $show->field('price', __('Price'));
        $show->field('date', __('Date'));
        $show->field('special_instruction', __('Special instruction'));
        $show->field('collective_person', __('Collective person'));
        $show->field('payment_mode', __('Payment mode'));
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
        $form = new Form(new LabOrder());
        $customers = Customer::pluck('customer_name','id');
        $collective_persons = LabCollectivePeople::where('lab_id',LabOrder::where('id',Request::segment(3))->value('lab_id'))->pluck('name','id');
        $laboratories = Laboratory::pluck('lab_name','id');
        $lab_order_statuses = LabOrderStatus::pluck('status','id');
   
        /*if(!Admin::user()->isAdministrator()){
            $form->hidden('lab_id')->value($laboratories);
        }else{
            $form->select('lab_id', __('Lab'))->options($laboratories);
        }*/
        $form->select('collective_person', __('Collective Person'))->options($collective_persons);
        $form->select('status', __('Status'))->options($lab_order_statuses)->default(1)->rules(function ($form) {
            return 'required';
        });
        
        $form->saving(function (Form $form) {
           if($form->collective_person > 0 && $form->status ==1){
                $error = new MessageBag([
                    'title'   => 'Warning',
                    'message' => 'Please change order status...',
                ]);

                return back()->with(compact('error'));
           }
        });
        
        $form->saved(function (Form $form) {
            
            $lab_id = DB::table('laboratories')->where('id',$form->lab_id)->value('id');
            $slug = DB::table('lab_order_statuses')->where('id',$form->status)->value('slug');
            $payment_type = DB::table('payment_modes')->where('id',$form->model()->payment_mode)->value('slug');
            $status = DB::table('lab_order_statuses')->where('slug',$input['slug'])->value('id');
          
        if($slug == "completed"){
            $this->commission_calculations($form->model()->id);
        }
         
        $this->update_status($form->model()->id,$form->status);
        $this->find_fcm_message('order_status_'.$status,$order->customer_id,0,0);
           
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
                $database->getReference('lab_orders/'.$id)
                ->update([
                    'status' => $status
                ]);
                
    }
    
    public function commission_calculations($order_id){
        $order = LabOrder::where('id',$order_id)->first();
        $cus_address = DB::table('addresses')->where('id',$order->address_id)->first();
        $res = DB::table('laboratories')->where('id',$order->lab_id)->first();
        //$admin_percent = LaboratoryAppSetting::where('id',1)->value('lab_commission');
        $commission_type = LaboratoryAppSetting::value('commission_type');
        if($commission_type == 1){
            $admin_percent = LaboratoryAppSetting::where('id',1)->value('lab_commission');
        }else{
            $admin_percent = Laboratory::where('id',$order->lab_id)->value('lab_commission');
        }
       
        $admin_commission = ($order->total / 100) * $admin_percent; 
        $admin_commission = number_format((float)$admin_commission, 2, '.', '');
        
        $lab_commission = $order->total - $admin_commission;
        $lab_commission = number_format((float)$lab_commission, 2, '.', '');
        
        $order_commission['order_id'] = $order_id;
        $order_commission['role'] = 'laboratory';
        $order_commission['user_id'] = $order->lab_id;
        $order_commission['amount'] = $lab_commission;
        OrderCommission::create($order_commission);
        
        $order_commission['order_id'] = $order_id;
        $order_commission['role'] = 'admin';
        $order_commission['user_id'] = 1;
        $order_commission['amount'] = $admin_commission;
        OrderCommission::create($order_commission);
        
        LabEarning::create([ 'order_id' => $order_id, 'lab_id' => $order->lab_id, 'amount' => $lab_commission]);
        LabWalletHistory::create([ 'lab_id' => $order->lab_id, 'type' => 1, 'message' => 'Your earnings credited for this order #'.$order->id, 'amount' => $lab_commission]);
        
        $wallet = Laboratory::where('id',$order->lab_id)->value('wallet');
        $new_wallet = $wallet + $lab_commission;
        $new_wallet = number_format((float)$new_wallet, 2, '.', '');
        
        Laboratory::where('id',$order->lab_id)->update([ 'wallet' => $new_wallet]);
        
    }
    
    public function find_fcm_message($slug,$customer_id,$vendor_id,$partner_id){
        $message = FcmNotification::where('slug',$slug)->first();
        //print_r($message);exit;
        if($customer_id){
            $fcm_token = Customer::where('id',$customer_id)->value('fcm_token');
            if($fcm_token){
                $this->send_fcm($message->customer_title, $message->customer_description, $fcm_token);
            }
        }
        
        /*if($vendor_id){
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
        }*/
    }
}
