# 📊 Debug Log Görüntüleyici - Kullanım Kılavuzu

## ✨ Özellikler

### 1. **Anlık Log Görüntüleme**
- ✅ Sayfa yenilenmeden modal içinde açılır
- ✅ Son 500 log kaydı gösterilir
- ✅ Otomatik parse ve kategorizasyon
- ✅ Gerçek zamanlı filtreleme

### 2. **Akıllı Log Analizi**
- 🔴 **Kritik:** Fatal error, Database error
- 🟠 **Yüksek:** Quota exceeded, Invalid API key, AMO errors
- 🟡 **Orta:** Timeout, Network errors
- 🟢 **Başarılı:** Success messages
- 🔵 **Düşük:** Info, warnings

### 3. **Detaylı Sorun Çözüm Bilgisi**
Her log için:
- ⚠️ **Sorun:** Ne oldu?
- ✅ **Çözüm:** Nasıl çözülür?
- 🎯 **Aksiyon:** Ne yapmalı?

## 🎯 Kullanım

### Adım 1: Log Görüntüleyiciyi Aç

```
WordPress Admin → Makale Oluşturucu → Dashboard
→ "Log Görüntüle" butonuna tıkla
```

### Adım 2: Log'ları İncele

**Modal açılır ve gösterir:**
- 📝 Toplam log sayısı
- 💾 Log dosyası boyutu
- 🕐 Son değişiklik zamanı

### Adım 3: Filtreleme

**Üst menüden filtre seç:**
- **Tümü:** Tüm log'lar
- **🔴 Kritik:** Acil müdahale gerekli
- **🟠 Yüksek:** Önemli hatalar
- **🟡 Orta:** Geçici sorunlar
- **🟢 Başarılı:** Başarılı işlemler

### Adım 4: Detay İncele

Her log satırında **"Detay"** butonuna tıklayın:

```
📊 Log Detayı
├─ 📅 Zaman: 14-Oct-2025 12:00:00
├─ ⚠️ Sorun: API key quota aşıldı
├─ 💬 Tam Mesaj: [Full log message]
├─ 🔑 API Key: AIzaSy...
├─ ✅ Çözüm: Sistem otomatik olarak diğer key'e geçecek
└─ 🎯 Yapılması Gereken: Yeni key eklemek isterseniz: API Anahtarları → Yeni Key Ekle
```

## 📊 Log Tipleri ve Anlamları

### 🔴 Kritik (İvedi Müdahale)

#### **Fatal Error**
```
Problem: PHP kritik hatası
Çözüm: Plugin yeniden yüklenmeli
Aksiyon: Eklenti deaktif → aktif et
```

#### **Database Error**
```
Problem: Veritabanı hatası
Çözüm: Tablo zaten var veya DB sorunu
Aksiyon: Eklenti deaktif → aktif et (tabloları yeniden oluşturur)
```

### 🟠 Yüksek (Önemli)

#### **Quota Exceeded**
```
Problem: API key quota aşıldı
Çözüm: Sistem otomatik diğer key'e geçer. Gece yarısı quota reset olur.
Aksiyon: Yeni key eklemek isterseniz: API Anahtarları → Yeni Key Ekle
```

#### **Invalid API Key**
```
Problem: API key geçersiz
Çözüm: Sistem otomatik diğer key'e geçer
Aksiyon: API Anahtarları → Key'i test et veya yeni key ekle
```

#### **AMO Error**
```
Problem: Plugin içi hata
Çözüm: Sistem otomatik düzeltme yapacak
Aksiyon: Log'u izle, sorun devam ederse destek al
```

### 🟡 Orta (Geçici)

#### **Timeout**
```
Problem: İstek zaman aşımı
Çözüm: Sistem otomatik diğer key'e geçer. Network geçici yavaş olabilir.
Aksiyon: İnternet bağlantınızı kontrol edin
```

#### **Network Error**
```
Problem: Network hatası
Çözüm: Sistem otomatik diğer key'e geçer
Aksiyon: Birkaç dakika bekleyin ve tekrar deneyin
```

### 🟢 Başarılı

```
Problem: Başarılı işlem
Çözüm: Her şey normal çalışıyor!
Aksiyon: Herhangi bir işlem gerekmez
```

## 🎨 Görsel Göstergeler

