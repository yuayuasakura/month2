<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PostResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'content'    => $this->content,
            // 画像がなければ null（フロントでは image_url があるときだけ <img> を表示する）
            'image_url'  => $this->image_path ? Storage::disk('s3')->url($this->image_path) : null,
            'user'       => [
                'name' => $this->user->name,   // 投稿者の名前（パスワードやメールアドレスは返さない）
            ],
            'created_at' => $this->created_at->toDateString(),
        ];
    }
}