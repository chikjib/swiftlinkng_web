<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\NecoPinResource;
use App\Models\NecoPins;
use Illuminate\Http\Request;
use App\Http\Resources\WaecPinResource;
use App\Http\Resources\JambPinResource;

use App\Models\WaecPins;
use App\Models\JambPins;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\Validator;

class PinController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //
        \Log::info($request->title);

        $pin_title = $request->title;
        if($pin_title == 'WAEC'){
            if($request->has('id')){
                $data =  WaecPins::where('id',$request->id)->orderBy('created_at','desc')->get();
                $products = WaecPinResource::collection($data);
            }else{
                $data =  WaecPins::orderBy('created_at','desc')->get();
                $products = WaecPinResource::collection($data);
            }

        }elseif($pin_title == 'NECO'){
            if($request->has('id')){
                $data =  NecoPins::where('id',$request->id)->orderBy('created_at','desc')->get();
                $products = NecoPinResource::collection($data);
            }else{
                $data =  NecoPins::orderBy('created_at','desc')->get();
                $products = NecoPinResource::collection($data);
            }

        }elseif($pin_title == 'JAMB'){
            if($request->has('id')){
                $data =  JambPins::where('id',$request->id)->orderBy('created_at','desc')->get();
                $products = JambPinResource::collection($data);
            }else{
               $data =  JambPins::orderBy('created_at','desc')->get();
                $products = JambPinResource::collection($data);
            }

        }


        return $products;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        //
        $input = $request->all();
        \Log::info($input);

        if($request->title == 'WAEC'){
            $validator = Validator::make($input, [
                'pin_no' => 'required|unique:waec_pins',
                'serial_no' => 'required|unique:waec_pins',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Pin no or serial no already exist', $validator->errors());
            }

            $pins = WaecPins::create($input);

        return $this->sendResponse(new WaecPinResource($pins), 'WaecPin added successfully.');

        }else if($request->title == 'NECO'){
            $validator = Validator::make($input, [
                'pin_no' => 'required|unique:neco_pins',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Pin no already exist', $validator->errors());
            }

            $pins = NecoPins::create($input);

        return $this->sendResponse(new NecoPinResource($pins), 'NecoPin added successfully.');

        }else if($request->title == 'JAMB'){
            $validator = Validator::make($input, [
                'pin_no' => 'required|unique:jamb_pins',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Pin no already exist', $validator->errors());
            }

            $pins = JambPins::create($input);

        return $this->sendResponse(new JambPinResource($pins), 'JambPin added successfully.');
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
        $input = $request->all();
        \Log::info($input);
        \Log::info($id);

        if($request->title == 'WAEC'){
            $waec_pin = WaecPins::find($id);
            $input = $request->all();
            $waec_pin->fill($input)->save();

        return $this->sendResponse(new WaecPinResource($waec_pin), 'WaecPin updated successfully.');

        }else if($request->title == 'NECO'){
            $neco_pin = NecoPins::find($id);
            $input = $request->all();
            $neco_pin->fill($input)->save();

        return $this->sendResponse(new NecoPinResource($neco_pin), 'NecoPin updated successfully.');

        }else if($request->title == 'JAMB'){

            $jamb_pin = JambPins::find($id);
            $input = $request->all();
            $jamb_pin->fill($input)->save();

        return $this->sendResponse(new JambPinResource($jamb_pin), 'JambPin updated successfully.');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        //

        if($request->title == "WAEC"){
            $waec =  WaecPins::find($request->id);
            $waec->delete();
        }elseif($request->title == "Neco"){
            $waec =  NecoPins::find($request->id);
            $waec->delete();
        }elseif($request->title == "JAMB"){
            $jamb =  JambPins::find($request->id);
            $jamb->delete();
        }


        return $this->sendResponse([], 'Pin deleted successfully.');
    }
}
