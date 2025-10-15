# 🔧 Troubleshooting Guide - Otomatik Makale Oluşturucu

## 🔴 Yaygın Hatalar ve Çözümleri

### 1. "You exceeded your current quota" Hatası

**Sorun:** API key'inizin ücretsiz kotası dolmuş.

**Çözüm:**
```
1. Google AI Studio → https://aistudio.google.com/app/apikey
2. Yeni bir API key oluşturun (farklı Google hesabından)
3. WordPress → API Anahtarları → Yeni key ekleyin
4. Eski key'i silin veya Test edin (otomatik deaktif olacak)
```

**Kota Bilgileri (Free Tier):**
- 15 istek/dakika
- 1M token/dakika
- 1,500 istek/gün

### 2. "API key not valid" Hatası

**Sorun:** API key geçersiz veya yanlış kopyalanmış.

**Çözüm:**
```
1. Key'i tamamen silin ve yeniden kopyalayın
2. Başında/sonunda boşluk olmamasına dikkat edin
3. Tam key'i kopyaladığınızdan emin olun (39 karakter)
4. Format: AIzaSy... ile başlamalı
```

### 3. "cURL error 28: Operation timed out" Hatası

**Sorun:** İstek 60 saniyede tamamlanamadı.

**Çözüm:**
```
1. İnternet bağlantınızı kontrol edin
2. Google API'ye erişim var mı test edin
3. Firewall/antivirus ayarlarını kontrol edin
4. Farklı bir API key deneyin
```

### 4. "Maximum execution time exceeded" Hatası

**Sorun:** PHP script 300 saniyeyi aştı.

**Çözüm:**
```
1. Tüm geçersiz key'leri silin
2. En az 1 çalışan key olduğundan emin olun
3. Key'leri tek tek test edin
```

### 5. Tüm Key'ler Başarısız Oluyor

**Sorun:** Hiçbir key çalışmıyor.

**Çözüm:**
```
1. WordPress → API Anahtarları
2. Her key için "Test" butonuna basın
3. ✅ Yeşil (başarılı) görenler çalışıyor
4. ❌ Kırmızı (başarısız) görenler quota/invalid
5. En az 1 çalışan key olmalı
```

## 🔄 Akıllı Key Rotation (Yeni!)

Sistem artık akıllı rotation yapıyor:

### ✅ Başarısız Key'leri Deaktif ETMEZ
```
"exceeded your current quota" → Diğer key'e geç, bu key'i aktif tut
"API key not valid" → Diğer key'e geç, bu key'i aktif tut
"timeout" → Diğer key'e geç, bu key'i aktif tut
```

**Neden?** Key'ler geçici olarak başarısız olabilir:
- Rate limit 1 dakika sonra sıfırlanır
- Quota gece yarısı reset olur
- Network timeout geçici olabilir

### ✅ Sürekli Deneme
```
Makale 1: Key1 (başarısız) → Key2 (başarısız) → Key3 (başarılı) ✅
Makale 2: Key1 (başarılı) ✅  // Key1 tekrar denenecek!
```

### ✅ Timeout'ları Azalttı
```
Eski: 120 saniye → Yeni: 60 saniye
Daha hızlı hata tespiti
```

### ✅ Max Execution Time Kontrolü
```
Script: 300 saniye max
Her key denemesi: 60 saniye max
```

## 📊 Key Durumları

| Durum | İkon | Açıklama | Rotation Davranışı |
|-------|------|----------|-------------------|
| **Aktif** | ✓ | Çalışıyor | Kullanılır |
| **Aktif (Geçici Başarısız)** | ✓ | Rate limit/quota geçici | Atlanır, sonra tekrar denenir |
| **Deaktif (Manuel)** | ✗ | Kullanıcı deaktif etti | Hiç kullanılmaz |

**Önemli:** Sistem key'leri otomatik deaktif ETMEZ. Tüm aktif key'ler her rotation'da denenir.

## 🎯 Hızlı Düzeltme Adımları

### Senaryo 1: Makale Üretilmiyor

```bash
1. API Anahtarları sayfasına git
2. Tüm key'leri test et
3. En az 1 ✅ yeşil olmalı
4. Yoksa yeni key ekle
5. "Şimdi Makale Üret" butonuna bas
```

