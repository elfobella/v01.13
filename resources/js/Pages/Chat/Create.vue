<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { defineProps } from 'vue';

const props = defineProps({
    users: Array,
});

const form = useForm({
    user_id: ''
});

const startChat = (userId) => {
    form.user_id = userId;
    form.post(route('chat.create'));
};
</script>

<template>
    <Head title="Yeni Sohbet" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Yeni Sohbet Başlat</h2>
                <Link :href="route('chat.index')" class="text-blue-600 hover:text-blue-800">
                    Sohbetlere Dön
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Sohbet etmek istediğiniz kullanıcıyı seçin</h3>
                        
                        <div v-if="users && users.length > 0" class="divide-y divide-gray-200">
                            <div v-for="user in users" :key="user.id" 
                                class="py-4 flex items-center justify-between hover:bg-gray-50 px-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                                            {{ user.name.substring(0, 2).toUpperCase() }}
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-900">{{ user.name }}</p>
                                        <p class="text-sm text-gray-500">{{ user.email }}</p>
                                    </div>
                                </div>
                                <button
                                    @click="startChat(user.id)"
                                    class="inline-flex items-center px-4 py-2 bg-blue-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-600"
                                    :disabled="form.processing"
                                >
                                    Sohbet Başlat
                                </button>
                            </div>
                        </div>
                        <div v-else class="text-center py-10">
                            <p class="text-gray-500">Henüz hiç kullanıcı yok veya sohbet edilebilecek kullanıcı bulunamadı.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template> 