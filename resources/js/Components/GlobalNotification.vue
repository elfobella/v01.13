<script setup>
import { ref, watchEffect, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    autoHideAfter: {
        type: Number,
        default: 5000 // 5 saniye sonra otomatik gizlenecek
    }
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const notifications = ref([]);
const isVisible = computed(() => notifications.value.length > 0);

// Bildirimi ekle
const addNotification = (notification) => {
    const id = Date.now();
    notifications.value.push({
        id,
        ...notification,
        timestamp: new Date()
    });

    // Otomatik gizle
    if (props.autoHideAfter > 0) {
        setTimeout(() => {
            removeNotification(id);
        }, props.autoHideAfter);
    }
};

// Bildirimi kaldır
const removeNotification = (id) => {
    notifications.value = notifications.value.filter(n => n.id !== id);
};

// Tarih formatlayıcı
const formatTime = (date) => {
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

// Echo kanalını dinle
onMounted(() => {
    if (window.Echo) {
        window.Echo.private(`user.${user.value.id}`)
            .listen('.new.notification', (notificationData) => {
                // Eğer bildirim gönderen kişi bizim aktif sohbetimizin dışındaysa göster
                if (window.activeChatId && notificationData.data && 
                    Number(window.activeChatId) === Number(notificationData.data.chat_id)) {
                    // Eğer aynı sohbetteyse bildirimi gösterme
                    return;
                }
                
                // Bildirimi göster
                addNotification({
                    title: notificationData.related_user ? 
                        `${notificationData.related_user.name} size mesaj gönderdi` : 
                        'Yeni bildirim',
                    message: notificationData.data ? notificationData.data.message : '',
                    chatId: notificationData.data ? notificationData.data.chat_id : null,
                    type: 'message'
                });
            });
    }
});

// Bildirime tıklandığında sohbete git
const navigateToChat = (chatId) => {
    if (chatId) {
        window.location.href = route('chat.show', chatId);
    }
};
</script>

<template>
    <div v-if="isVisible" 
         class="fixed top-20 right-4 z-50 w-80 max-w-[90vw] flex flex-col gap-2">
        <div v-for="notification in notifications" 
             :key="notification.id"
             @click="navigateToChat(notification.chatId)"
             class="bg-white rounded-lg shadow-lg overflow-hidden border border-sky-200 transform transition-all duration-300 hover:scale-105 cursor-pointer animate-slide-in">
            
            <div class="bg-sky-500 px-4 py-2 flex justify-between items-center">
                <h3 class="text-white text-sm font-medium truncate">{{ notification.title }}</h3>
                <button @click.stop="removeNotification(notification.id)" 
                        class="text-sky-100 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="px-4 py-3">
                <p class="text-gray-700 text-sm">{{ notification.message }}</p>
                <div class="text-xs text-gray-500 mt-1 text-right">
                    {{ formatTime(notification.timestamp) }}
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-slide-in {
    animation: slideIn 0.3s ease forwards;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}
</style> 