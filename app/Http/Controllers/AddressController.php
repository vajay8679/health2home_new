<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\Address;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    public function add_address(Request $request)
    {   
        $input = $request->all();

        $validator =  Validator::make($input,[
            'customer_id' => 'required',
            'address' => 'required',
            'landmark' => 'required',
            'lat' => 'required',
            'lng' => 'required'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }
        
        $input['status'] = 1;

        if (Address::create($input)) {
            return response()->json([
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
    
    public function update(Request $request)
    {
        $input = $request->all();
       
        $validator =  Validator::make($input,[
            'id' => 'required',
            'customer_id' => 'required',
            'address' => 'required',
            'landmark' => 'required',
            'lat' => 'required',
            'lng' => 'required'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }
        
        $input['status'] = 1;

        if (Address::where('id',$input['id'])->update($input)) {
            return response()->json([
                "message" => 'Updated Successfully',
                "status" => 1
            ]);
        } else {
            return response()->json([
                "message" => 'Sorry, something went wrong !',
                "status" => 0
            ]);
        }
    }
    
    public function all_addresses(Request $request){

        $input = $request->all();

        $validator =  Validator::make($input,[
            'customer_id' => 'required',
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        //$addresses = CustomerAddress::where('customer_id',$input['customer_id'])->orderBy('created_at', 'desc')->get();
        $addresses = DB::table('addresses')
            ->leftJoin('customers', 'customers.id', '=', 'addresses.customer_id')
            ->select('addresses.*','customers.customer_name')
            ->where('addresses.customer_id',$input['customer_id'])
            ->orderBy('addresses.created_at', 'desc')
            ->get();
        if ($addresses) {
            return response()->json([
                "result" => $addresses,
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
    
    public function edit(Request $request)
    {
        $input = $request->all();
        //$input['id'] = $id;

        $validator =  Validator::make($input,[
            'id' => 'required'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $address = Address::where('id',$input['id'])->first();

        if ($address) {
            return response()->json([
                "result" => $address,
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
    
    public function delete(Request $request)
    {
        $input = $request->all();

        $validator =  Validator::make($input,[
            'customer_id' => 'required',
            'address_id' => 'required'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }
        $res = Address::where('id',$input['address_id'])->delete();
        if ($res) {
            $addresses = Address::where('customer_id',$input['customer_id'])->orderBy('created_at', 'desc')->get();
            return response()->json([
                "result" => $addresses,
                "message" => 'Deleted Successfully',
                "status" => 1
            ]);
        } else {
            return response()->json([
                "message" => 'Sorry, something went wrong !',
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
