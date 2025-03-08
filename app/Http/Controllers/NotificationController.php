<?php

namespace App\Http\Controllers;

use App\Events\NewNotificationEvent;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * Bildirimleri listele
     */
    public function index()
    {
        $notifications = Auth::user()->notifications()
            ->with('relatedUser')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications
        ]);
    }

    /**
     * Bildirimi okundu olarak işaretle
     */
    public function markAsRead(Notification $notification)
    {
        // Kullanıcının kendi bildirimi mi kontrol et
        if ($notification->user_id !== Auth::id()) {
            abort(403, 'Bu bildirimi işaretleme izniniz yok.');
        }
        
        $notification->update(['is_read' => true]);
        
        return response()->json(['success' => true]);
    }

    /**
     * Tüm bildirimleri okundu olarak işaretle
     */
    public function markAllAsRead()
    {
        Auth::user()->notifications()
            ->where('is_read', false)
            ->update(['is_read' => true]);
        
        return response()->json(['success' => true]);
    }

    /**
     * Belirli bir sohbetle ilgili tüm bildirimleri okundu olarak işaretle
     */
    public function markChatNotificationsAsRead(Request $request, $chatId)
    {
        // Kullanıcının okunmamış bildirimlerini bul
        $updatedCount = Auth::user()->notifications()
            ->where('is_read', false)
            ->where('related_type', 'Chat')
            ->where('related_id', $chatId)
            ->update(['is_read' => true]);
        
        return response()->json([
            'success' => true,
            'marked_count' => $updatedCount
        ]);
    }

    /**
     * Yeni bildirim oluştur (API tarafından kullanılır)
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string',
            'data' => 'required',
            'related_user_id' => 'nullable|exists:users,id',
            'related_type' => 'nullable|string',
            'related_id' => 'nullable|integer'
        ]);
        
        $notification = Notification::create([
            'user_id' => $request->user_id,
            'type' => $request->type,
            'data' => $request->data,
            'related_user_id' => $request->related_user_id,
            'related_type' => $request->related_type,
            'related_id' => $request->related_id
        ]);
        
        // İlişkiyi yükle
        $notification->load('relatedUser');
        
        // Event'ı tetikle
        broadcast(new NewNotificationEvent($notification));
        
        return response()->json($notification);
    }

    /**
     * Helper: Chat bildirimlerini oluşturmak için
     */
    public function createChatNotification($userId, $chatId, $messageId, $senderId, $content)
    {
        // Kullanıcı şu anda bu sohbette aktif ise bildirim oluşturma
        if (\App\Http\Controllers\ChatController::isUserActiveInChat($userId, $chatId)) {
            return null;
        }
        
        $notification = Notification::create([
            'user_id' => $userId,
            'type' => 'new_message',
            'data' => [
                'message' => $content,
                'chat_id' => $chatId,
                'message_id' => $messageId
            ],
            'related_user_id' => $senderId,
            'related_type' => 'Chat',
            'related_id' => $chatId
        ]);
        
        // İlişkiyi yükle
        $notification->load('relatedUser');
        
        // Event'ı tetikle
        broadcast(new NewNotificationEvent($notification));
        
        return $notification;
    }
}
