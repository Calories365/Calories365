<?php

namespace App\Services;

use FFMpeg\FFMpeg;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AudioConversionService
{
    protected FFMpeg $ffmpeg;

    public function __construct()
    {
        $this->ffmpeg = FFMpeg::create([
            'ffmpeg.binaries' => '/usr/bin/ffmpeg',
            'ffprobe.binaries' => '/usr/bin/ffprobe',
        ]);
    }

    /**
     * Convert any supported format (oga, ogg, m4a, mp4) to mp3.
     * Returns an array [relativeConvertedPath, absoluteConvertedPath].
     */
    public function convertToMp3(string $localPath, string $sourceFullPath): array
    {
        $extensionToMp3 = function (string $filename) {
            return preg_replace('/\.(oga|ogg|m4a|mp4)$/i', '.mp3', $filename);
        };

        $convertedLocalPath = $extensionToMp3($localPath);
        $convertedFullPath = Storage::disk('public')->path($convertedLocalPath);

        try {
            $audioFile = $this->ffmpeg->open($sourceFullPath);
            $mp3Format = new \FFMpeg\Format\Audio\Mp3;
            $audioFile->save($mp3Format, $convertedFullPath);

            return [$convertedLocalPath, $convertedFullPath];
        } catch (\Exception $e) {
            Log::error('Error converting audio: '.$e->getMessage());

            return [null, null];
        }
    }
}
