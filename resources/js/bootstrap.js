import axios from 'axios';
import _ from 'lodash';

window._ = _;

// Aktif sohbet ID'si için global değişken
window.activeChatId = null;

/**
 * Axios HTTP kütüphanesini kurulumu ve yapılandırması
 */
window.axios = axios;

// Dinamik olarak baz URL'yi belirle (production için APP_URL, development için localhost)
const appUrl = import.meta.env.VITE_APP_URL || document.head.querySelector('meta[name="app-url"]')?.content || window.location.origin;
window.axios.defaults.baseURL = appUrl;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

// CSRF token ayarları
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token bulunamadı: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// Axios istek zaman aşımı süresi (ms)
window.axios.defaults.timeout = 30000;

// GET istekleri için önbellek sistemi
const cache = new Map();
const cacheTime = 5 * 60 * 1000; // 5 dakika

window.axios.interceptors.request.use(config => {
    // Sadece GET istekleri için önbellek kullan
    if (config.method === 'get') {
        const url = config.url + JSON.stringify(config.params || {});
        const cachedResponse = cache.get(url);
        
        if (cachedResponse && Date.now() - cachedResponse.timestamp < cacheTime) {
            // Cache'den yanıt döndür ve isteği iptal et
            config.adapter = () => {
                return Promise.resolve({
                    data: cachedResponse.data,
                    status: 200,
                    statusText: 'OK',
                    headers: {},
                    config: config,
                    request: {}
                });
            };
        }
    }
    
    return config;
});

window.axios.interceptors.response.use(response => {
    // GET istekleri için önbelleğe al
    if (response.config.method === 'get') {
        const url = response.config.url + JSON.stringify(response.config.params || {});
        cache.set(url, {
            data: response.data,
            timestamp: Date.now()
        });
    }
    
    return response;
});

/**
 * Echo kurulumu
 */
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Pusher kimlik bilgileri
const pusherAppKey = import.meta.env.VITE_PUSHER_APP_KEY || '805676c1218fb0333ec3';
const pusherAppCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER || 'eu';

// Production ortamında console.log kaldırıldı
if (import.meta.env.MODE !== 'production') {
    console.log('Pusher Kurulumu:', { 
        key: pusherAppKey, 
        cluster: pusherAppCluster 
    });
}

// Echo bağlantısı
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: pusherAppKey,
    cluster: pusherAppCluster,
    forceTLS: true,
    encrypted: true,
    disableStats: true,
});
