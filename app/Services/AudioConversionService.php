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
     * Convert any supported format (oga, ogg, m4a, mp4, webm) to mp3.
     * Returns an array [relativeConvertedPath, absoluteConvertedPath].
     */
    public function convertToMp3(string $localPath, string $sourceFullPath): array
    {
        // webm was missing: the target named the source, ffmpeg could not write
        // over its own input, and the recording went to transcription unconverted.
        $extensionToMp3 = function (string $filename) {
            return preg_replace('/\.(oga|ogg|m4a|mp4|webm)$/i', '.mp3', $filename);
        };

        $convertedLocalPath = $extensionToMp3($localPath);
        $convertedFullPath = Storage::disk('public')->path($convertedLocalPath);

        // An mp3 or an unknown format would be written over itself, destroying the source.
        if ($convertedLocalPath === $localPath) {
            return [null, null];
        }

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
