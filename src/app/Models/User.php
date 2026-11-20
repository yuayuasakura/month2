<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\PostListCache;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // hasMany: ユーザー(1) → 投稿(多)
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    // hasMany: ユーザー(1) → コメント(多)
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    // hasMany: ユーザー(1) → いいね(多)
    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    // hasMany: ユーザー(1) → タスク(多)
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    // 投稿一覧には投稿者名を表示しているので、名前の変更・退会でキャッシュを作り直す
    // （ログインのたびに remember_token が更新されるので、名前が変わったときだけにする）
    // 退会時は投稿が DB の cascadeOnDelete で消える（Post のイベントは起きない）ので、ここで作り直す
    protected static function booted(): void
    {
        static::updated(function (User $user) {
            if ($user->wasChanged('name')) {
                PostListCache::flush();
            }
        });
        static::deleted(fn () => PostListCache::flush());
    }
}
