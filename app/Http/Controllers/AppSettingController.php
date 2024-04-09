<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomerAppSetting;
use App\Models\DoctorAppSetting;
use App\Models\DeliveryBoyAppSetting;
use App\Models\VendorAppSetting;
class AppSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function customer_app_setting()
    {
        $data = CustomerAppSetting::first();
        $data->mode = env('MODE');
        $data->stripe_key = env('STRIPE_KEY');
        $data->stripe_secret = env('STRIPE_API_KEY');
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
    public function doctor_app_setting()
    {
        $data = DoctorAppSetting::first();
        $data->mode = env('MODE');
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
    public function delivery_boy_app_setting()
    {
        $data = DeliveryBoyAppSetting::first();
        $data->mode = env('MODE');
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
    }
    
    public function vendor_app_setting()
    {
        $data = VendorAppSetting::first();
        $data->mode = env('MODE');
        return response()->json([
            "result" => $data,
            "message" => 'Success',
            "status" => 1
        ]);
    }
    


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
