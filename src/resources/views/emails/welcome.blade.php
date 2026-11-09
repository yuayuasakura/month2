{{-- ウェルカムメールの本文（Markdown メール）。WelcomeMail の public $user が使える --}}
{{-- ※ Markdown として解釈されるので、行頭にインデント（字下げ）を入れないこと（コードブロック扱いになる） --}}
<x-mail::message>
# {{ $user->name }} さん、ようこそ！

{{ config('app.name') }} への会員登録が完了しました。

ご登録いただいたメールアドレス：{{ $user->email }}

さっそくブログに投稿してみましょう。

<x-mail::button :url="route('posts.create')">
新規投稿する
</x-mail::button>

このメールに心当たりがない場合は、お手数ですが破棄してください。

{{ config('app.name') }}
</x-mail::message>
