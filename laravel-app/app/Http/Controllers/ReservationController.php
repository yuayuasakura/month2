<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReservationRequest;
use App\Models\Event;
use App\Models\Reservation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    // 予約一覧（新しい予約順）
    public function index()
    {
        // with('event') でイベントをまとめて取得（N+1問題を防ぐ）
        $reservations = Reservation::with('event')->latest()->paginate(20);
        return view('reservations.index', compact('reservations'));
    }

    // 予約フォーム表示
    public function create(Event $event)
    {
        if ($event->isFinished()) {
            return redirect()
                ->route('events.show', $event)
                ->with('error', 'このイベントは終了しているため予約できません');
        }

        return view('reservations.create', compact('event'));
    }

    // 予約の保存
    public function store(ReservationRequest $request, Event $event)
    {
        $data = $request->validated();
        $reservedAt = Carbon::parse($data['reserved_at']);

        // トランザクション＋行ロックで、同時に予約が来ても定員を超えないようにする
        $reservation = DB::transaction(function () use ($event, $data, $reservedAt) {
            Event::whereKey($event->id)->lockForUpdate()->first();

            $remaining = $event->remainingAt($reservedAt);
            if ($data['people'] > $remaining) {
                throw ValidationException::withMessages([
                    'people' => $remaining === 0
                        ? 'この日時は満席です。別の日時を選択してください'
                        : "この日時の残り枠は{$remaining}人です",
                ]);
            }

            return $event->reservations()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'people' => $data['people'],
                'reserved_at' => $reservedAt,
            ]);
        });

        return redirect()
            ->route('reservations.index')
            ->with('success', "予約を受け付けました（予約番号: {$reservation->id}）");
    }

    // 予約のキャンセル（データは削除せず cancelled_at を記録する）
    public function cancel(Reservation $reservation)
    {
        if (! $reservation->isCancellable()) {
            return redirect()
                ->route('reservations.index')
                ->with('error', 'この予約はキャンセルできません');
        }

        // cancelled_at はフォームから送られる値ではないので $fillable に入れず、直接代入して保存する
        $reservation->cancelled_at = now();
        $reservation->save();

        return redirect()
            ->route('reservations.index')
            ->with('success', "予約（予約番号: {$reservation->id}）をキャンセルしました");
    }
}
