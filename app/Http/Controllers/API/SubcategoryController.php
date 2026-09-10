<?php

namespace App\Http\Controllers\API;

use App\Models\Subcategory;
use Auth;
use Illuminate\Http\Request;
use App\Http\Resources\PlansResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\SubcategoryResource;
use App\Http\Resources\PlansCategoryResource;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class SubcategoryController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $category_id = $request->input('category_id');
         

        if ($request->has('category_id')) {
             $data = Subcategory::where('category_id', $category_id)->where('status', 1)->orderBy('position','asc')->get();
        } else if ($request->has('search')) { 

            $data = Subcategory::where('title', 'like', '%' . $request->search . '%')
                ->orWhere('description', 'like', '%' . $request->search . '%')
                ->orWhere('description2', 'like', '%' . $request->search . '%')
                ->orderBy('category_id', 'asc')->orderBy('position', 'desc')->paginate(10);
        } else if ($request->has('all')) {
            // $data = Subcategory::orderBy('category_id', 'asc')->orderBy('created_at', 'desc')->get();
            $data = Subcategory::orderBy('category_id', 'asc')->orderBy('position', 'desc')->get();
        } else {
            $data = Subcategory::orderBy('category_id', 'asc')->orderBy('position', 'desc')->paginate(10);
        }


        $products = SubcategoryResource::collection($data);

        return $products;
    }
    
    public function load_accounting(Request $request)
    {
        $data = Subcategory::whereIn('category_id', [1, 2, 3, 4, 6])->orderBy('category_id', 'asc')->orderBy('position', 'desc')->get();

        return SubcategoryResource::collection($data);
    }

    public function load_public_plans()
    {
        $data =  Subcategory::where('status', 1)->whereIn('category_id', [1, 2, 3, 4, 6])->get();
        $products = PlansResource::collection($data);

        return $products;
    }

    public function load_plans(Request $request)
    {

        $data =  Subcategory::where(['category_id' => $request->category_id, 'status' => 1])->orderBy('position','asc')->get();

        $products = PlansCategoryResource::collection($data);
        
        //\Log::info(print_r($products, true));

        return $products;
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
            'title' => 'required',
            'category_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $category = Subcategory::create($input);

        return $this->sendResponse(new SubcategoryResource($category), 'SubCategory created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $subcategory = Subcategory::find($id);

        if (is_null($subcategory)) {
            return $this->sendError('Category not found.');
        }

        return $this->sendResponse(new SubcategoryResource($subcategory), 'SubCategory retrieved successfully.');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Subcategory $subcategory)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'title' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        // $subcategory->title = $input['title'];
        // $subcategory->category_id = $input['category_id'];
        // $subcategory->save();
        $admin_user = User::find($this->user->id);
        if($admin_user->role == 1){
            $input = $request->all();
            $subcategory->fill($input)->save();
    
    
    
            return $this->sendResponse(new SubcategoryResource($subcategory), 'Subcategory updated successfully.');
        }else{
            return $this->sendError("Processing failed");
        }
    }
    
     public function toggleAllPackages(Request $request, $title)
    {
        $user = Auth::user();

        if (!$user || $user->role != 1) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $title = trim($title);

        $packages = Subcategory::where('title', 'LIKE', "%{$title}%")->get();

        if ($packages->isEmpty()) {
            return response()->json([
                'message' => 'No package found'
            ], 404);
        }

        // Check first package status
        $newStatus = $packages->first()->status ? 0 : 1;

        // Update all matched packages
        $count = Subcategory::whereIn('id', $packages->pluck('id'))
            ->update([
                'status' => $newStatus
            ]);

        $action = $newStatus ? 'activated' : 'deactivated';

        return $this->sendResponse(
            $count . " plans {$action}",
            $count . " plans {$action}"
        );
    }

    public function toggleActivePackage(Request $request, $id)
    {
        $user = Auth::user();

        if ($user && $user->role == 1) {

            $subcategory = Subcategory::find($id);

            if (!$subcategory) {
                return response()->json([
                    'message' => 'Package not found'
                ], 404);
            }

            $newStatus = $subcategory->status == 1 ? 0 : 1;

            $subcategory->update([
                'status' => $newStatus
            ]);

            return $this->sendResponse(
                $newStatus ? "Plan activated" : "Plan deactivated",
                $subcategory
            );
        }

        return response()->json([
            'message' => 'Unauthorized'
        ], 403);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
     
    
    public function destroy($id)
    {
        $admin_user = User::find($this->user->id);
        \Log::info($admin_user);
        if($admin_user->role == 1){
            $subcategory =  Subcategory::find($id);
            $subcategory->delete();
    
            return $this->sendResponse([], 'Subcategory deleted successfully.');
        }else {
            return $this->sendError("Processing failed!");
        }
    }
}
