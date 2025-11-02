<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'video' => 'required|file|mimetypes:video/mp4', // MP4 uniquement, pas de limite de taille
        ]);

        $file = $request->file('video');
        $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('public/videos', $filename);

        $url = Storage::url('videos/' . $filename);

        return response()->json([
            'url' => $url,
        ]);
    }

    public function stream($filename)
    {
        $path = 'public/videos/' . $filename;
        if (!Storage::exists($path)) {
            abort(404);
        }

        // Récupère le chemin absolu du fichier (compatible local/public)
        if (method_exists(Storage::disk(), 'path')) {
            $fullPath = Storage::disk()->path($path);
        } else {
            $fullPath = base_path('storage/app/' . $path);
        }
        $size = filesize($fullPath);
        $mime = Storage::mimeType($path);

        $headers = [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Accept-Ranges' => 'bytes',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
            'Access-Control-Allow-Headers' => 'Range',
            'Access-Control-Expose-Headers' => 'Content-Range, Content-Length, Accept-Ranges',
        ];

        if (isset($_SERVER['HTTP_RANGE'])) {
            $range = $_SERVER['HTTP_RANGE'];
            list(, $range) = explode('=', $range, 2);
            list($start, $end) = explode('-', $range) + [null, null];
            $start = $start === '' ? 0 : intval($start);
            $end = $end === null || $end === '' ? $size - 1 : intval($end);
            $length = $end - $start + 1;

            $fh = fopen($fullPath, 'rb');
            fseek($fh, $start);

            $headers['Content-Range'] = "bytes $start-$end/$size";
            $headers['Content-Length'] = $length;

            return response()->stream(function () use ($fh, $length) {
                $buffer = 8192;
                $sent = 0;
                while (!feof($fh) && $sent < $length) {
                    $toRead = min($buffer, $length - $sent);
                    echo fread($fh, $toRead);
                    $sent += $toRead;
                    @ob_flush();
                    flush();
                }
                fclose($fh);
            }, 206, $headers);
        } else {
            $headers['Content-Length'] = $size;
            $fh = fopen($fullPath, 'rb');
            return response()->stream(function () use ($fh) {
                fpassthru($fh);
                fclose($fh);
            }, 200, $headers);
        }
    }

    public function streamInstrumental($projectId, $filename)
    {
        $path = 'instrumental/' . $projectId . '/' . $filename;

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $fullPath = Storage::disk('public')->path($path);
        $size = filesize($fullPath);
        $mime = mime_content_type($fullPath) ?: 'audio/wav';

        $headers = [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Accept-Ranges' => 'bytes',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
            'Access-Control-Allow-Headers' => 'Range',
            'Access-Control-Expose-Headers' => 'Content-Range, Content-Length, Accept-Ranges',
        ];

        if (isset($_SERVER['HTTP_RANGE'])) {
            $range = $_SERVER['HTTP_RANGE'];
            list(, $range) = explode('=', $range, 2);
            list($start, $end) = explode('-', $range) + [null, null];
            $start = $start === '' ? 0 : intval($start);
            $end = $end === null || $end === '' ? $size - 1 : intval($end);
            $length = $end - $start + 1;

            $fh = fopen($fullPath, 'rb');
            fseek($fh, $start);

            $headers['Content-Range'] = "bytes $start-$end/$size";
            $headers['Content-Length'] = $length;

            return response()->stream(function () use ($fh, $length) {
                $buffer = 8192;
                $sent = 0;
                while (!feof($fh) && $sent < $length) {
                    $toRead = min($buffer, $length - $sent);
                    echo fread($fh, $toRead);
                    $sent += $toRead;
                    @ob_flush();
                    flush();
                }
                fclose($fh);
            }, 206, $headers);
        } else {
            $headers['Content-Length'] = $size;
            $fh = fopen($fullPath, 'rb');
            return response()->stream(function () use ($fh) {
                fpassthru($fh);
                fclose($fh);
            }, 200, $headers);
        }
    }

    public function extractAudio($filename)
    {
        // Le $filename peut contenir "/storage/videos/xyz.mp4" ou "videos/xyz.mp4"
        // On extrait juste le nom du fichier
        $basename = basename($filename);
        $videoPath = 'public/videos/' . $basename;

        if (!Storage::exists($videoPath)) {
            abort(404, 'Video not found: ' . $videoPath);
        }

        // Chemin absolu de la vidéo
        $fullVideoPath = Storage::path($videoPath);

        // Créer un nom de fichier temporaire pour l'audio
        $audioFilename = pathinfo($basename, PATHINFO_FILENAME) . '.wav';
        $tempAudioPath = storage_path('app/temp/' . $audioFilename);

        // Créer le répertoire temp s'il n'existe pas
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        // Extraire l'audio avec FFmpeg (comme dans DetectSceneChanges.php)
        // Utiliser le chemin complet si FFmpeg n'est pas dans le PATH
        $ffmpegPath = config('ai.ffmpeg_path', '/opt/homebrew/bin/ffmpeg');

        $command = sprintf(
            '%s -i %s -vn -acodec pcm_s16le -ar 44100 -ac 2 %s 2>&1',
            $ffmpegPath,
            escapeshellarg($fullVideoPath),
            escapeshellarg($tempAudioPath)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0 || !file_exists($tempAudioPath)) {
            return response()->json([
                'error' => 'Failed to extract audio from video',
                'details' => implode("\n", $output)
            ], 500);
        }

        // Stream le fichier audio et le supprimer après
        $headers = [
            'Content-Type' => 'audio/wav',
            'Content-Disposition' => 'attachment; filename="' . $audioFilename . '"',
            'Content-Length' => filesize($tempAudioPath),
            'Access-Control-Allow-Origin' => '*',
        ];

        return response()->stream(function () use ($tempAudioPath) {
            $fh = fopen($tempAudioPath, 'rb');
            fpassthru($fh);
            fclose($fh);

            // Supprimer le fichier temporaire après l'envoi
            @unlink($tempAudioPath);
        }, 200, $headers);
    }
}
