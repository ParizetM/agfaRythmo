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
}
