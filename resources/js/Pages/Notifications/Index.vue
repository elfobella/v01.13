<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { defineProps, ref, onMounted, computed } from 'vue';

const props = defineProps({
    notifications: Object,
});

// Sayfa ve kullanıcı bilgilerini al
const page = usePage();
const user = computed(() => page.props.auth.user);

// Bildirimleri dinle
onMounted(() => {
    // Kullanıcıya özel bildirim kanalını dinle
    window.Echo.private(`user.${user.value.id}`)
        .listen('.new.notification', (notification) => {
            // Sayfa yenileme (ideal durumda state güncellemesi yapılabilir)
            window.location.reload();
        });
});

// Bildirimleri okundu olarak işaretle
const markAsRead = async (notification) => {
    try {
        await axios.post(route('notifications.read', notification.id));
        notification.is_read = true;
    } catch (error) {
        console.error('Bildirim işaretlenemedi:', error);
    }
};

// Tüm bildirimleri okundu olarak işaretle
const markAllAsRead = async () => {
    try {
        await axios.post(route('notifications.read-all'));
        props.notifications.data.forEach(notification => {
            notification.is_read = true;
        });
    } catch (error) {
        console.error('Bildirimler işaretlenemedi:', error);
    }
};

// Zaman formatla
const formatTime = (date) => {
    if (!date) return '';
    const messageDate = new Date(date);
    const now = new Date();
    
    // 24 saatten az ise saat göster
    const diff = now - messageDate;
    if (diff < 24 * 60 * 60 * 1000) {
        return messageDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } else if (diff < 7 * 24 * 60 * 60 * 1000) {
        // 1 haftadan az ise gün adını göster
        const days = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
        return days[messageDate.getDay()];
    } else {
        // Diğer durumlar için tarih göster
        return messageDate.toLocaleDateString();
    }
};

// Bildirim içeriğini formatla
const formatNotification = (notification) => {
    if (notification.type === 'new_message') {
        return {
            title: `${notification.related_user?.name || 'Birisi'} size mesaj gönderdi`,
            content: notification.data.message.length > 30 
                ? notification.data.message.substring(0, 30) + '...' 
                : notification.data.message,
            link: route('chat.show', notification.data.chat_id),
            icon: 'message'
        };
    }
    
    // Default
    return {
        title: 'Yeni bildirim',
        content: 'Bildirim detayı',
        link: null,
        icon: 'bell'
    };
};

// Bildirim ikonu
const getIcon = (type) => {
    if (type === 'message') {
        return `<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>`;
    }
    
    // Default bell icon
    return `<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>`;
};
</script>

<template>
    <Head title="Bildirimler" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Bildirimler</h2>
                <button 
                    v-if="notifications.data.some(n => !n.is_read)"
                    @click="markAllAsRead" 
                    class="text-sm text-blue-600 hover:text-blue-800">
                    Tümünü Okundu İşaretle
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div v-if="notifications.data && notifications.data.length > 0" class="divide-y divide-gray-200">
                            <div v-for="notification in notifications.data" :key="notification.id" 
                                 class="py-4 flex items-start"
                                 :class="{'bg-blue-50': !notification.is_read}">
                                
                                <div class="flex-shrink-0 mr-4">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-500" v-html="getIcon(formatNotification(notification).icon)">
                                    </div>
                                </div>
                                
                                <div class="flex-1 min-w-0">
                                    <Link v-if="formatNotification(notification).link" 
                                         :href="formatNotification(notification).link"
                                         class="block">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ formatNotification(notification).title }}
                                        </p>
                                        <p class="text-sm text-gray-500 truncate">
                                            {{ formatNotification(notification).content }}
                                        </p>
                                        <p class="text-xs text-gray-400 mt-1">
                                            {{ formatTime(notification.created_at) }}
                                        </p>
                                    </Link>
                                    <div v-else>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ formatNotification(notification).title }}
                                        </p>
                                        <p class="text-sm text-gray-500 truncate">
                                            {{ formatNotification(notification).content }}
                                        </p>
                                        <p class="text-xs text-gray-400 mt-1">
                                            {{ formatTime(notification.created_at) }}
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="flex-shrink-0 ml-4" v-if="!notification.is_read">
                                    <button @click="markAsRead(notification)" class="text-xs text-blue-600 hover:text-blue-800">
                                        Okundu İşaretle
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-10">
                            <p class="text-gray-500">Henüz hiç bildiriminiz yok.</p>
                        </div>
                        
                        <!-- Pagination -->
                        <div v-if="notifications.data && notifications.data.length > 0" class="mt-4 flex justify-center">
                            <div class="flex space-x-2">
                                <Link v-if="notifications.prev_page_url" 
                                     :href="notifications.prev_page_url"
                                     class="px-4 py-2 border rounded text-sm">
                                    Önceki
                                </Link>
                                <span class="px-4 py-2 text-sm">
                                    Sayfa {{ notifications.current_page }} / {{ notifications.last_page }}
                                </span>
                                <Link v-if="notifications.next_page_url"
                                     :href="notifications.next_page_url"
                                     class="px-4 py-2 border rounded text-sm">
                                    Sonraki
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template> 