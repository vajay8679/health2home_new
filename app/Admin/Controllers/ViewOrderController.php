<?php

namespace App\Admin\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Address;
use App\Models\DeliveryBoy;
use App\Models\CustomerAppSetting;
use App\Models\PaymentMode;
use App\Models\Label;
use App\Models\Vendor;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\DB;
class ViewOrderController extends Controller
{
    public function index($id)
    {
        return Admin::content(function (Content $content) use ($id) {
            $content->header('Order Details');
            $content->description('View');
            $order_details = Order::where('id',$id)->first();
            $app_setting = CustomerAppSetting::first();
            $data = array();
            $data['order_id'] = $order_details->id;
            $data['customer_name'] = Customer::where('id',$order_details->customer_id)->value('customer_name');
            $data['vendor_name'] = Vendor::where('id',$order_details->vendor_id)->value('store_name');
            $data['vendor_phone_number'] = Vendor::where('id',$order_details->vendor_id)->value('phone_number');
            $data['vendor_address'] = Vendor::where('id',$order_details->vendor_id)->value('address');
            $data['phone_number'] = Customer::where('id',$order_details->customer_id)->value('phone_number');
            $data['address'] = Address::where('id',$order_details->address_id)->value('address');
            $data['delivered_by'] = (DeliveryBoy::where('id',$order_details->delivered_by)->value('delivery_boy_name') != '' ) ? DeliveryBoy::where('id',$order_details->delivered_by)->value('delivery_boy_name') : "---" ;
            $data['payment_mode'] = PaymentMode::where('id',$order_details->payment_mode)->value('payment_name');
            $data['sub_total'] = $app_setting->default_currency.$order_details->sub_total;
            $data['delivery_charge'] = $app_setting->default_currency.$order_details->delivery_charge;
            $data['discount'] =  $app_setting->default_currency.$order_details->discount;
            $data['total'] =  $app_setting->default_currency.$order_details->total;
            $data['status'] =  OrderStatus::where('id',$order_details->status)->value('status');
            $order_items = DB::table('order_items')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->select('products.product_name','order_items.qty')
            ->where('order_items.order_id',$order_details->id)
            ->orderBy('order_items.created_at', 'asc')
            ->get();
            $data['order_items'] = $order_items;
            $content->body(view('admin.view_orders', $data));
        });
    }
    
    public function view_prescription($id)
    {
        return Admin::content(function (Content $content) use ($id) {
            $content->header('Prescription Details');
            $content->description('View');
            $order_details = Order::where('id',$id)->first();
            $app_setting = CustomerAppSetting::first();
            $data = array();
            $data['order_id'] = $order_details->id;
            $data['vendor_name'] = Vendor::where('id',$order_details->vendor_id)->value('store_name');
            $data['vendor_address'] = Vendor::where('id',$order_details->vendor_id)->value('address');
            $data['prescription'] = $order_details->prescription;
            if($order_details->prescription_id){
                $data['prescription_items'] = DB::table('customer_prescription_items')->where('customer_prescription_id',$order_details->prescription_id)->get();
            }
            /*$data['order_id'] = $order_details->id;
            $data['customer_name'] = Customer::where('id',$order_details->customer_id)->value('customer_name');
            $data['vendor_name'] = Vendor::where('id',$order_details->vendor_id)->value('store_name');
            $data['vendor_phone_number'] = Vendor::where('id',$order_details->vendor_id)->value('phone_number');
            $data['vendor_address'] = Vendor::where('id',$order_details->vendor_id)->value('address');
            $data['phone_number'] = Customer::where('id',$order_details->customer_id)->value('phone_number');
            $data['address'] = Address::where('id',$order_details->address_id)->value('address');
            $data['delivered_by'] = (DeliveryBoy::where('id',$order_details->delivered_by)->value('delivery_boy_name') != '' ) ? DeliveryBoy::where('id',$order_details->delivered_by)->value('delivery_boy_name') : "---" ;
            $data['payment_mode'] = PaymentMode::where('id',$order_details->payment_mode)->value('payment_name');
            $data['sub_total'] = $app_setting->default_currency.$order_details->sub_total;
            $data['delivery_charge'] = $app_setting->default_currency.$order_details->delivery_charge;
            $data['discount'] =  $app_setting->default_currency.$order_details->discount;
            $data['total'] =  $app_setting->default_currency.$order_details->total;
            $data['status'] =  OrderStatus::where('id',$order_details->status)->value('status');
            $order_items = DB::table('order_items')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->select('products.product_name','order_items.qty')
            ->where('order_items.order_id',$order_details->id)
            ->orderBy('order_items.created_at', 'asc')
            ->get();
            $data['order_items'] = $order_items;*/
            $content->body(view('admin.view_prescription', $data));
        });
    }
}
