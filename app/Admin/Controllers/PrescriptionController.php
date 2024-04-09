<?php

namespace App\Admin\Controllers;
use Request;
use App\Models\Order;
use App\Models\CustomerAppSetting;
use App\Models\Tax;
use App\Models\UnitMeasurement;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\Product;
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

class PrescriptionController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Add Products';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Order);
        if(!Admin::user()->isAdministrator()){
            $grid->model()->where('vendor_id', Vendor::where('admin_user_id',Admin::user()->id)->value('id'))->where('total',0);
        }else{
            $grid->model()->where('total',0);
        }
        $grid->column('id', __('Order Id'));
        $grid->column('vendor_id', __('Vendor'))->display(function($vendor){
            $vendor = Vendor::where('id',$vendor)->value('store_name');
                return $vendor;
        });
        $grid->column('customer_id', __('Customer'))->display(function($customer_id){
            return Customer::where('id',$customer_id)->value('customer_name');
        });
        $grid->column('View Prescription')->display(function () {
            if($this->prescription != NULL && $this->total == 0){
                return "<a href='/admin/view_prescription/".$this->id."'><span class='label label-info'>View Prescription</span></a>";
            }else if($this->prescription_id != NULL && $this->total == 0){
                return "<a href='/admin/view_prescription/".$this->id."'><span class='label label-info'>View Prescription</span></a>";
            }else{
                return '------';
            }
            
        });
        //$grid->column('prescription',__('Prescription Image'))->image();
        $grid->disableExport();
        $grid->disableFilter();
        $grid->disableCreateButton();
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableDelete();
        });
        
        return $grid;
    }
    
    protected function form()
    {
        $form = new Form(new Order);
        
        
        if(!Admin::user()->isAdministrator()){
            $orders = Order::where('vendor_id',Vendor::where('admin_user_id',Admin::user()->id)->value('id'))->pluck('id', 'id');
        }else{
            $orders = Order::pluck('id', 'id');
        }
        
        $form->hasMany('items', function ($form) {
          
            $vendor_id = Order::where('id',Request::segment(3))->value('vendor_id');
            $products = Product::where('vendor_id',$vendor_id)->pluck('product_name', 'id');
            $units = UnitMeasurement::pluck('unit', 'unit');

            $form->select('product_id', __('Product Name'))->options($products)->rules(function ($form) {
                return 'required';
            });
            $form->number('qty', __('QTY'))->rules(function ($form) {
                return 'required|min:1';
            });
            $form->number('price', __('Price'))->rules(function ($form) {
                return 'required|min:1';
            });
            $form->select('unit', __('Unit'))->options($units)->rules(function ($form) {
                return 'required';
            });
        });
        $form->saved(function (Form $form){
            $this->pricing_calculations($form->model()->id);
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
    
    public function pricing_calculations($id){
       $settings = CustomerAppSetting::where('id',1)->first();
       $items = OrderItem::where('order_id',$id)->get();
       $sub_total = 0;
       $total = 0;
       $delivery_charge = $settings->pharm_delivery_charge;
       foreach($items as $key => $value){
           $sub_total = $sub_total + $value->price;
           $items[$key]->product_name = Product::where('id',$value->product_id)->value('product_name');
           $items[$key]->image = Product::where('id',$value->product_id)->value('image');
       }
       
       $net_total = $sub_total + $delivery_charge;
       
       //Tax calculation
       $taxes_count = Tax::where('status',1)->where('service_id',3)->count();
       $tax = 0;
       if($taxes_count > 0){
           $taxes = Tax::where('status',1)->where('service_id',3)->get();
           foreach($taxes as $key => $value){
               $tax = $tax + ($net_total / 100) * $value->percentage;
           }
       }
    
       $total = $net_total + $tax;
       Order::where('id',$id)->update([ 'delivery_charge' => $delivery_charge, 'total' => $total, 'sub_total' => $sub_total, 'tax' => $tax, 'status' => 1, 'items' => json_encode($items) ]);
       
    }
} 
