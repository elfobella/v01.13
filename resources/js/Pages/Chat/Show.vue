<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { defineProps, ref, onMounted, nextTick, computed, onUnmounted } from 'vue';

const props = defineProps({
    chat: Object,
    messages: Array,
    participants: Array,
});

// Sayfa ve kullanıcı bilgilerini al
const page = usePage();
const user = computed(() => page.props.auth.user);

const newMessage = ref('');
const messagesList = ref(null);
const messages = ref(props.messages || []);
const loadingMessage = ref(false);

// Boosting service data (örnek veri)
const boostingData = {
    currentRank: {
        name: "Gümüş 2",
        icon: "🥈",
        level: 2,
        maxLevel: 5,
    },
    desiredRank: {
        name: "Altın 3",
        icon: "🥇",
        level: 3,
        maxLevel: 5,
    },
    progress: 35,
    estimatedTime: "2 gün 5 saat",
    price: "350 TL",
};

// Mesaj gönder
const sendMessage = async () => {
    if (!newMessage.value.trim()) return;
    
    // Mesaj içeriğini geçici olarak kaydet
    const messageContent = newMessage.value;
    
    // Kullanıcı arayüzünü hemen güncelleyerek daha iyi bir deneyim sağla
    // Mesaja geçici bir ID ver
    const tempMessageId = 'temp-' + Date.now();
    const tempMessage = {
        id: tempMessageId,
        chat_id: props.chat.id,
        user_id: user.value.id,
        content: messageContent,
        created_at: new Date().toISOString(),
        is_temporary: true,
        user: {
            id: user.value.id,
            name: user.value.name
        }
    };
    
    // Mesajı önce UI'da göster (iyimser güncelleme)
    messages.value.push(tempMessage);
    newMessage.value = '';
    
    // Mesaj listesini en alta kaydır
    nextTick(() => {
        if (messagesList.value) {
            messagesList.value.scrollTop = messagesList.value.scrollHeight;
        }
    });
    
    // Arka planda gerçek isteği gönder
    loadingMessage.value = true;
    
    try {
        const response = await axios.post(route('chat.messages.send', props.chat.id), {
            content: messageContent
        });
        
        // Sunucudan gelen gerçek mesajla geçici mesajı değiştir
        const realMessage = response.data;
        const tempIndex = messages.value.findIndex(m => m.id === tempMessageId);
        
        if (tempIndex !== -1) {
            messages.value[tempIndex] = realMessage;
        }
        
    } catch (error) {
        console.error('Mesaj gönderilemedi:', error);
        
        // Hata durumunda geçici mesajı kaldır
        messages.value = messages.value.filter(m => m.id !== tempMessageId);
        
        // Kullanıcıya hata mesajı göster
        alert('Mesaj gönderilemedi. Lütfen tekrar deneyin.');
        
        // Mesaj içeriğini geri yükle
        newMessage.value = messageContent;
    } finally {
        loadingMessage.value = false;
    }
};

// Chat kanalını dinle
onMounted(() => {
    // Mesaj listesini en alta kaydır
    nextTick(() => {
        if (messagesList.value) {
            messagesList.value.scrollTop = messagesList.value.scrollHeight;
        }
    });
    
    // Bu sohbetle ilgili bildirimleri okundu olarak işaretle
    markChatNotificationsAsRead();
    
    // Kullanıcının aktif olarak bu sohbette olduğunu belirt
    setActiveChatStatus(true);
    
    // Echo'nun başlatılıp başlatılmadığını kontrol et
    const checkAndListenToEcho = () => {
        if (window.Echo) {
            console.log(`Attempting to listen to chat.${props.chat.id} channel`);
            
            try {
                // Chat kanalını dinle
                window.Echo.private(`chat.${props.chat.id}`)
                    .listen('.new.message', (data) => {
                        console.log('New message received via Pusher:', data);
                        
                        // Yeni mesaj geldiğinde ekle
                        messages.value.push({
                            id: data.id,
                            chat_id: data.chat_id,
                            user_id: data.user_id,
                            content: data.content,
                            created_at: data.created_at,
                            user: {
                                id: data.user_id,
                                name: data.user_name
                            }
                        });
                        
                        // Mesaj listesini aşağı kaydır
                        nextTick(() => {
                            if (messagesList.value) {
                                messagesList.value.scrollTop = messagesList.value.scrollHeight;
                            }
                        });
                    });
                
                console.log('Successfully subscribed to channel');
            } catch (error) {
                console.error('Error subscribing to channel:', error);
            }
        } else {
            // Echo henüz başlatılmamış ise bir süre sonra tekrar dene
            console.log('Echo henüz başlatılmamış, tekrar deneniyor...');
            setTimeout(checkAndListenToEcho, 1000);
        }
    };
    
    // Echo'yu kontrol et ve dinlemeye başla
    checkAndListenToEcho();
    
    // Sayfa kapatıldığında veya bileşen kaldırıldığında aktif sohbet durumunu temizle
    onUnmounted(() => {
        setActiveChatStatus(false);
    });
});

