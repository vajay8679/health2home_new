<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Validator;


class CommonController extends Controller
{

	/**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function get_notifications(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'app_module' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        
        $data = Notification::where('app_module',$input['app_module'])->orderBy('id','DESC')->get();
        
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


