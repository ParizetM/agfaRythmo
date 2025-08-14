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
            'video' => 'required|file|mimetypes:video/mp4,video/quicktime|max:102400', // 100 Mo
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
}
