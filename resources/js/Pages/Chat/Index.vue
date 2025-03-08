<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { defineProps, ref, onMounted, computed } from 'vue';

const props = defineProps({
    chats: Array,
});

// Sayfa ve kullanıcı bilgilerini al
const page = usePage();
const user = computed(() => page.props.auth.user);

// Echo kanallarını dinle
onMounted(() => {
    // Echo'nun başlatılıp başlatılmadığını kontrol et
    const checkAndListenToEcho = () => {
        if (window.Echo) {
            // Kullanıcıya özel bildirim kanalını dinle
            window.Echo.private(`user.${user.value.id}`)
                .listen('.new.notification', (notification) => {
                    if (notification.type === 'new_message') {
                        // Yeni mesaj bildirimi geldiğinde sayfa yenilenebilir
                        // Veya state güncellenebilir (ideal durumda)
                        console.log('Yeni mesaj bildirimi:', notification);
                    }
                });
        } else {
            // Echo henüz başlatılmamış ise bir süre sonra tekrar dene
            console.log('Echo henüz başlatılmamış, tekrar deneniyor...');
            setTimeout(checkAndListenToEcho, 1000);
        }
    };
    
    // Echo'yu kontrol et ve dinlemeye başla
    checkAndListenToEcho();
});

// Son mesajı formatla
const formatLastMessage = (chat) => {
    if (!chat.latest_message) return 'Henüz mesaj yok';
    return chat.latest_message.content.length > 30 
        ? chat.latest_message.content.substring(0, 30) + '...' 
        : chat.latest_message.content;
};

// Zaman formatla
const formatTime = (date) => {
    if (!date) return '';
    const messageDate = new Date(date);
    const now = new Date();
    
    if (messageDate.toDateString() === now.toDateString()) {
        return messageDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } else {
        return messageDate.toLocaleDateString();
    }
};
</script>

<template>
    <Head title="Sohbetler" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sohbetler</h2>
                <Link :href="route('chat.create.form')" 
                    class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg">
                    Yeni Sohbet
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div v-if="chats && chats.length > 0" class="divide-y divide-gray-200">
                            <Link v-for="chat in chats" :key="chat.id" 
                                  :href="route('chat.show', chat.id)"
                                  class="block hover:bg-gray-50">
                                <div class="flex items-center px-4 py-4 sm:px-6">
                                    <div class="min-w-0 flex-1 flex items-center">
                                        <div class="flex-shrink-0">
                                            <div class="h-12 w-12 rounded-full bg-gray-300 flex items-center justify-center text-gray-600 font-bold">
                                                {{ chat.is_group_chat 
                                                   ? chat.name.substring(0, 2).toUpperCase() 
                                                   : chat.users.find(u => u.id !== user.id).name.substring(0, 2).toUpperCase() }}
                                            </div>
                                        </div>
                                        <div class="min-w-0 flex-1 px-4">
                                            <div>
                                                <p class="text-sm font-medium text-blue-600 truncate">
                                                    {{ chat.is_group_chat 
                                                       ? chat.name 
                                                       : chat.users.find(u => u.id !== user.id).name }}
                                                </p>
                                                <p class="mt-1 text-sm text-gray-500 truncate">
                                                    {{ formatLastMessage(chat) }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex-shrink-0 ml-5">
                                            <div class="text-sm text-gray-500">
                                                {{ chat.latest_message ? formatTime(chat.latest_message.created_at) : '' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </div>
                        <div v-else class="text-center py-10">
                            <p class="text-gray-500">Henüz hiç sohbetiniz yok.</p>
                            <Link :href="route('chat.create.form')" 
                                class="mt-4 inline-block bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg">
                                Yeni Sohbet Başlat
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template> 