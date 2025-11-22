<?php

namespace JessyLedama\Kompressor\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use Illuminate\Support\Facades\Log;

class KompressorService
{
    
    public function compress($uploadedFile)
    {
        $startTime = microtime(true); // for tracking how long it takes to compress.

        $config = config('kompressor');
        $maxKB = $config['max_kb'];

        // Check for Imagick
        if (extension_loaded('imagick')) {
            $driver = 'imagick';
            Log::info('Kompressor: Imagick is available. Using Imagick driver.');
        } else {
            $driver = 'gd';
            Log::warning('Kompressor: Imagick not available. Falling back to GD driver.');
            // install imagick extension for better performance
            exec('apt-get install -y php-imagick');
            
            // after installing, check again if imagick is available
            if (extension_loaded('imagick')) {
                $driver = 'imagick';
                Log::info('Kompressor: Imagick installed successfully. Using Imagick driver.');
            } else {
                Log::error('Kompressor: Imagick installation failed. Continuing with GD driver.');
                // Fallback to GD driver
                $driver = 'gd';
                Log::info('Kompressor: Continuing with GD driver.');
            }
        }

        $manager = ImageManager::{$driver}();

        $originalName = uniqid() . "." . $uploadedFile->getClientOriginalExtension();
        $originalPath = $uploadedFile->storeAs($config['original_path'], $originalName);

        $image = $manager->read($uploadedFile->getRealPath());

        // Resize the image to a maximum width/height while maintaining aspect ratio
        $image->resize(1920, 1080, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $tempPath = storage_path("app/temp_" . $originalName);
        $image->save($tempPath, 85); // Start with a moderate quality

        // Use a single optimization pass instead of iterative quality reduction
        $optimizer = OptimizerChainFactory::create()->setTimeout(10);        
        $optimizer->optimize($tempPath);

        // Check final size and adjust if necessary
        if (filesize($tempPath) > ($maxKB * 1024)) {
            // If still too large, reduce quality further in one step
            $image->save($tempPath, 75);
            $optimizer->optimize($tempPath);
        }

        $compressedName = "compressed_" . $originalName;
        $compressedPath = $config['compressed_path'] . '/' . $compressedName;

        //ensure the compressed directory exists then store the compressed image there.
        Storage::makeDirectory($config['compressed_path']);

        Storage::disk('public')->put($compressedPath, file_get_contents($tempPath));

        unlink($tempPath);

        // Log elapsed time
        $elapsed = microtime(true) - $startTime;
        Log::info("Kompressor: Fast compression finished after {$elapsed} seconds.");

        return [
            'original' => $originalPath,
            'compressed' => $compressedPath,
            'final_size_kb' => round(filesize(storage_path("app/public/".$compressedPath)) / 1024, 2),
            'driver_used' => $driver,
            'compression_time_seconds' => $elapsed
        ];
    }
}
