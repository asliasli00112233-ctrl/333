# 🔑 API Key Rotation Sistemi - Kullanım Kılavuzu

## 🎯 Yeni Özellikler

### ✅ Gemini 2.5 Pro Entegrasyonu
- **Model:** `gemini-2.5-pro`
- **Endpoint:** `streamGenerateContent`
- **Timeout:** 90 saniye (gelişmiş içerik üretimi için)

### ✅ Otomatik Key Rotation
- **Akıllı Rotation:** Her sorgudan sonra otomatik olarak bir sonraki key'e geçer
- **Başarısız/Başarılı Fark Etmez:** Tüm sorgular rotation'a devam eder
- **Sürekli Deneme:** Bir key başarısız olursa diğer key'ler denenir
- **İstatistik Takibi:** Her key'in kullanım sayısı otomatik kaydedilir

### ✅ Test Butonları
- Her API key yanında **Test butonu**
- **Gerçek zamanlı test:** Gemini 2.5 Pro endpoint'i kontrol eder
- **Detaylı sonuçlar:**
  - ✅ Başarılı: Model, response time, API yanıtı
  - ❌ Başarısız: Status code, error mesajı, response time
- **10 saniye sonra otomatik kapanır**

## 📋 Kullanım

### 1. API Key Ekleme

```
WordPress Admin → Makale Oluşturucu → API Anahtarları
```

**Form Alanları:**
- **API Anahtarı:** Google AI Studio'dan aldığınız key
- **Sağlayıcı:** Gemini (2.5 Pro)

**API Key Alma:**
1. https://aistudio.google.com/app/apikey
2. "Create API Key" tıklayın
3. Key'i kopyalayın
4. WordPress'e ekleyin

### 2. API Key Test Etme

Her key yanında **Test butonu** var:

1. **Test** butonuna tıklayın
2. Sistem şunu kontrol eder:
   ```
   POST https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-pro:streamGenerateContent?key=YOUR_KEY
   ```
3. Sonuç gösterilir:
   - **Yeşil:** ✅ API key çalışıyor
   - **Kırmızı:** ❌ API key geçersiz veya quota aşıldı

### 3. Otomatik Rotation Nasıl Çalışır?

**Örnek Senaryo:**
```
3 adet API key var: Key1, Key2, Key3
```

**İşleyiş:**
1. İlk makale → **Key1** kullanılır
2. İkinci makale → **Key2** kullanılır
3. Üçüncü makale → **Key3** kullanılır
4. Dördüncü makale → **Key1** kullanılır (döngü)

**Başarısız Durum:**
```
Key1 kullanıldı → Başarısız (rate limit)
Key2 kullanıldı → Başarısız (quota)
Key3 kullanıldı → Başarılı ✅
```

**Önemli:** Sistem otomatik olarak:
- Tüm key'leri sırayla dener
- İlk başarılı key ile devam eder
- **Başarısız key'leri deaktif ETMEZ** (bir süre sonra yeniden aktif olabilirler)
- Rate limit/quota reset olunca başarısız key'ler tekrar kullanılabilir
- Her rotation'da tüm aktif key'ler denenir

## 🔄 API Endpoint Detayları

### Request Format
```json
{
  "contents": [
    {
      "parts": [
        {
          "text": "Your prompt here"
        }
      ]
    }
  ]
}
```

### Response Format
```json
{
  "candidates": [
    {
      "content": {
        "parts": [
          {
            "text": "Generated content"
          }
        ]
      }
    }
  ]
}
```

## 📊 İstatistikler ve Takip

### Her Key İçin Gösterilen Bilgiler

| Sütun | Açıklama |
|-------|----------|
| **Durum** | ✓ Aktif / ✗ Deaktif |
| **API Anahtarı** | İlk 25 + son 5 karakter görünür |
| **Model** | Gemini 2.5 Pro |
| **Kullanım** | Toplam istek sayısı |
| **Son Kullanım** | "X dakika önce" formatında |
| **Oluşturulma** | Key eklenme tarihi |
| **İşlemler** | Test ve Sil butonları |

### Copy to Clipboard
Her key yanında **kopyala butonu** (📋) var:
- Tıklayınca tam key panoya kopyalanır
- 2 saniye yeşil tick (✓) gösterilir
- Bildirim: "API key copied to clipboard!"

## 🎨 Görsel Göstergeler

### Durum Badgeleri
- **✓ Yeşil:** Aktif key
- **✗ Kırmızı:** Deaktif key

### Test Sonuçları
- **🟢 Yeşil Box:** Test başarılı
- **🔴 Kırmızı Box:** Test başarısız
- **🔵 Mavi Box:** Test devam ediyor (loading)

### Model Badge
- **Mavi Badge:** "Gemini 2.5 Pro"

## ⚙️ Ayarlar ve Konfigürasyon

### Timeout Süreleri
```php
// Test için: 30 saniye
'timeout' => 30

// İçerik üretimi için: 90 saniye
'timeout' => 90
```

