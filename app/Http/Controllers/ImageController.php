<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Intervention\Image\ImageManager;

class ImageController extends Controller
{
    protected $imageManager;

    public function __construct(ImageManager $imageManager)
    {
        $this->imageManager = $imageManager;
    }

    public function index()
    {
        return view('image-editor');
    }

    public function crop(Request $request)
    {
        $request->validate([
            'image' => 'required|image',
            'x' => 'required|numeric',
            'y' => 'required|numeric',
            'width' => 'required|numeric',
            'height' => 'required|numeric',
            'watermark' => 'nullable|image',
            'watermark_position' => 'nullable|string',
            'watermark_size' => 'nullable|numeric',
            'watermark_opacity' => 'nullable|numeric|min:10|max:100'
        ]);

        $image = $this->imageManager->read($request->file('image'));
        
        // Crop image
        $image->crop($request->width, $request->height, $request->x, $request->y);

        // Add watermark if provided
        if ($request->hasFile('watermark')) {
            $watermark = $this->imageManager->read($request->file('watermark'));
            
            // Resize watermark
            $watermarkSize = $request->watermark_size ?? 100;
            $watermark->scale($watermarkSize, $watermarkSize);
            
            // Apply opacity (skip if 100%)
            $opacity = $request->watermark_opacity ?? 100;
            if ($opacity < 100) {
                // Create transparent overlay effect
                $watermark = $watermark->brightness($opacity - 100);
            }

            // Position watermark
            $position = $request->watermark_position ?? 'bottom-right';
            
            if ($position === 'repeat') {
                $spacing = 50;
                for ($x = 0; $x < $image->width(); $x += $watermark->width() + $spacing) {
                    for ($y = 0; $y < $image->height(); $y += $watermark->height() + $spacing) {
                        $image->place($watermark, 'top-left', $x, $y);
                    }
                }
            } else {
                $offsetX = $offsetY = 10;
                
                switch($position) {
                    case 'top-left':
                        $offsetX = 10; $offsetY = 10;
                        break;
                    case 'top-right':
                        $offsetX = $image->width() - $watermark->width() - 10;
                        $offsetY = 10;
                        break;
                    case 'bottom-left':
                        $offsetX = 10;
                        $offsetY = $image->height() - $watermark->height() - 10;
                        break;
                    case 'bottom-right':
                        $offsetX = $image->width() - $watermark->width() - 10;
                        $offsetY = $image->height() - $watermark->height() - 10;
                        break;
                    case 'center':
                        $offsetX = ($image->width() - $watermark->width()) / 2;
                        $offsetY = ($image->height() - $watermark->height()) / 2;
                        break;
                }
                
                $image->place($watermark, 'top-left', $offsetX, $offsetY);
            }
        }

        // Save to storage
        $filename = 'cropped_' . time() . '.jpg';
        $path = storage_path('app/public/' . $filename);
        $image->save($path);

        return response()->json([
            'success' => true,
            'download_url' => asset('storage/' . $filename)
        ]);
    }
}