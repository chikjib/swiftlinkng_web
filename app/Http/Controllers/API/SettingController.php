<?php

namespace App\Http\Controllers\API;

use App\Models\Setting;

use Illuminate\Http\Request;
use App\Http\Resources\SettingResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\UserSettingResource;
use App\Http\Controllers\API\BaseController as BaseController;

class SettingController extends BaseController
{
    private const DASHBOARD_UPDATE_SETTINGS = [
        'APP_UPDATE_EYEBROW' => 'Updates for You',
        'APP_UPDATE_TITLE' => 'OPay wallet funding is now available!',
        'APP_UPDATE_MESSAGE' => 'Enjoy instant funding with low charges.',
        'APP_UPDATE_DETAILS' => 'OPay wallet funding is now available on Swiftlinkng. Open Fund Wallet, choose My Personal Account and transfer to your OPay account for fast, secure wallet funding.',
        'APP_UPDATE_LINK' => '',
        'APP_UPDATE_ENABLED' => '1',
    ];

    public function dashboardUpdate()
    {
        $stored = Setting::whereIn(
            'key',
            array_keys(self::DASHBOARD_UPDATE_SETTINGS)
        )->pluck('value', 'key');

        return response()->json([
            'status' => true,
            'data' => [
                'eyebrow' => $stored->get('APP_UPDATE_EYEBROW', self::DASHBOARD_UPDATE_SETTINGS['APP_UPDATE_EYEBROW']),
                'title' => $stored->get('APP_UPDATE_TITLE', self::DASHBOARD_UPDATE_SETTINGS['APP_UPDATE_TITLE']),
                'message' => $stored->get('APP_UPDATE_MESSAGE', self::DASHBOARD_UPDATE_SETTINGS['APP_UPDATE_MESSAGE']),
                'details' => $stored->get('APP_UPDATE_DETAILS', self::DASHBOARD_UPDATE_SETTINGS['APP_UPDATE_DETAILS']),
                'link' => $stored->get('APP_UPDATE_LINK', self::DASHBOARD_UPDATE_SETTINGS['APP_UPDATE_LINK']),
                'enabled' => $stored->get('APP_UPDATE_ENABLED', self::DASHBOARD_UPDATE_SETTINGS['APP_UPDATE_ENABLED']) === '1',
            ],
        ]);
    }

    public function updateDashboardUpdate(Request $request)
    {
        $validated = $request->validate([
            'eyebrow' => ['required', 'string', 'max:40'],
            'title' => ['required', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:240'],
            'details' => ['nullable', 'string', 'max:5000'],
            'link' => ['nullable', 'url', 'max:500'],
            'enabled' => ['required', 'boolean'],
        ]);

        $values = [
            'APP_UPDATE_EYEBROW' => $validated['eyebrow'],
            'APP_UPDATE_TITLE' => $validated['title'],
            'APP_UPDATE_MESSAGE' => $validated['message'] ?? '',
            'APP_UPDATE_DETAILS' => $validated['details'] ?? '',
            'APP_UPDATE_LINK' => $validated['link'] ?? '',
            'APP_UPDATE_ENABLED' => $validated['enabled'] ? '1' : '0',
        ];

        foreach ($values as $key => $value) {
            $setting = Setting::where('key', $key)->first() ?: new Setting();
            $setting->key = $key;
            $setting->value = $value;
            $setting->save();
        }

        return $this->dashboardUpdate();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products = Setting::all();

        return $this->sendResponse(SettingResource::collection($products), 'Settings retrieved successfully.');
    }
    
    public function getTokensMtn()
    {
        $products = Setting::whereIn('id',[15,16,17,18,19,20,21,22])->get();


        return $this->sendResponse(SettingResource::collection($products), 'Settings retrieved successfully.');
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'key' => 'required',
            'value' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $category = Setting::create($input);

        return $this->sendResponse(new SettingResource($category), 'Setting created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $category = Setting::find($id);

        if (is_null($category)) {
            return $this->sendError('Category not found.');
        }

        return $this->sendResponse(new SettingResource($category), 'Setting retrieved successfully.');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id, Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'key' => 'required',
            'value' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $category =  Setting::find($id);
        $category->key = $input['key'];
        $category->value = $input['value'];
        $category->save();

        return $this->sendResponse(new SettingResource($category), 'Setting updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $category =  Setting::find($id);
        $category->delete();

        return $this->sendResponse([], 'Setting deleted successfully.');
    }
}
