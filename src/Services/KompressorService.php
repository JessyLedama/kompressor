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
        }

        $manager = ImageManager::{$driver}();

        $originalName = uniqid() . "." . $uploadedFile->getClientOriginalExtension();
        $originalPath = $uploadedFile->storeAs($config['original_path'], $originalName);

        $image = $manager->read($uploadedFile->getRealPath());

        $tempPath = storage_path("app/temp_" . $originalName);
        $image->save($tempPath, 90);

        $optimizer = OptimizerChainFactory::create()->setTimeout(10);

        $quality = 90;
        while (filesize($tempPath) > ($maxKB * 1024) && $quality > 40) {
            $image->save($tempPath, $quality);
            $quality -= 5;
        }

        $optimizer->optimize($tempPath);

        $compressedName = "compressed_" . $originalName;
        $compressedPath = $config['compressed_path'] . '/' . $compressedName;

        //ensure the compressed directory exists then store the compressed image there.
        Storage::makeDirectory($config['compressed_path']);

        // Storage::put($compressedPath, file_get_contents($tempPath));
        Storage::disk('public')->put($compressedPath, file_get_contents($tempPath));

        unlink($tempPath);

        // Log elapsed time
        $elapsed = microtime(true) - $startTime;
        Log::info("Kompressor: Compression finished after {$elapsed} seconds.");

        return [
            'original' => $originalPath,
            'compressed' => $compressedPath,
            'final_size_kb' => round(filesize(storage_path("app/public/".$compressedPath)) / 1024, 2),
            'driver_used' => $driver
        ];
    }
}