### Rotation Persistence
```php
// Current key index database'de saklanır
get_option('amo_current_key_index', 0);
```

## 🔧 Teknik Detaylar

### Database Schema
```sql
CREATE TABLE wp_amo_api_keys (
    id int(11) NOT NULL AUTO_INCREMENT,
    api_key varchar(255) NOT NULL,
    provider varchar(50) NOT NULL DEFAULT 'gemini',
    is_active tinyint(1) NOT NULL DEFAULT 1,
    usage_count int(11) NOT NULL DEFAULT 0,
    last_used datetime DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
)
```

### AJAX Actions
```php
// Test API key
wp_ajax_amo_test_api_key

// Generate article (uses rotation)
wp_ajax_amo_generate_article
```

### Key Selection Logic
```php
// Get all active Gemini keys
$api_keys = AMO_Database::get_api_keys('gemini', true);

// Try each key in rotation
foreach ($api_keys as $key_obj) {
    $result = generate_content($key_obj->api_key);
    
    if (!is_wp_error($result)) {
        return $result; // Success!
    }
    
    // Continue to next key...
}
```

## 📝 Örnek Kullanım Senaryoları

### Senaryo 1: Quota Yönetimi
```
Problem: Google AI Studio free tier günlük quota var

Çözüm:
- 5 farklı Google hesabından 5 API key alın
- Hepsini sisteme ekleyin
- Otomatik rotation quota'yı 5 kat artırır
```

### Senaryo 2: Yüksek Hacimli İçerik
```
Hedef: Saatte 50 makale üretmek

Yaklaşım:
- 10 API key ekleyin
- Her key saatte 5 makale = 50 makale/saat
- Sistem otomatik dağıtır
```

### Senaryo 3: Yedekleme
```
Ana Key: Production key (yüksek quota)
Yedek Key: Backup key (düşük quota)

Sistem:
- Ana key başarısız olursa
- Otomatik yedek key'e geçer
- Hiç kesinti olmaz
```

## 🚨 Hata Durumları ve Çözümler

### "No active Gemini API keys found"
**Çözüm:** En az 1 API key ekleyin

### "API key is invalid or quota exceeded"
**Çözüm:**
1. Key'i test edin
2. Google AI Studio'da quota kontrol edin
3. Başka key ekleyin

### "All API keys failed"
**Çözüm:**
1. Tüm key'leri test edin
2. En az 1 çalışan key olmalı
3. Quota durumunu kontrol edin

## 📈 Performans Optimizasyonu

### Best Practices
1. **Çoklu Key:** Minimum 3-5 API key kullanın
2. **Test Önce:** Yeni key ekledikten sonra test edin
3. **İstatistik Takip:** Kullanım sayılarını kontrol edin
4. **Quota İzleme:** Google AI Studio'da daily usage bakın

### Önerilen Yapı
```
Production: 10 API Key
Development: 3 API Key
Testing: 1 API Key
```

## 🔐 Güvenlik

### API Key Güvenliği
- Database'de **plain text** saklanır
- Sadece admin yetkisiyle erişim
- UI'da **maskelenmiş** gösterilir (first 25 + last 5)
- HTTPS kullanın (production)

### Best Practices
```php
// ✅ Doğru: Database'den al
$keys = AMO_Database::get_api_keys();

// ❌ Yanlış: Hard-code etme
define('API_KEY', 'AIzaSy...');
```

## 🎓 İleri Seviye Kullanım

### Custom Key Filtering
```php
// Sadece Gemini key'leri
$gemini_keys = AMO_Database::get_api_keys('gemini', true);

// Tüm key'ler (aktif+deaktif)
$all_keys = AMO_Database::get_api_keys(null, false);
```

### Manual Key Usage Update
```php
// Increment usage
AMO_Database::increment_key_usage($key_id);

// Update status
AMO_Database::update_key_status($key_id, 'active', 'Note');
```

## 📚 Ek Kaynaklar

- [Google AI Studio](https://aistudio.google.com/app/apikey)
- [Gemini API Documentation](https://ai.google.dev/docs)
- [WordPress AJAX Guide](https://developer.wordpress.org/plugins/javascript/ajax/)

## 🆘 Destek

Sorun yaşarsanız:
1. **Debug Logger** eklentisini kontrol edin
2. Browser console'da JavaScript hataları bakın
3. API test butonlarını kullanın
4. Error mesajlarını kaydedin

---

**Not:** Bu sistem production-ready'dir ve büyük ölçekli içerik üretimi için optimize edilmiştir.

## ✨ Özet

✅ Gemini 2.5 Pro kullanımda  
✅ Otomatik key rotation aktif  
✅ Test butonları çalışıyor  
✅ Her sorgudan sonra rotation devam ediyor  
✅ Başarılı/başarısız tüm durumlar loglanıyor  

**Sisteminiz hazır! 🚀**