### Senaryo 2: Bazı Key'ler Çalışıyor, Bazıları Çalışmıyor

```bash
# Normal! Sistem otomatik rotation yapıyor
1. Sistem tüm key'leri sırayla dener
2. Başarısız key'ler atlanır
3. Başarılı key bulunca makale oluşturulur
4. Bir sonraki makale için tüm key'ler tekrar denenir

Örnek:
Makale 1: Key1(başarısız) → Key2(başarılı) ✅
Makale 2: Key1(başarılı) ✅  // Key1 tekrar denendi!
Makale 3: Key1(başarısız) → Key2(başarısız) → Key3(başarılı) ✅
```

### Senaryo 3: Tüm Key'ler Sürekli Başarısız Oluyor

```bash
# Olası sebepler:
1. Tüm key'lerin quota'sı dolmuş (gece yarısı reset)
2. Tüm key'ler geçersiz (yanlış kopyalanmış)
3. Network sorunu (firewall/antivirus)

Çözüm:
1. Birkaç saat bekleyin (quota reset için)
2. Yeni key ekleyin (farklı Google hesabından)
3. Mevcut key'leri test edin
4. En az 1 çalışan key bulun
```

## 🔍 Debug Modu

### Error Log Kontrolü

WordPress error log'u kontrol edin:

```
Konum: wp-content/debug.log
```

**Örnek Log Mesajları:**

```log
# Quota aşımı (geçici, bir sonraki rotation'da tekrar denenir)
AMO: Key failed, trying next: AIzaSyDuMk... - Error: You exceeded your current quota

# Geçersiz key (geçici, bir sonraki rotation'da tekrar denenir)
AMO: Key failed, trying next: AIzaSyAe3U... - Error: API key not valid

# Timeout (geçici, bir sonraki rotation'da tekrar denenir)
AMO: Key failed, trying next: AIzaSyCRmt... - Error: cURL error 28: Operation timed out

# Başarılı
Article published successfully with key: AIzaSyBVVD...
```

**Önemli:** "Key failed, trying next" mesajı NORMAL'dir. Sistem diğer key'e geçiyor.

### Manuel Test

WordPress Admin → Appearance → Theme File Editor → functions.php

Geçici test kodu ekleyin:

```php
add_action('admin_init', function() {
    if (isset($_GET['test_amo_keys'])) {
        $keys = AMO_Database::get_api_keys('gemini', true);
        
        echo '<h2>AMO API Keys Test</h2>';
        echo '<table border="1" style="border-collapse: collapse; width: 100%;">';
        echo '<tr><th>Key</th><th>Status</th><th>Message</th></tr>';
        
        foreach ($keys as $key) {
            $response = wp_remote_post(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-pro:generateContent?key=' . $key->api_key,
                array(
                    'headers' => array('Content-Type' => 'application/json'),
                    'body' => json_encode(array(
                        'contents' => array(array('parts' => array(array('text' => 'Test'))))
                    )),
                    'timeout' => 30
                )
            );
            
            $status_code = wp_remote_retrieve_response_code($response);
            $body = json_decode(wp_remote_retrieve_body($response), true);
            
            $status = $status_code === 200 ? '✅ Working' : '❌ Failed';
            $message = $status_code === 200 ? 'OK' : ($body['error']['message'] ?? 'Unknown error');
            
            echo '<tr>';
            echo '<td>' . substr($key->api_key, 0, 15) . '...</td>';
            echo '<td>' . $status . '</td>';
            echo '<td>' . esc_html($message) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        die();
    }
});
```

Sonra ziyaret edin:
```
http://localhost/wp/wp-admin/?test_amo_keys=1
```

## 🛠️ Manuel Key Cleanup

Eğer çok fazla deaktif key varsa:

**SQL ile temizleme:**
```sql
-- WordPress phpMyAdmin → wp_amo_api_keys

-- Sadece deaktif key'leri göster
SELECT id, LEFT(api_key, 15) as key_preview, is_active, last_used 
FROM wp_amo_api_keys 
WHERE is_active = 0;

-- Deaktif key'leri sil
DELETE FROM wp_amo_api_keys WHERE is_active = 0;

-- Tüm key'leri aktif et (dikkatli!)
UPDATE wp_amo_api_keys SET is_active = 1;
```