// Kullanıcının aktif sohbet durumunu ayarla
const setActiveChatStatus = async (isActive) => {
    try {
        await axios.post(route('chat.active.status'), {
            chat_id: props.chat.id,
            is_active: isActive
        });
        console.log(`Aktif sohbet durumu ${isActive ? 'ayarlandı' : 'temizlendi'}`);
    } catch (error) {
        console.error('Aktif sohbet durumu ayarlanamadı:', error);
    }
};

// Bileşen kaldırıldığında aktif sohbet durumunu temizle
onUnmounted(() => {
    setActiveChatStatus(false);
});

// Bu sohbetle ilgili bildirimleri okundu olarak işaretle
const markChatNotificationsAsRead = async () => {
    try {
        const response = await axios.post(route('notifications.chat.read', props.chat.id));
        console.log('Chat bildirimleri okundu olarak işaretlendi:', response.data.marked_count);
    } catch (error) {
        console.error('Chat bildirimleri işaretlenemedi:', error);
    }
};

// Formatlanmış tarih
const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

// Sohbet başlığını belirle
const chatTitle = () => {
    if (props.chat.is_group_chat) {
        return props.chat.name;
    } else {
        const otherUser = props.participants.find(p => p.id !== user.value.id);
        return otherUser ? otherUser.name : 'Sohbet';
    }
};

// İlerleme çubuğu değeri
const progressValue = computed(() => `${boostingData.progress}%`);

// Yıldız gösterme helper fonksiyonu
const getStars = (level, maxLevel, colorClass) => {
    const stars = [];
    for (let i = 0; i < maxLevel; i++) {
        stars.push(i < level);
    }
    return stars;
};

// Avatar fallback oluşturma
const getAvatarFallback = (name) => {
    return name ? name.substring(0, 2).toUpperCase() : 'NA';
};
</script>

