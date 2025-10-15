# Otomatik Makale Oluşturucu

WordPress için Gemini AI destekli otomatik makale oluşturucu eklentisi. Kullanıcıların belirlediği konular hakkında zengin, görsel içerikli ve profesyonel makaleler oluşturur.

## Özellikler

- **Gemini AI Entegrasyonu**: Google Gemini AI kullanarak kapsamlı ve özgün içerik üretimi
- **Otomatik Görsel Ekleme**: Pexels API ile konuya uygun profesyonel görseller
- **Zengin İçerik Formatı**:
  - Anahtar çıkarımlar
  - Artı/eksi yönler analizi
  - Uzman alıntıları
  - İstatistik ve grafikler (Chart.js)
  - YouTube video entegrasyonu
  - Karşılaştırmalı tablolar
  - FAQ (Sıkça Sorulan Sorular) bölümü
  - Yazar kartı
  - Sosyal medya paylaşım butonları
- **Responsive Tasarım**: Tüm cihazlarda mükemmel görünüm
- **Kolay Kullanım**: Shortcode ile herhangi bir sayfaya eklenebilir

## Kurulum

### 1. Eklentiyi WordPress'e Yükleme

1. `otomatik-makale-olusturucu` klasörünü WordPress sitenizin `/wp-content/plugins/` dizinine yükleyin
2. WordPress yönetim panelinden 'Eklentiler' menüsüne gidin
3. 'Otomatik Makale Oluşturucu' eklentisini aktif edin

### 2. API Anahtarlarını Ayarlama

Eklenti çalışması için iki API anahtarına ihtiyaç duyar:

#### Gemini API Anahtarı

1. [Google AI Studio](https://makersuite.google.com/app/apikey) adresine gidin
2. Google hesabınızla giriş yapın
3. "Create API Key" butonuna tıklayın
4. Oluşturulan API anahtarını kopyalayın

#### Pexels API Anahtarı

1. [Pexels API](https://www.pexels.com/api/) sayfasına gidin
2. Ücretsiz hesap oluşturun veya giriş yapın
3. API anahtarınızı alın

#### API Anahtarlarını Ekleme

1. WordPress yönetim panelinde **Makale Oluşturucu** menüsüne gidin
2. **Gemini API Anahtarı** alanına Google Gemini API anahtarınızı girin
3. **Pexels API Anahtarı** alanına Pexels API anahtarınızı girin
4. **Ayarları Kaydet** butonuna tıklayın

## Kullanım

### Shortcode ile Kullanım

Makale oluşturucuyu herhangi bir sayfada veya yazıda kullanmak için şu shortcode'u ekleyin:

```
[otomatik_makale_olusturucu]
```

### Makale Oluşturma

1. Shortcode'un eklendiği sayfaya gidin
2. Makale konusunu giriş alanına yazın (örn: "Yapay Zeka Etiği")
3. **Oluştur** butonuna tıklayın
4. Sistem otomatik olarak:
   - Konuyla ilgili görsel bulur
   - Gemini AI ile kapsamlı makale içeriği oluşturur
   - Grafik verileri hazırlar
   - Tüm içeriği profesyonel bir şekilde sunar

## Teknik Gereksinimler

- WordPress 5.0 veya üzeri
- PHP 7.4 veya üzeri
- Aktif internet bağlantısı (API çağrıları için)
- jQuery (WordPress ile birlikte gelir)

## Dosya Yapısı

```
otomatik-makale-olusturucu/
├── admin/
│   └── class-amo-admin.php          # Yönetim paneli
├── assets/
│   ├── css/
│   │   ├── admin.css                # Yönetim paneli stilleri
│   │   └── frontend.css             # Ön yüz stilleri
│   └── js/
│       └── frontend.js              # Ön yüz JavaScript
├── includes/
│   ├── class-amo-api-handler.php    # API işlemleri
│   └── class-amo-shortcode.php      # Shortcode yönetimi
├── otomatik-makale-olusturucu.php   # Ana eklenti dosyası
└── README.md                         # Bu dosya
```

## Güvenlik Notları

- API anahtarları WordPress veritabanında güvenli bir şekilde saklanır
- Tüm kullanıcı girişleri sanitize edilir
- AJAX istekleri nonce ile korunur
- API çağrıları WordPress HTTP API kullanılarak yapılır

## Sık Sorulan Sorular

### Eklenti ücretsiz mi?

Eklentinin kendisi ücretsizdir, ancak kullandığı API'ler için:
- **Gemini API**: Ücretsiz kotası bulunmaktadır
- **Pexels API**: Tamamen ücretsizdir

### Oluşturulan makaleler özgün mü?

Evet, Gemini AI her seferinde özgün içerik üretir. Ancak içeriklerin kalitesi ve doğruluğu konuya ve AI modelinin performansına bağlıdır.

### Makale oluşturma ne kadar sürer?

Genellikle 15-30 saniye arasında değişir. Süre API yanıt hızına ve internet bağlantınıza bağlıdır.

### Oluşturulan makaleler düzenlenebilir mi?

Evet, oluşturulan HTML içeriği istediğiniz gibi düzenleyebilir ve özelleştirebilirsiniz.

## Destek

Sorun yaşarsanız veya öneriniz varsa lütfen GitHub üzerinden issue açın.

## Lisans

GPL v2 veya üzeri

## Değişiklik Geçmişi

### Versiyon 1.0.0
- İlk sürüm
- Gemini AI entegrasyonu
- Pexels görsel entegrasyonu
- Zengin makale formatı
- Shortcode desteği
- Yönetim paneli

## Geliştirici Notları

### Filtreleme Hooks

Geliştiriciler için özelleştirme imkanı:

```php
// Prompt'u özelleştirme
add_filter('amo_article_prompt', function($prompt, $topic, $image_url) {
    // Prompt'u özelleştir
    return $prompt;
}, 10, 3);
```

### Action Hooks

```php
// Makale oluşturulduktan sonra
add_action('amo_article_generated', function($article_data, $topic) {
    // İşlemler
}, 10, 2);
```

## Katkıda Bulunma

Katkılarınızı bekliyoruz! Pull request'lerinizi göndermekten çekinmeyin.
