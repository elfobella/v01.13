<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';

// Sayfa ve kullanıcı bilgilerini al
const page = usePage();
const user = computed(() => page.props.auth.user);

// Okunmayan bildirimleri takip et
const unreadNotificationsCount = ref(0);

// WebSocket kanallarını dinle
onMounted(() => {
    // Echo'nun başlatılıp başlatılmadığını kontrol et
    const checkAndListenToEcho = () => {
        if (window.Echo) {
            // Kullanıcıya özel bildirim kanalını dinle
            window.Echo.private(`user.${user.value.id}`)
                .listen('.new.notification', (notification) => {
                    unreadNotificationsCount.value++;
                });
                
            // Okunmamış bildirim sayısını API'dan al
            fetchUnreadNotificationsCount();
        } else {
            // Echo henüz başlatılmamış ise bir süre sonra tekrar dene
            console.log('Echo henüz başlatılmamış, tekrar deneniyor...');
            setTimeout(checkAndListenToEcho, 1000);
        }
    };
    
    // Echo'yu kontrol et ve dinlemeye başla
    checkAndListenToEcho();
});

// Okunmamış bildirim sayısını al
const fetchUnreadNotificationsCount = async () => {
    try {
        // API endpoint'i göreli URL ile çağır
        const response = await axios.get('/api/notifications/unread-count');
        console.log('Bildirim yanıtı:', response.data);
        unreadNotificationsCount.value = response.data.count;
    } catch (error) {
        console.error('Bildirim sayısı alınamadı:', error);
        // Hata durumunda bildirim sayısını 0 olarak ayarla
        unreadNotificationsCount.value = 0;
        
        // Yetkilendirme hatası varsa (401), kullanıcı oturumunun geçerli olduğunu kontrol et
        if (error.response && error.response.status === 401) {
            console.warn('Oturum kimlik doğrulama hatası. Bildirim sayısı alınamadı.');
            // 5 saniye sonra tekrar dene
            setTimeout(fetchUnreadNotificationsCount, 5000);
        }
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-gray-900">
                        <div class="mb-6">
                            <p class="text-lg">Hoş geldiniz, {{ user.name }}!</p>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                            <!-- Sohbetler -->
                            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
                                <div class="px-4 py-5 sm:p-6">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 rounded-md bg-blue-500 p-3">
                                            <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <dt class="truncate text-sm font-medium text-gray-500">
                                                Sohbetler
                                            </dt>
                                            <dd class="mt-1 text-3xl font-semibold text-gray-900">
                                                <Link :href="route('chat.index')" class="text-blue-600 hover:text-blue-800">
                                                    Sohbetlerinize Gidin
                                                </Link>
                                            </dd>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-4 py-4 sm:px-6">
                                    <div class="text-sm">
                                        <Link :href="route('chat.index')" class="font-medium text-blue-600 hover:text-blue-500">
                                            Tüm sohbetleri görüntüle
                                        </Link>
                                    </div>
                                </div>
                            </div>

                            <!-- Bildirimler -->
                            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
                                <div class="px-4 py-5 sm:p-6">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 rounded-md bg-purple-500 p-3">
                                            <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                            </svg>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <dt class="truncate text-sm font-medium text-gray-500">
                                                Bildirimler
                                            </dt>
                                            <dd class="mt-1 flex items-baseline">
                                                <div class="flex items-baseline text-2xl font-semibold text-blue-600">
                                                    <span>{{ unreadNotificationsCount }}</span>
                                                    <span class="ml-2 text-sm font-medium text-gray-500">
                                                        Okunmamış bildirim
                                                    </span>
                                                </div>
                                                <div v-if="unreadNotificationsCount > 0" 
                                                    class="ml-2 inline-flex items-baseline rounded-full bg-red-100 px-2.5 py-0.5 text-sm font-medium text-red-800">
                                                    <span>Yeni</span>
                                                </div>
                                            </dd>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-4 py-4 sm:px-6">
                                    <div class="text-sm">
                                        <Link :href="route('notifications.index')" class="font-medium text-blue-600 hover:text-blue-500">
                                            Tüm bildirimleri görüntüle
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
