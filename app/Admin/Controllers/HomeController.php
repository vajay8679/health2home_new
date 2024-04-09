<?php

namespace App\Admin\Controllers;

use App\Models\Customer;
use App\Models\Doctor;
use App\Models\Vendor;
use App\Models\Order;
use App\Models\Hospital;
use App\Models\BookingRequest;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        return Admin::content(function (Content $content) {

            $content->header('Dashboard');
            $data = array();
            $current_year = date("Y");
            
            
            if(Admin::user()->isRole('hospital')){
                $hospital_id = Hospital::where('admin_user_id',Admin::user()->id)->value('id');
                //echo($hospital_id);exit;
                $data['total_orders'] = DB::table('orders')
                ->join('vendors', 'vendors.id', '=', 'orders.vendor_id')
                ->join('hospitals', 'hospitals.id', '=', 'vendors.hospital_id')
                ->select('orders.*')
                ->where('hospitals.admin_user_id',Admin::user()->id)->count();
                $data['pending_orders'] = DB::table('orders')
                ->join('vendors', 'vendors.id', '=', 'orders.vendor_id')
                ->join('hospitals', 'hospitals.id', '=', 'vendors.hospital_id')
                ->select('orders.*')
                ->where('hospitals.admin_user_id',Admin::user()->id)
                ->where('orders.status','!=',6)->count();
                $data['completed_orders'] = DB::table('orders')
                ->join('vendors', 'vendors.id', '=', 'orders.vendor_id')
                ->join('hospitals', 'hospitals.id', '=', 'vendors.hospital_id')
                ->select('orders.*')
                ->where('hospitals.admin_user_id',Admin::user()->id)
                ->where('orders.status','=',6)->count();
                $data['new_orders'] = DB::table('orders')
                ->join('vendors', 'vendors.id', '=', 'orders.vendor_id')
                ->join('hospitals', 'hospitals.id', '=', 'vendors.hospital_id')
                ->select('orders.*')
                ->where('hospitals.admin_user_id',Admin::user()->id)
                ->where('orders.status','=',1)->count();
                //$data['total_orders'] = Order::where('vendor_id', $vendor_id)->count();
                //$data['pending_orders'] = Order::where('status','!=',6)->where('vendor_id', $vendor_id)->count();
                //$data['completed_orders'] = Order::where('status','=',6)->where('vendor_id', $vendor_id)->count();
                //$data['new_orders'] = Order::where('status','=',1)->where('vendor_id', $vendor_id)->count();

                $customers = Customer::select('id', 'created_at')
                    ->get()
                    ->groupBy(function ($val) {
                        return Carbon::parse($val->created_at)->format('M');
                    });
                $orders = Order::select('id', 'created_at')
                    ->get()
                    ->groupBy(function ($val) {
                        return Carbon::parse($val->created_at)->format('M');
                    });
                $month = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                $temp = [];
                foreach ($customers as $c) {
                    $temp[Carbon::parse($c[0]->created_at)->format('M')] = count($c);
                }
                $growth = [];
                foreach ($month as $m) {
                    if (isset($temp[$m])) {
                        $growth[] = $temp[$m];
                    } else {
                        $growth[] = 0;
                    }
    
                }
                $temp_orders = [];
                foreach ($orders as $o) {
                    $temp_orders[Carbon::parse($o[0]->created_at)->format('M')] = count($o);
                }
                $growth_orders = [];
                foreach ($month as $m) {
                    if (isset($temp_orders[$m])) {
                        $growth_orders[] = $temp_orders[$m];
                    } else {
                        $growth_orders[] = 0;
                    }
    
                }
                $data['customers_chart'] = implode(",", $growth);
                $data['orders_chart'] = implode(",", $growth_orders);
                $content->body(view('admin.hospital_dashboard', $data));
                
            }else if(Admin::user()->isRole('vendor')){
                $vendor_id = Vendor::where('admin_user_id',Admin::user()->id)->value('id');
                $data['total_orders'] = Order::where('vendor_id', $vendor_id)->count();
                $data['pending_orders'] = Order::where('status','!=',6)->where('vendor_id', $vendor_id)->count();
                $data['completed_orders'] = Order::where('status','=',6)->where('vendor_id', $vendor_id)->count();
                $data['new_orders'] = Order::where('status','=',1)->where('vendor_id', $vendor_id)->count();

                $customers = Customer::select('id', 'created_at')
                    ->get()
                    ->groupBy(function ($val) {
                        return Carbon::parse($val->created_at)->format('M');
                    });
                $orders = Order::select('id', 'created_at')
                    ->get()
                    ->groupBy(function ($val) {
                        return Carbon::parse($val->created_at)->format('M');
                    });
                $month = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                $temp = [];
                foreach ($customers as $c) {
                    $temp[Carbon::parse($c[0]->created_at)->format('M')] = count($c);
                }
                $growth = [];
                foreach ($month as $m) {
                    if (isset($temp[$m])) {
                        $growth[] = $temp[$m];
                    } else {
                        $growth[] = 0;
                    }
    
                }
                $temp_orders = [];
                foreach ($orders as $o) {
                    $temp_orders[Carbon::parse($o[0]->created_at)->format('M')] = count($o);
                }
                $growth_orders = [];
                foreach ($month as $m) {
                    if (isset($temp_orders[$m])) {
                        $growth_orders[] = $temp_orders[$m];
                    } else {
                        $growth_orders[] = 0;
                    }
    
                }
                $data['customers_chart'] = implode(",", $growth);
                $data['orders_chart'] = implode(",", $growth_orders);
                $content->body(view('admin.vendor_dashboard', $data));
                
            }else{
                $data['customers'] = Customer::where('status','!=',0)->count();
                $data['vendors'] = Vendor::where('status','!=',0)->count();
                $data['doctors'] = Doctor::where('status','!=',0)->count();
                $data['total_orders'] = Order::count();
                $data['completed_orders'] = Order::where('status','=',8)->count();
                $data['pending_orders'] = Order::where('status','!=',8)->count();
                $data['doctor_bookings'] = BookingRequest::count();
            $customers = Customer::select('id', 'created_at')
                ->get()
                ->groupBy(function ($val) {
                    return Carbon::parse($val->created_at)->format('M');
                });
            $bookings = BookingRequest::select('id', 'created_at')
                ->get()
                ->groupBy(function ($val) {
                    return Carbon::parse($val->created_at)->format('M');
                });
            
            $month = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            $temp = [];
            foreach ($customers as $c) {
                $temp[Carbon::parse($c[0]->created_at)->format('M')] = count($c);
            }
            $growth = [];
            foreach ($month as $m) {
                if (isset($temp[$m])) {
                    $growth[] = $temp[$m];
                } else {
                    $growth[] = 0;
                }

            }
            $temp_orders = [];
            foreach ($bookings as $o) {
                $temp_orders[Carbon::parse($o[0]->created_at)->format('M')] = count($o);
            }
            $growth_orders = [];
            foreach ($month as $m) {
                if (isset($temp_orders[$m])) {
                    $growth_orders[] = $temp_orders[$m];
                } else {
                    $growth_orders[] = 0;
                }

            }
            
            $data['customers_chart'] = implode(",", $growth);
            $data['bookings_chart'] = implode(",", $growth_orders);
            
                $content->body(view('admin.dashboard', $data));
            }
        });

    }
}
