<?php

namespace App\Http\Controllers;

use App\Models\Event;

class EventController extends Controller
{
    // イベント一覧（開催日が近い順）
    public function index()
    {
        $events = Event::orderBy('start_date')->paginate(10);
        return view('events.index', compact('events'));
    }

    // イベント詳細
    public function show(Event $event)
    {
        return view('events.show', compact('event'));
    }
}
