<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bed;
use Illuminate\Support\Facades\DB;
use Validator;

class BedApiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function allBeds()
    {
        //
        $beds = DB::table('beds')
        ->select('beds.*','hospitals.*')
            ->leftJoin('hospitals', 'hospitals.id', '=', 'beds.hospital_id')
            ->orderBy('beds.created_at', 'desc')
            ->get();
            
        if ($beds) {
            return response()->json([
                "result" => $beds,
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


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    // public function sendError($message, $code = 404): JsonResponse
    // {
    //     return response()->json(['error' => $message], $code);
    // }
    
   
    public function create(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'bed_count' => 'required|numeric',
            'bed_type' => 'required|in:General Ward Beds,ICU Beds,NICU Beds,PICU Beds,Maternity Beds,Emergency Room Beds,Surgical Beds,Isolation Beds,Psychiatric Beds,Hospice Beds',
            'hospital_id' => 'required|numeric',
        ]);
        

        if ($validator->fails()) {
            // return $this->sendError($validator->errors());
            // return response()->json([
            //     "message" => $validator->errors(),
            //     "status" => 404
            // ]);
            return response()->json(['message' => $validator->errors()], '404');
        }

        
        $input['status'] = 1;
        

        $beds = Bed::create($input);
        $cus = Bed::where('id',$beds->id)->first();

        if (is_object($cus)) {
          
            return response()->json([
                "result" => $cus,
                "message" => 'Add New Successfully',
                "status" => 1
            ]);
        } else {
            return response()->json([
                "message" => 'Sorry, something went wrong !',
                "status" => 0
            ]);
        }

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
        $beds = DB::table('beds')
        ->select('beds.*','hospitals.*')
            ->leftJoin('hospitals', 'hospitals.id', '=', 'beds.hospital_id')
            
            ->where('beds.id',$id)
            ->orderBy('beds.created_at', 'desc')
            ->get();
            
        if ($beds) {
            return response()->json([
                "result" => $beds,
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

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        // print_r($id);die;
        $beds = DB::table('beds')
        ->select('beds.*','hospitals.*')
            ->leftJoin('hospitals', 'hospitals.id', '=', 'beds.hospital_id')
            
            ->where('beds.id',$id)
            ->orderBy('beds.created_at', 'desc')
            ->get();
            
        if ($beds) {
            return response()->json([
                "result" => $beds,
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

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        
        $input = $request->all();
        $validator = Validator::make($input, [
            'bed_id' => 'required|numeric',
            'bed_count' => 'required|numeric',
            'hospital_id' => 'required|numeric',
            'bed_type' => 'required|in:General Ward Beds,ICU Beds,NICU Beds,PICU Beds,Maternity Beds,Emergency Room Beds,Surgical Beds,Isolation Beds,Psychiatric Beds,Hospice Beds',
        ]);
        

        if ($validator->fails()) {
           
            return response()->json(['message' => $validator->errors()], '404');
        }
// print_r($input);die;

$beds = DB::table('beds')
        ->select('beds.*','hospitals.hospital_name','hospitals.hospital_logo','hospitals.phone_number')
            ->leftJoin('hospitals', 'hospitals.id', '=', 'beds.hospital_id')
            ->where('beds.id',$input['bed_id'])->first();
            if($beds->bed_type == 'ICU Beds'){
                $updatebeds =  Bed::where('id',$input['bed_id'])->update(['hospital_id' => $input['hospital_id'] ]);
            }else{
                $updatebeds =  Bed::where('id',$input['bed_id'])->update(['bed_count' => $input['bed_count'], 'bed_type' => $input['bed_type'],'hospital_id' => $input['hospital_id'] ]);
            }
            // $updatebeds =  Bed::where('id',$input['bed_id'])->update(['bed_count' => $input['bed_count'], 'bed_type' => $input['bed_type'],'hospital_id' => $input['hospital_id'] ]);
            // return $beds;
            
       
        $beds = DB::table('beds')
        ->select('beds.*','hospitals.hospital_name','hospitals.hospital_logo','hospitals.phone_number')
            ->leftJoin('hospitals', 'hospitals.id', '=', 'beds.hospital_id')
            ->where('beds.id',$input['bed_id'])->first();
        if ($beds) {
            return response()->json([
                "result" => $beds,
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {


        $bed = Bed::find($id); // Find the bed by its ID

    if ($bed) {
        $bed->delete(); // Delete the bed record

        return response()->json([
            "message" => 'Bed deleted successfully',
            "status" => 1
        ]);
    } else {
        return response()->json([
            "message" => 'Bed not found',
            "status" => 0
        ], 404); // Return 404 status code for not found
    }
    }
}