## 📈 Performans İyileştirme

### Önerilen Key Sayısı

| Kullanım | Önerilen Key | Neden |
|----------|--------------|-------|
| **Test** | 1-2 | Yeterli |
| **Düşük Hacim** | 3-5 | Güvenli |
| **Yüksek Hacim** | 10+ | Quota dağıtımı |

### Quota Yönetimi

```
# Her key için günlük kota: 1,500 istek
# 10 key × 1,500 = 15,000 istek/gün

# Her makale: ~1 istek
# 10 key ile: 15,000 makale/gün teorik
```

### Rate Limiting

```
Free Tier: 15 istek/dakika/key
10 key: 150 istek/dakika toplam

Saatte makale: 150 × 60 = 9,000 (teorik)
Gerçek: ~100-500 makale/saat (timeout'lar vs)
```

## 🆘 Acil Durum Kurtarma

### Tüm Sistem Çöktü

```bash
1. Tüm key'leri sil:
   WordPress → API Anahtarları → Hepsini tek tek sil

2. Database'i temizle:
   phpMyAdmin → wp_amo_api_keys → Truncate

3. Yeni baştan başla:
   - 3-5 adet yeni Google hesabı aç
   - Her birinden API key al
   - Hepsini WordPress'e ekle
   - Tek tek test et

4. İlk makaleyi üret:
   - Kelime ekle
   - "Şimdi Makale Üret" bas
   - Log'u izle
```

### Plugin Çalışmıyor

```bash
1. Eklenti deaktif et
2. Eklenti aktif et (hook'ları yeniden kaydet)
3. API key'leri kontrol et
4. Kelime listesi var mı kontrol et
5. Test et
```

## 📞 Destek

Sorun çözülmediyse:

1. **Error log'u kaydedin** (`wp-content/debug.log`)
2. **Test sonuçlarını kaydedin** (her key için)
3. **Screenshot alın** (hata mesajlarının)
4. **Bilgileri toplayın:**
   - WordPress versiyonu
   - PHP versiyonu
   - Kaç API key var
   - Test sonuçları

---

## 🎯 Özet: Akıllı Rotation Mantığı

### ✅ Sistem Nasıl Çalışır?

```
1. Makale üretimi başlatılır
2. Sistem tüm AKTIF key'leri sırayla dener
3. İlk başarılı key ile makale oluşturulur
4. Başarısız key'ler AKTIF kalır (geçici sorun olabilir)
5. Bir sonraki makale için AYNI süreç tekrarlanır
6. Key'ler ASLA otomatik deaktif olmaz
```

### 🔄 Gerçek Hayat Örneği

```
Saat 10:00 - Makale 1
├─ Key1: Quota exceeded (atlandı)
├─ Key2: Success ✅ (makale oluşturuldu)
└─ Key3: Denenmedi (gerek kalmadı)

Saat 11:00 - Makale 2
├─ Key1: Success ✅ (quota reset olmuş!)
└─ Key2: Denenmedi

Saat 12:00 - Makale 3
├─ Key1: Timeout (atlandı)
├─ Key2: Quota exceeded (atlandı)
├─ Key3: Success ✅
└─ Sonuç: 3 makale oluşturuldu, hiç key deaktif olmadı!
```

### 🎓 En İyi Pratikler

1. **En az 3-5 API key ekleyin** (quota dağılımı için)
2. **Key'leri manuel test edin** (hangileri çalışıyor görmek için)
3. **Log'ları izleyin** (rotation'u anlamak için)
4. **Başarısız key'leri silmeyin** (bir süre sonra çalışabilir)
5. **Sabırlı olun** (sistem tüm key'leri dener, sonunda başarılı olur)

---

**Son Güncelleme:** Sistem artık akıllı rotation yapıyor! Key'ler otomatik deaktif olmaz, geçici hatalar bekleyerek yeniden denenir. Rate limit/quota reset olunca key'ler kendiliğinden çalışmaya devam eder! ✨
