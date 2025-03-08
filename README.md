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

## Production Ortamına Deployment

### Laravel Forge ve DigitalOcean ile Deployment

#### Ön Gereksinimler
- Laravel Forge hesabı
- DigitalOcean hesabı
- Pusher hesabı (gerçek zamanlı özellikler için)

#### Adımlar

1. **Laravel Forge'da Sunucu Oluşturma:**
   - Laravel Forge'da yeni bir sunucu oluşturun
   - DigitalOcean sağlayıcısını seçin
   - Basic paket ($12/ay) seçilebilir
   - PHP 8.2 seçin, MySQL 8.0 veya MariaDB 10.5 ekleyin

2. **Yeni Site Ekleme:**
   - Sunucu oluşturulduktan sonra, "New Site" butonuna tıklayın
   - Domain adını girin veya IP adresini kullanın
   - Laravel projesini seçin

3. **GitHub Repository Bağlama:**
   - GitHub hesabınıza bağlanın
   - elfobella/v01.13 repository'sini seçin
   - Ana branch'i seçin (ör. main)

4. **Deployment Script Düzenleme:**
   - Bu projede hazır bulunan forge-deploy.sh içeriğini Forge'daki deployment script alanına kopyalayın
   - "Deploy Script" butonuna tıklayarak kaydedin

5. **Environment (.env) Ayarları:**
   - Forge'da .env dosyasını düzenleyin
   - Bu projede bulunan .env.example dosyasını baz alın
   - APP_URL, DB_PASSWORD, PUSHER bilgilerini doğru bir şekilde ayarlayın

6. **SSL Sertifikası Ekleme:**
   - Let's Encrypt kullanarak bir SSL sertifikası oluşturun
   - Sertifikayı aktifleştirin

7. **Queue Worker Ekleme:**
   - Bildirimler ve arka plan işleri için queue worker ekleyin
   - Connection olarak "redis" seçin
   - Queue olarak "default" girin
   - Processes olarak 2 girin

8. **İlk Deployment:**
   - "Deploy" butonuna tıklayarak ilk deployment'ı başlatın
   - Deployment loglarını kontrol edin

### Komut Satırı ile Manuel Deployment

Production ortamında manuel deployment için aşağıdaki adımları uygulayın:

```bash
# Production ortamı için bağımlılıkları yükleyin
composer install --no-dev --optimize-autoloader

# .env.example dosyasını .env olarak kopyalayın ve düzenleyin
cp .env.example .env

# Uygulama anahtarı oluşturun
php artisan key:generate

# Veritabanı tablolarını oluşturun
php artisan migrate --force

# Varlıkları production için derleyin
npm ci && npm run build-production

# Önbelleği temizleyin ve önbelleğe alın
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Queue worker'ı başlatın (supervisor ile kullanmanız önerilir)
php artisan queue:work redis --tries=3
```

## Kullanılan Teknolojiler

- Laravel 10
- Vue.js 3 
- Inertia.js
- Tailwind CSS
- Pusher (Gerçek zamanlı iletişim)
- Redis (Queue, Cache)

## Ekran Görüntüleri

![Chat Arayüzü](https://via.placeholder.com/800x450?text=Chat+Interface)

## Lisans

MIT
