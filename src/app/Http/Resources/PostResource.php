<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'content'    => $this->content,
            'user'       => [
                'name' => $this->user->name,   // 投稿者の名前（パスワードやメールアドレスは返さない）
            ],
            'created_at' => $this->created_at->toDateString(),
        ];
    }
}