### Severity (Önem Seviyesi)
| Icon | Seviye | Renk | Anlamı |
|------|--------|------|--------|
| 🔴 | Critical | Kırmızı | Acil müdahale |
| 🟠 | High | Turuncu | Önemli hata |
| 🟡 | Medium | Sarı | Geçici sorun |
| 🟢 | Success | Yeşil | Başarılı |
| 🔵 | Low | Mavi | Bilgilendirme |

### Type Badges
| Badge | Renk | Açıklama |
|-------|------|----------|
| Quota | Kırmızı | Quota aşıldı |
| Geçersiz Key | Turuncu | Invalid key |
| Timeout | Sarı | Zaman aşımı |
| Network | Mavi | Ağ hatası |
| Fatal | Kırmızı | Kritik hata |
| Success | Yeşil | Başarılı |

### Tablo Renk Kodları

**Sol kenar rengi:**
- 🔴 Kırmızı çizgi = Kritik
- 🟠 Turuncu çizgi = Yüksek
- 🟡 Sarı çizgi = Orta
- 🟢 Yeşil çizgi = Başarılı

## 🔍 Örnek Senaryolar

### Senaryo 1: Tüm Key'ler Quota Aşıldı

**Log'da görecekleriniz:**
```
🟠 [14-Oct-2025 12:00:00] AMO: Key failed, trying next: AIzaSy... - Error: You exceeded your current quota
🟠 [14-Oct-2025 12:00:05] AMO: Key failed, trying next: AIzaBV... - Error: You exceeded your current quota
🟠 [14-Oct-2025 12:00:10] AMO: Key failed, trying next: AIzaSC... - Error: You exceeded your current quota
```

**Detay butonuna tıklayınca:**
```
⚠️ Sorun: API key quota aşıldı
✅ Çözüm: Sistem otomatik olarak diğer key'e geçecek. Gece yarısı quota reset olur.
🎯 Aksiyon: Yeni key eklemek isterseniz: API Anahtarları → Yeni Key Ekle
```

**Ne yapmalı:**
1. Normal! Sistem diğer key'leri deniyor
2. Gece yarısını bekleyin (quota reset)
3. Veya yeni key ekleyin

### Senaryo 2: Başarılı Makale Üretimi

**Log'da görecekleriniz:**
```
🟢 [14-Oct-2025 12:05:00] Article published successfully with key: AIzaSy...
```

**Detay:**
```
⚠️ Sorun: Başarılı işlem
✅ Çözüm: Her şey normal çalışıyor!
🎯 Aksiyon: Herhangi bir işlem gerekmez
```

### Senaryo 3: Timeout Sonra Başarı

**Log'da görecekleriniz:**
```
🟡 [14-Oct-2025 12:10:00] AMO: Key failed, trying next: AIzaSy... - Error: cURL error 28: Operation timed out
🟢 [14-Oct-2025 12:10:05] Article published successfully with key: AIzaBV...
```

**Analiz:**
1. İlk key timeout oldu (geçici)
2. Sistem otomatik 2. key'e geçti
3. Başarılı makale oluşturuldu ✅

## 💡 İpuçları

### 1. Düzenli Kontrol
```
Her sabah log'ları kontrol edin:
- 🔴 Kritik var mı?
- 🟠 Çok fazla hata var mı?
- 🟢 Başarılı işlemler oluyor mu?
```

### 2. Filtreleme Kullanın
```
Önce "🔴 Kritik" filtresi:
→ Acil sorunları görmek için

Sonra "🟢 Başarılı" filtresi:
→ Sistemin çalıştığını doğrulamak için
```

### 3. Detay İnceleyin
```
Her hatanın detayında:
- Tam hata mesajı
- Önerilen çözüm
- Yapılacak adımlar
```

### 4. Pattern Tanıma
```
Aynı hata tekrarlanıyor mu?
→ Kalıcı sorun olabilir, müdahale et

Farklı hatalar mı?
→ Normal, sistem rotation yapıyor
```

## 📋 Teknik Detaylar

### Log Okuma
```php
// Son 500 satır okunur
$lines = read_last_lines('debug.log', 500);

// En yeni log en üstte
$logs = array_reverse($parsed_logs);
```

