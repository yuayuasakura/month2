<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageController extends Controller
{
    // アップロード画面を表示
    public function create()
    {
        return view('images.create');
    }

    // 画像を受け取ってWebPに変換し、S3に保存
    public function store(Request $request)
    {
        // 画像かどうか・サイズをチェック（5MBまで）
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,gif,webp|max:5120',
        ]);

        // ↓↓↓ ここに、もらったコードを貼る ↓↓↓
        $file = $request->file('image');

        $image = imagecreatefromstring(file_get_contents($file->getRealPath()));
        imagepalettetotruecolor($image);

        ob_start();
        imagewebp($image, null, 80);
        $webp = ob_get_clean();
        imagedestroy($image);

        $path = 'images/' . Str::uuid() . '.webp';
        Storage::disk('s3')->put($path, $webp);
        $url = Storage::disk('s3')->url($path);
        // ↑↑↑ ここまで ↑↑↑

        // 保存した画像のURLを持って、画面に戻る
        return back()->with('url', $url);
    }
}