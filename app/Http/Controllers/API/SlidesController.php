<?php

namespace App\Http\Controllers\API;

use App\Models\Slides;
use Illuminate\Http\Request;
use App\Providers\UploadImageClass;
use App\Http\Resources\SlidesResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\Storage;

class SlidesController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products = Slides::all();

        return $this->sendResponse(SlidesResource::collection($products), 'Slides retrieved successfully.');
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
            'description' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $category = Slides::create($input);

        return $this->sendResponse(new SlidesResource($category), 'Slides created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Slides  $slides
     * @return \Illuminate\Http\Response
     */
    public function show(Slides $slides)
    {
        //
    }

    public function update($id, Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'description' => 'required',

        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $category =  Slides::find($id);
        $category->description = $input['description'];
        $category->save();

        return $this->sendResponse(new SlidesResource($category), 'slides updated successfully.');
    }



    public function uploadImage(Request $request)
{
    $request->validate([
        'cat_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        'description' => 'nullable|string|max:1000',
    ]);

    try {
        \Log::info('Upload slide started');

        $fileupload = new UploadImageClass();

        // Pass the complete Request because uploadFile expects Request
        $filename = $fileupload->uploadFile($request);

        $slide = new Slides();
        $slide->cat_image = $filename;
        $slide->description = $request->input('description');
        $slide->save();

        return $this->sendResponse(
            new SlidesResource($slide),
            'Slide uploaded successfully.'
        );
    } catch (\Throwable $exception) {
        \Log::error('Slide upload failed', [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        return $this->sendError(
            'Slide upload failed: ' . $exception->getMessage(),
            [],
            500
        );
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
        $category =  Slides::find($id);
              \Log::info("Upload slide");

        \Log::info($category->cat_image);
        \Log::info(Storage::disk('public')->exists('category/' . $category->cat_image));


        if (Storage::disk('public')->exists('category/' . $category->cat_image)) {
            Storage::disk('public')->delete('category/' . $category->cat_image);
            $category->delete();
            return $this->sendResponse([], 'Slides deleted successfully.');
        } else {
            return $this->sendError('Error', 'File not found');
        }
    }
}
