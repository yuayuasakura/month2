<?php
namespace Tests\Feature;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
class StripeWebhookTest extends TestCase
{
    private const SECRET = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.stripe.webhook_secret' => self::SECRET]);
    }

    // Stripe と同じ方式で署名ヘッダーを作る（t=タイムスタンプ,v1=HMAC-SHA256）
    private function sign(string $payload, string $secret = self::SECRET, ?int $time = null): string
    {
        $t = $time ?? time();
        return "t={$t},v1=" . hash_hmac('sha256', "{$t}.{$payload}", $secret);
    }

    private function send(string $payload, ?string $signature)
    {
        $headers = ['CONTENT_TYPE' => 'application/json'];
        if ($signature !== null) $headers['HTTP_STRIPE_SIGNATURE'] = $signature;
        return $this->call('POST', '/api/webhook/stripe', [], [], [], $headers, $payload);
    }

    private function event(string $type): string
    {
        return json_encode(['id'=>'evt_123','object'=>'event','type'=>$type,'data'=>['object'=>['id'=>'pi_test_123','object'=>'payment_intent','amount'=>500,'currency'=>'jpy']]]);
    }

    public function test_payment_intent_succeeded_is_logged(): void
    {
        Log::spy();
        $payload = $this->event('payment_intent.succeeded');
        $this->send($payload, $this->sign($payload))->assertOk();
        Log::shouldHaveReceived('info')->once()->withArgs(fn ($msg, $ctx) => $msg === '決済完了: pi_test_123' && $ctx['payment_intent_id'] === 'pi_test_123' && $ctx['amount'] === 500);
    }

    public function test_other_events_return_200_without_logging(): void
    {
        Log::spy();
        $payload = $this->event('customer.created');
        $this->send($payload, $this->sign($payload))->assertOk();
        Log::shouldNotHaveReceived('info');
    }

    public function test_invalid_requests_are_rejected(): void
    {
        Log::spy();
        $payload = $this->event('payment_intent.succeeded');
        $this->send($payload, null)->assertStatus(400);                                   // 署名ヘッダーなし
        $this->send($payload, $this->sign($payload, 'whsec_wrong'))->assertStatus(400);   // 別のシークレットで署名
        $this->send($payload, $this->sign($payload, self::SECRET, time() - 600))->assertStatus(400); // 古すぎる（再送攻撃）
        $tampered = str_replace('500', '1', $payload);
        $this->send($tampered, $this->sign($payload))->assertStatus(400);                 // 署名後に本文を改ざん
        $this->send('not json', $this->sign('not json'))->assertStatus(400);              // 署名は正しいが JSON でない
        config(['services.stripe.webhook_secret' => null]);
        $this->send($payload, $this->sign($payload))->assertStatus(400);                  // シークレット未設定
        Log::shouldNotHaveReceived('info');
    }
}
