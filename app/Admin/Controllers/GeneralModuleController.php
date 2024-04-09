<?php

namespace App\Admin\Controllers;

use App\Models\Customer;
use App\Models\LabOrder;
use App\Models\Address;
use App\Models\LabCollectivePeople;
use App\Models\CustomerAppSetting;
use App\Models\PaymentMode;
use App\Models\LabOrderStatus;
use App\Models\Laboratory;
use App\Models\LabOrderItem;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\DB;
class GeneralModuleController extends Controller
{
    public function index($id)
    {
        return Admin::content(function (Content $content) use ($id) {
            $content->header('Order Details');
            $content->description('View');
            $order_details = LabOrder::where('id',$id)->first();
            $app_setting = CustomerAppSetting::first();
            $data = array();
            $data['order_id'] = $order_details->id;
            $data['customer_name'] = Customer::where('id',$order_details->customer_id)->value('customer_name');
            $data['lab_name'] = Laboratory::where('id',$order_details->lab_id)->value('lab_name');
            $data['lab_phone_number'] = Laboratory::where('id',$order_details->lab_id)->value('phone_number');
            $data['lab_address'] = Laboratory::where('id',$order_details->lab_id)->value('address');
            $data['customer_phone_number'] = Customer::where('id',$order_details->customer_id)->value('phone_number');
            $data['address'] = Address::where('id',$order_details->address_id)->value('address');
            $data['collective_person'] = (LabCollectivePeople::where('id',$order_details->collective_person)->value('name') != '' ) ? DeliveryBoy::where('id',$order_details->collective_person)->value('name') : "---" ;
            $data['payment_mode'] = PaymentMode::where('id',$order_details->payment_mode)->value('payment_name');
            $data['sub_total'] = $app_setting->default_currency.$order_details->sub_total;
            $data['discount'] =  $app_setting->default_currency.$order_details->discount;
            $data['tax'] =  $app_setting->default_currency.$order_details->tax;
            $data['total'] =  $app_setting->default_currency.$order_details->total;
            $data['status'] =  LabOrderStatus::where('id',$order_details->status)->value('status');
            $order_items = DB::table('lab_order_items')
            ->leftJoin('lab_packages', 'lab_packages.id', '=', 'lab_order_items.item_id')
            ->select('lab_packages.package_name','lab_order_items.price')
            ->where('lab_order_items.order_id',$order_details->id)
            ->orderBy('lab_order_items.created_at', 'asc')
            ->get();
            $data['order_items'] = $order_items;
            $content->body(view('admin.view_lab_orders', $data));
        });
    }
    
    public function patient_histories($id)
    {
        return Admin::content(function (Content $content) use ($id) {
            $content->header('Patient Histories');
            $content->description('View');
            $data['history'] = DB::table('booking_requests')
                             ->join('doctors','doctors.id','=','booking_requests.doctor_id')
                             ->join('customers','customers.id','=','booking_requests.patient_id')
                             ->join('hospitals','hospitals.id','=','doctors.hospital_id')
                             ->where('booking_requests.patient_id',$id)
                             ->select('booking_requests.*','doctors.doctor_name','customers.customer_name','hospitals.hospital_name')
                             ->get();
            $data['patient_details'] = DB::table('customers')->where('id',$id)->first();
            $content->body(view('admin.patient_histories', $data));
        });

    }
}