<template>
    <Head :title="chatTitle()" />
    
    <div class="flex h-screen w-full flex-col bg-gradient-to-br from-gray-50 via-white to-sky-50 p-4 md:p-6">
        <div class="mx-auto w-full max-w-6xl flex flex-col md:flex-row gap-4 h-full">
            <!-- Main Chat Area -->
            <div class="rounded-xl bg-white/80 backdrop-blur-md border border-sky-200 shadow-xl overflow-hidden flex flex-col flex-grow">
                <!-- Header -->
                <div class="p-4 border-b border-sky-100 bg-sky-100/50 backdrop-blur-md flex items-center">
                    <div class="h-10 w-10 mr-3 rounded-full bg-sky-500 text-white flex items-center justify-center overflow-hidden">
                        <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(chatTitle())}&color=FFFFFF&background=38BDF8`" 
                            alt="Avatar" class="h-full w-full object-cover">
                    </div>
                    <div>
                        <h2 class="font-semibold text-sky-900">{{ chatTitle() }}</h2>
                        <p class="text-xs text-sky-500">Çevrimiçi</p>
                    </div>
                    <Link :href="route('chat.index')" class="ml-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-sky-400 hover:text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </Link>
                </div>

                <!-- Messages -->
                <div ref="messagesList" class="flex-1 overflow-y-auto p-4 space-y-4 bg-white">
                    <div v-for="message in messages" :key="message.id" 
                        class="flex" 
                        :class="message.user_id === user.id ? 'justify-end' : 'justify-start'">
                        
                        <div v-if="message.user_id !== user.id" class="h-8 w-8 mr-2 mt-1 flex-shrink-0 rounded-full bg-sky-500 text-white flex items-center justify-center overflow-hidden">
                            <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(message.user.name)}&color=FFFFFF&background=38BDF8`" 
                                alt="Avatar" class="h-full w-full object-cover">
                        </div>
                        
                        <div class="max-w-[80%] rounded-2xl px-4 py-2"
                            :class="[
                                message.user_id === user.id 
                                    ? 'bg-sky-500 text-white border border-sky-400/30' 
                                    : 'bg-gray-100 text-gray-800 border border-gray-200',
                                message.is_temporary ? 'opacity-70' : ''
                            ]">
                            
                            <div class="flex items-center">
                                <p>{{ message.content }}</p>
                                <span v-if="message.is_temporary" class="ml-2 inline-block animate-spin text-xs">⌛</span>
                            </div>
                            
                            <div class="text-xs text-right mt-1" 
                                :class="message.user_id === user.id ? 'text-sky-100' : 'text-gray-500'">
                                {{ formatDate(message.created_at) }}
                            </div>
                        </div>
                        
                        <div v-if="message.user_id === user.id" class="h-8 w-8 ml-2 mt-1 flex-shrink-0 rounded-full bg-sky-500 text-white flex items-center justify-center overflow-hidden">
                            <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&color=FFFFFF&background=38BDF8`" 
                                alt="Avatar" class="h-full w-full object-cover">
                        </div>
                    </div>
                </div>

                <!-- Input area -->
                <div class="p-3 border-t border-sky-100 bg-white">
                    <div class="flex items-center gap-2">
                        <button class="rounded-full bg-gray-100 hover:bg-gray-200 text-sky-500 p-2 w-10 h-10 flex items-center justify-center transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                            </svg>
                            <span class="sr-only">Dosya ekle</span>
                        </button>
                        <button class="rounded-full bg-gray-100 hover:bg-gray-200 text-sky-500 p-2 w-10 h-10 flex items-center justify-center transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                            <span class="sr-only">Resim ekle</span>
                        </button>
                        <input
                            v-model="newMessage"
                            type="text"
                            placeholder="Mesajınızı yazın..."
                            class="flex-1 bg-gray-100 border-0 text-gray-800 placeholder:text-gray-400 focus:ring-sky-400 h-10 rounded-lg px-3"
                            :disabled="loadingMessage"
                            @keydown.enter="sendMessage"
                        >
                        <button class="rounded-full bg-gray-100 hover:bg-gray-200 text-sky-500 p-2 w-10 h-10 flex items-center justify-center transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 18.5A6.5 6.5 0 0 1 5.5 12"></path>
                                <path d="M12 18.5A6.5 6.5 0 0 0 18.5 12"></path>
                                <path d="M12 18.5V21"></path>
                                <path d="M8 14l-4 4 4 4"></path>
                                <path d="M20 14l4 4-4 4"></path>
                                <circle cx="12" cy="6" r="3"></circle>
                            </svg>
                            <span class="sr-only">Ses kaydı</span>
                        </button>
                        <button 
                            @click="sendMessage" 
                            class="rounded-full bg-sky-500 hover:bg-sky-600 text-white p-2 w-10 h-10 flex items-center justify-center transition-colors"
                            :disabled="loadingMessage || !newMessage.trim()"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                            </svg>
                            <span class="sr-only">Gönder</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Rank Information Sidebar -->
            <div class="md:w-80 rounded-xl bg-white/80 backdrop-blur-md border border-sky-200 shadow-xl overflow-hidden flex flex-col">
                <div class="p-4 border-b border-sky-100 bg-sky-100/50 backdrop-blur-md">
                    <h2 class="font-semibold text-sky-900 text-center">Chat Bilgileri</h2>
                </div>

                <div class="p-4 space-y-6">
                    <!-- Current Rank -->
                    <div class="space-y-2">
                        <h3 class="text-sky-700 font-medium text-sm">Güncel Seviye</h3>
                        <div class="bg-gray-50 backdrop-blur-sm border border-sky-100 rounded-lg p-3 flex items-center">
                            <div class="w-12 h-12 flex items-center justify-center bg-sky-100 rounded-full mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-sky-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path>
                                    <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path>
                                    <path d="M4 22h16"></path>
                                    <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"></path>
                                    <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"></path>
                                    <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sky-900 font-semibold">{{ boostingData.currentRank.name }}</div>
                                <div class="flex mt-1">
                                    <svg v-for="(isActive, index) in getStars(boostingData.currentRank.level, boostingData.currentRank.maxLevel)" 
                                        :key="index"
                                        class="h-4 w-4" 
                                        :class="isActive ? 'text-sky-500 fill-sky-500' : 'text-gray-300'"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Desired Rank -->
                    <div class="space-y-2">
                        <h3 class="text-sky-700 font-medium text-sm">Hedef Seviye</h3>
                        <div class="bg-gray-50 backdrop-blur-sm border border-sky-100 rounded-lg p-3 flex items-center">
                            <div class="w-12 h-12 flex items-center justify-center bg-amber-100 rounded-full mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path>
                                    <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path>
                                    <path d="M4 22h16"></path>
                                    <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"></path>
                                    <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"></path>
                                    <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sky-900 font-semibold">{{ boostingData.desiredRank.name }}</div>
                                <div class="flex mt-1">
                                    <svg v-for="(isActive, index) in getStars(boostingData.desiredRank.level, boostingData.desiredRank.maxLevel)" 
                                        :key="index"
                                        class="h-4 w-4" 
                                        :class="isActive ? 'text-amber-500 fill-amber-500' : 'text-gray-300'"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <h3 class="text-sky-700 font-medium text-sm">İlerleme</h3>
                            <span class="text-sky-900 text-sm font-medium">{{ boostingData.progress }}%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="bg-sky-500 h-2 rounded-full" :style="{ width: progressValue }"></div>
                        </div>
                    </div>

                    <!-- Additional Info -->
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sky-700 text-sm">Tahmini Süre:</span>
                            <span class="text-sky-900 text-sm">{{ boostingData.estimatedTime }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sky-700 text-sm">Fiyat:</span>
                            <span class="text-sky-900 text-sm font-semibold">{{ boostingData.price }}</span>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <button class="w-full bg-sky-500 hover:bg-sky-600 text-white py-2 px-4 rounded-lg font-medium transition-colors">
                        Boosting Satın Al
                    </button>
                    
                    <!-- Participants Section -->
                    <div class="space-y-2 pt-4 border-t border-sky-100">
                        <h3 class="text-sky-700 font-medium text-sm">Katılımcılar</h3>
                        <div class="space-y-2">
                            <div v-for="participant in participants" :key="participant.id"
                                class="bg-gray-50 border border-sky-100 rounded-lg p-2 flex items-center">
                                <div class="w-8 h-8 rounded-full bg-sky-500 flex items-center justify-center text-white mr-2 overflow-hidden">
                                    <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(participant.name)}&color=FFFFFF&background=38BDF8`" 
                                        alt="Avatar" class="h-full w-full object-cover">
                                </div>
                                <div class="text-sky-900">{{ participant.name }}</div>
                                <div v-if="participant.id === user.id" class="ml-auto text-xs text-sky-500">(Sen)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
/* Gradient ve backdrop blur için gerekli stiller */
.backdrop-blur-md {
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

.backdrop-blur-sm {
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

/* Animasyon için */
.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Geçiş animasyonları */
.transition-colors {
    transition-property: background-color, border-color, color, fill, stroke;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

/* İlerleme çubuğu için */
.rounded-full {
    border-radius: 9999px;
}
</style> 