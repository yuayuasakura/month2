<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostRequest;
use App\Repositories\CategoryRepository;
use App\Repositories\PostRepository;
use App\Services\PostService;
use Illuminate\Support\Facades\Gate;

// コントローラーの役割は「リクエストを受け取る → 権限チェック → Service/Repository に渡す → レスポンスを返す」だけ
//   バリデーション：PostRequest
//   権限チェック：PostPolicy（Gate::authorize）
//   データの取得：PostRepository / CategoryRepository
//   データの保存・更新・削除：PostService
class PostController extends Controller
{
    public function __construct(
        private PostService $postService,
        private PostRepository $postRepository,
        private CategoryRepository $categoryRepository
    ) {}

    // 一覧表示（誰でも見られる）
    public function index()
    {
        $posts = $this->postRepository->getPublished();
        return view('posts.index', compact('posts'));
    }

    // 作成フォーム表示（ログインユーザーのみ：routes/web.php の auth ミドルウェアで制限）
    public function create()
    {
        $categories = $this->categoryRepository->getAll();
        return view('posts.create', compact('categories'));
    }

    // データ保存（バリデーションは PostRequest で行う）
    public function store(PostRequest $request)
    {
        // 画像の保存も含めて、保存の手順は全て PostService に任せる（コントローラーは受け取って渡すだけ）
        $post = $this->postService->createPost($request->validated(), $request->user()->id);

        return redirect()
            ->route('posts.show', $post)
            ->with('success', '投稿を作成しました');
    }

    // 詳細表示（誰でも見られる。なければ404）
    public function show(int $id)
    {
        $post = $this->postRepository->findWithComments($id);
        return view('posts.show', compact('post'));
    }

    // 編集フォーム表示
    public function edit(int $id)
    {
        $post = $this->postRepository->findById($id);
        // PostPolicy の update() で判定。自分の投稿でなければ 403 エラー
        Gate::authorize('update', $post);

        $categories = $this->categoryRepository->getAll();
        return view('posts.edit', compact('post', 'categories'));
    }

    // データ更新
    public function update(PostRequest $request, int $id)
    {
        $post = $this->postRepository->findById($id);
        // フォームを経由せず直接リクエストを送られても防げるよう、更新処理でもチェックする
        Gate::authorize('update', $post);

        $post = $this->postService->updatePost($post, $request->validated());

        return redirect()
            ->route('posts.show', $post)
            ->with('success', '投稿を更新しました');
    }

    // データ削除
    public function destroy(int $id)
    {
        $post = $this->postRepository->findById($id);
        Gate::authorize('delete', $post);

        $this->postService->deletePost($post);

        return redirect()
            ->route('posts.index')
            ->with('success', '投稿を削除しました');
    }
}
