<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PostStoreRequest;
use App\Http\Resources\PostResource;
use App\Repositories\PostRepository;
use App\Services\PostService;

class PostController extends Controller
{
    public function __construct(
        private PostService $postService,
        private PostRepository $postRepository
    ) {}

    // 一覧（新しい順・10件ずつ。投稿者もまとめて取得するので N+1 にならない）
    public function index()
    {
        $posts = $this->postRepository->getPublished();
        return PostResource::collection($posts);
    }

    public function show(int $id)
    {
        $post = $this->postRepository->findById($id);
        return new PostResource($post);
    }

    // 新規作成（バリデーションは PostStoreRequest、エラー時は 422 で JSON が返る）
    public function store(PostStoreRequest $request)
    {
        // 今週の API は認証なし。Seeder で作ったテスト用ユーザーの投稿として保存する（Service で設定）
        $post = $this->postService->createApiPost($request->validated());

        // new PostResource を return すると 201 Created で返る
        return new PostResource($post);
    }
}
