<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PrivacyPolicy;
use App\Models\Status;
use Validator;


class PrivacyPolicyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function get_privacy_policy(Request $request)
    {
        $input = $request->all();
        
        $validator = Validator::make($input, [
            'user_type' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        $data = PrivacyPolicy::where('status',1)->where('privacy_policy_type_id',$input['user_type'])->get();
        
        
        return response()->json([
            "result" => $data,
            "count" => count($data),
            "message" => 'Success',
            "status" => 1
        ]);
    }
  
    
    public function sendError($message) {
        $message = $message->all();
        $response['error'] = "validation_error";
        $response['message'] = implode('',$message);
        $response['status'] = "0";
        return response()->json($response, 200);
    }
}