### Log Parsing
```php
// Otomatik kategorizasyon
'quota' => '/exceeded your current quota/i'
'invalid_key' => '/API key not valid/i'
'timeout' => '/Operation timed out/i'
'network' => '/cURL error/i'
'fatal' => '/PHP Fatal error/i'
```

### Severity Belirleme
```php
'fatal', 'database' → Critical
'quota', 'invalid_key' → High
'timeout', 'network' → Medium
'success' → Success
```

## 🔄 Otomatik Yenileme (İsteğe Bağlı)

Modal açık kaldığı sürece otomatik yenilenmez. 

**Manuel yenileme için:**
1. Modal'ı kapat
2. "Log Görüntüle" butonuna tekrar tıkla
3. Güncel log'lar yüklenir

## 🎓 Best Practices

### DO ✅
```
✅ Her gün log kontrol et
✅ Kritik hataları önceliklendir
✅ Detay butonunu kullan
✅ Pattern'leri tanı
✅ Çözüm önerilerini uygula
```

### DON'T ❌
```
❌ Log'ları hiç kontrol etme
❌ Tüm hataları görmezden gel
❌ Aynı hatayı tekrar tekrar yap
❌ Çözüm aramadan destek iste
```

## 🆘 Sorun Giderme

### Log Görüntüleyici Açılmıyor

**Çözüm:**
```
1. Ctrl + Shift + R (hard refresh)
2. Browser console'u aç (F12)
3. Hata var mı kontrol et
4. JavaScript yüklendi mi kontrol et
```

### Log Dosyası Bulunamadı

**Çözüm:**
```
wp-config.php'de debug aktif mi?

define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Çok Fazla Log Var

**Çözüm:**
```
1. Filtreleme kullan
2. Sadece kritik/yüksek bak
3. Log dosyasını temizle (wp-content/debug.log)
```

## 📚 Örnek Kullanım Akışı

### Sabah Rutini (5 dakika)

```
1. Dashboard → "Log Görüntüle" [09:00]
   ├─ 📝 500 log kaydı yüklendi
   └─ 💾 2.5 MB

2. "🔴 Kritik" filtresine tıkla [09:01]
   ├─ Sonuç: 0 kritik hata
   └─ ✅ İyi!

3. "🟠 Yüksek" filtresine tıkla [09:02]
   ├─ Sonuç: 15 quota exceeded
   ├─ Detaya bak
   └─ Çözüm: Normal, rotation çalışıyor

4. "🟢 Başarılı" filtresine tıkla [09:03]
   ├─ Sonuç: 50 başarılı makale
   └─ ✅ Sistem çalışıyor!

5. Modal'ı kapat [09:04]
   └─ Kontrol tamamlandı!
```

### Hata Durumunda (10 dakika)

```
1. "Log Görüntüle" aç [10:00]

2. Çok fazla 🔴 kırmızı var [10:01]
   └─ Filtre: "🔴 Kritik"

3. İlk kritik hataya tıkla "Detay" [10:02]
   ├─ Sorun: PHP Fatal error
   ├─ Çözüm: Plugin yeniden yükle
   └─ Aksiyon: Deaktif → Aktif

4. Eklenti → Deaktif [10:03]

5. Eklenti → Aktif [10:04]

6. Test et: "Şimdi Makale Üret" [10:05]

7. "Log Görüntüle" tekrar aç [10:08]
   ├─ 🟢 Başarılı makale!
   └─ ✅ Sorun çözüldü!
```

## 🌟 Özet

**Log Görüntüleyici:**
- ✅ Sayfa yenilenmeden çalışır
- ✅ Anlık log analizi
- ✅ Detaylı sorun çözüm bilgisi
- ✅ Akıllı filtreleme
- ✅ Renk kodlu görselleştirme
- ✅ Her log için aksiyon önerisi

**Kullanım:**
1. Dashboard → "Log Görüntüle"
2. Filtreleyerek incele
3. Detay butonuyla derinleştir
4. Önerilen aksiyonları uygula

**Sonuç:**
Artık debug log'larını kolayca takip edebilir, sorunları hızlıca tespit edebilir ve çözüm önerilerini uygulayabilirsiniz! 🎉

---

**Not:** Bu özellik gerçek zamanlı çalışır ve sayfa yenilenmeden modal içinde açılır. Log dosyası her açılışta otomatik parse edilir ve kategorize edilir!
