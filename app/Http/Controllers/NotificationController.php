<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    // GET /notifications/unread — dipanggil polling dari navbar tiap ~20 detik
    public function unread()
    {
        $user = Auth::user();

        return response()->json([
            'count' => $user->unreadNotifications()->count(),
            'items' => $user->unreadNotifications()->latest()->take(10)->get()->map(fn ($n) => [
                'id' => $n->id,
                'description' => trim(($n->data['causer_name'] ?? '').' '.($n->data['description'] ?? '-')),
                'created_at_human' => $n->created_at->diffForHumans(),
            ]),
        ]);
    }

    // POST /notifications/mark-all-read
    public function markAllRead(Request $request)
    {
        Auth::user()->unreadNotifications->markAsRead();

        return response()->json(['status' => 'ok']);
    }

    // GET /notifications/{notification}/goto — tandai dibaca lalu arahkan ke sumbernya
    public function goto(string $notification)
    {
        $notif = Auth::user()->notifications()->findOrFail($notification);
        $notif->markAsRead();

        return redirect($notif->data['url'] ?? route('dashboard'));
    }
}