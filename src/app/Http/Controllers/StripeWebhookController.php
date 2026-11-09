<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\Webhook;

// Stripe からの Webhook（決済完了などのお知らせ）を受け取る
// ルートは routes/api.php の POST /api/webhook/stripe（api ルートなので CSRF チェックはかからない）
class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 署名検証には「加工前の生の本文」が必要（$request->all() などで JSON を解釈した後の値では検証できない）
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        try {
            // 署名を検証してからイベントを組み立てる
            // Stripe-Signature ヘッダーが無い・署名が合わない・5分以上前のリクエスト（なりすましや再送攻撃）は例外になる
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException $e) {
            // 署名が不正 → 偽のリクエストを拒否
            // （STRIPE_WEBHOOK_SECRET が stripe listen で表示された whsec_ と違う場合もここに来る）
            Log::warning('Stripe webhook: 署名検証に失敗', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        } catch (UnexpectedValueException $e) {
            // 本文が正しい JSON ではない
            Log::warning('Stripe webhook: 不正なペイロード', ['error' => $e->getMessage()]);
            return response('Invalid payload', 400);
        }

        // イベントの種類に応じて処理を分岐
        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;

            // 決済ID（pi_...）をログに記録する（storage/logs/laravel.log）
            Log::info('決済完了: ' . $paymentIntent->id, [
                'payment_intent_id' => $paymentIntent->id,
                'amount'            => $paymentIntent->amount,    // 最小単位（円なら1円単位、ドルならセント）
                'currency'          => $paymentIntent->currency,
                'event_id'          => $event->id,
            ]);
            // 例: 応援した履歴（supports）の記録や、書き手へのお知らせメールの送信など
        }

        // 処理しないイベントも 200 を返す（200 以外を返すと Stripe が失敗とみなして何度も再送してくる）
        return response('OK', 200);
    }
}
