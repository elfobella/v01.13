import axios from 'axios';
window.axios = axios;

// Axios'un base URL'sini ayarla
window.axios.defaults.baseURL = 'http://localhost:8000';

// Axios'u optimize et - Sadece gerekli başlıkları ve özellikleri etkinleştir
axios.defaults.withCredentials = true; // Cookie'leri gönder
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// CSRF token için basit ayar
const token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// Axios timeout değerini artır
axios.defaults.timeout = 30000; // 30 saniye

// İstek önbelleği - aynı GET isteklerinin önbelleklenmesi
const cache = new Map();

// Axios istek önbellekleme - performans için
const axiosGet = axios.get;
axios.get = async function(...args) {
    const cacheKey = JSON.stringify(args);
    
    // Önbellekte varsa ve 5 dakikadan eski değilse önbellekten döndür
    if (cache.has(cacheKey)) {
        const cachedResponse = cache.get(cacheKey);
        const cacheAge = Date.now() - cachedResponse.timestamp;
        
        if (cacheAge < 300000) { // 5 dakika (300000 ms)
            return Promise.resolve(cachedResponse.data);
        }
    }
    
    // Yoksa yeni istek yap
    try {
        const response = await axiosGet.apply(this, args);
        
        // Önbelleğe al
        cache.set(cacheKey, {
            data: response,
            timestamp: Date.now()
        });
        
        return response;
    } catch (error) {
        throw error;
    }
};

// Import Echo and the Pusher library
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Hardcoded Pusher credentials (same as in .env)
const PUSHER_KEY = '805676c1218fb0333ec3';
const PUSHER_CLUSTER = 'eu';

// Debug info
console.log('Pusher Key:', PUSHER_KEY);
console.log('Pusher Cluster:', PUSHER_CLUSTER);

// Echo ayarlarını yap - Doğrudan Pusher servisi kullanılıyor
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: PUSHER_KEY,
    cluster: PUSHER_CLUSTER,
    forceTLS: true,
    authEndpoint: '/broadcasting/auth',  // Göreli URL kullan (aynı origin)
    auth: {
        headers: {
            'X-CSRF-TOKEN': token ? token.content : '',
        },
    },
});
