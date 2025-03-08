# Modern Chat Uygulaması (v01.13)

Bu proje, Laravel ve Vue.js ile geliştirilmiş modern bir sohbet uygulamasıdır. Gerçek zamanlı mesajlaşma, bildirimler ve iyileştirilmiş kullanıcı deneyimi sunar.

## Özellikler

- Gerçek zamanlı mesajlaşma (Pusher entegrasyonu)
- Optimistik UI güncellemeleri
- Bildirim sistemi
- Grup sohbetleri
- Boosting bilgi paneli
- Responsive tasarım

## Kurulum

```bash
# Depoyu klonlayın
git clone https://github.com/elfobella/v01.13.git
cd v01.13

# Bağımlılıkları yükleyin
composer install
npm install

# .env dosyasını oluşturun
cp .env.example .env
php artisan key:generate

# Veritabanını kurun
php artisan migrate

# Varlıkları derleyin
npm run build

# Sunucuyu başlatın
php artisan serve
```

## Kullanılan Teknolojiler

- Laravel 10
- Vue.js 3 
- Inertia.js
- Tailwind CSS
- Pusher (Gerçek zamanlı iletişim)

## Ekran Görüntüleri

![Chat Arayüzü](https://via.placeholder.com/800x450?text=Chat+Interface)

## Lisans

MIT
