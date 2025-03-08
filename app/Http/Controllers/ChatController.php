<?php

namespace App\Http\Controllers;

use App\Events\NewMessageEvent;
use App\Http\Controllers\NotificationController;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ChatController extends Controller
{
    /**
     * Sohbet listesini göster
     */
    public function index()
    {
        $chats = Auth::user()->chats()->with(['users', 'latestMessage'])->get();
        
        return Inertia::render('Chat/Index', [
            'chats' => $chats
        ]);
    }

    /**
     * Belirli bir sohbeti göster
     */
    public function show(Chat $chat)
    {
        // Kullanıcının bu sohbete erişimi var mı kontrol et
        if (!$chat->users->contains(Auth::id())) {
            abort(403, 'Bu sohbete erişim izniniz yok.');
        }
        
        // Mesajları yükle
        $messages = $chat->messages()->with('user')->orderBy('created_at', 'asc')->get();
        
        // Sohbet katılımcılarını yükle
        $participants = $chat->users;
        
        return Inertia::render('Chat/Show', [
            'chat' => $chat,
            'messages' => $messages,
            'participants' => $participants
        ]);
    }

    /**
     * Sohbet başlatmak için form göster
     */
    public function createForm()
    {
        // Mevcut kullanıcıyı hariç tut
        $users = User::where('id', '!=', Auth::id())->get();
        
        return Inertia::render('Chat/Create', [
            'users' => $users
        ]);
    }

    /**
     * Yeni bir bireysel sohbet başlat
     */
    public function create(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);
        
        $otherUser = User::findOrFail($request->user_id);
        
        // Kullanıcı kendisiyle sohbet edemez
        if ($otherUser->id === Auth::id()) {
            return response()->json(['message' => 'Kendinizle sohbet edemezsiniz.'], 400);
        }
        
        // Bu kullanıcıyla daha önce bir bireysel sohbet var mı kontrol et
        $existingChat = Auth::user()->chats()
            ->whereHas('users', function($query) use ($otherUser) {
                $query->where('users.id', $otherUser->id);
            })
            ->where('is_group_chat', false)
            ->first();
        
        if ($existingChat) {
            return redirect()->route('chat.show', $existingChat);
        }
        
        // Yeni sohbet oluştur
        $chat = Chat::create([
            'is_group_chat' => false
        ]);
        
        // Kullanıcıları ekle
        $chat->users()->attach([Auth::id(), $otherUser->id]);
        
        return redirect()->route('chat.show', $chat);
    }

    /**
     * Yeni bir grup sohbeti oluştur
     */
    public function createGroup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);
        
        // Kullanıcı kendini eklememişse ekle
        $userIds = $request->user_ids;
        if (!in_array(Auth::id(), $userIds)) {
            $userIds[] = Auth::id();
        }
        
        // Yeni grup oluştur
        $chat = Chat::create([
            'name' => $request->name,
            'is_group_chat' => true
        ]);
        
        // Kullanıcıları ekle
        $chat->users()->attach($userIds);
        
        return redirect()->route('chat.show', $chat);
    }

    /**
     * Sohbete yeni mesaj gönder
     */
    public function sendMessage(Request $request, Chat $chat)
    {
        // Kullanıcının bu sohbete erişimi var mı kontrol et
        if (!$chat->users->contains(Auth::id())) {
            abort(403, 'Bu sohbete erişim izniniz yok.');
        }
        
        $request->validate([
            'content' => 'required|string'
        ]);
        
        // Mesajı oluştur
        $message = $chat->messages()->create([
            'user_id' => Auth::id(),
            'content' => $request->content
        ]);
        
        // İlişkiyi yükle
        $message->load('user');
        
        // Bildirimleri ve broadcasting'i arka planda çalıştır
        dispatch(function() use ($message, $chat) {
            // Event'ı tetikle
            broadcast(new NewMessageEvent($message))->toOthers();
            
            // Sohbet katılımcılarına bildirim gönder
            $notificationController = new NotificationController();
            
            foreach ($chat->users as $user) {
                // Mesajı gönderen kullanıcıya bildirim gönderme
                if ($user->id !== Auth::id()) {
                    $notificationController->createChatNotification(
                        $user->id,
                        $chat->id,
                        $message->id,
                        Auth::id(),
                        $message->content
                    );
                }
            }
        })->afterResponse();
        
        // AJAX veya JSON isteği ise JSON yanıt dön, değilse sohbet sayfasına yönlendir
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($message);
        }
        
        return redirect()->route('chat.show', $chat->id);
    }

    /**
     * Okunmamış mesajları okundu olarak işaretle
     */
    public function markAsRead(Chat $chat)
    {
        // Kullanıcının bu sohbete erişimi var mı kontrol et
        if (!$chat->users->contains(Auth::id())) {
            abort(403, 'Bu sohbete erişim izniniz yok.');
        }
        
        // Bu kullanıcının gönderdiği mesajları hariç tut
        $chat->messages()
            ->where('user_id', '!=', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);
        
        // AJAX veya JSON isteği ise JSON yanıt dön, değilse önceki sayfaya yönlendir
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true]);
        }
        
        return redirect()->back();
    }

    /**
     * Kullanıcının aktif olarak görüntülediği sohbetin durumunu ayarla
     */
    public function setActiveStatus(Request $request)
    {
        $request->validate([
            'chat_id' => 'required|exists:chats,id',
            'is_active' => 'required|boolean'
        ]);
        
        $userId = Auth::id();
        $chatId = $request->chat_id;
        $isActive = $request->is_active;
        
        $cacheKey = "user_{$userId}_active_chat";
        
        if ($isActive) {
            // Kullanıcının aktif sohbetini cache'e kaydet (1 saat geçerli)
            \Cache::put($cacheKey, $chatId, now()->addHour());
        } else {
            // Eğer kullanıcı bu sohbetten çıkıyorsa ve bu onun aktif sohbetiyse, cache'i temizle
            if (\Cache::get($cacheKey) == $chatId) {
                \Cache::forget($cacheKey);
            }
        }
        
        // AJAX veya XHR isteği ise JSON yanıt dön, değilse önceki sayfaya yönlendir
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        
        return redirect()->back();
    }
    
    /**
     * Kullanıcının belirli bir sohbette aktif olup olmadığını kontrol et
     * 
     * @param int $userId Kullanıcı ID
     * @param int $chatId Sohbet ID
     * @return bool
     */
    public static function isUserActiveInChat($userId, $chatId)
    {
        $cacheKey = "user_{$userId}_active_chat";
        $activeChatId = \Cache::get($cacheKey);
        
        return $activeChatId == $chatId;
    }
}
