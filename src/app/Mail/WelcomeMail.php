<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// 会員登録が完了したときに送るウェルカムメール
class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    // public なプロパティはメールのテンプレートでそのまま使える（{{ $user->name }}）
    public function __construct(public User $user) {}

    // 件名など（送信元は .env の MAIL_FROM_ADDRESS / MAIL_FROM_NAME が使われる）
    public function envelope(): Envelope
    {
        return new Envelope(subject: '【' . config('app.name') . '】ご登録ありがとうございます');
    }

    // 本文（Markdown 形式のテンプレートを使うと、ボタンなどが整った見た目の HTML メールになる）
    public function content(): Content
    {
        return new Content(markdown: 'emails.welcome');
    }
}
