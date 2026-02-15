# OpenAI Assistant Widget - Kullanım Kılavuzu (Türkçe)

## 🇹🇷 Türkçe Kılavuz

### Nedir Bu?

OpenAI Assistant Widget, Zabbix 7.0 dashboard'unuza yapay zeka desteği getiren gelişmiş bir widget'tır. OpenAI'ın güçlü dil modellerini doğrudan Zabbix içinde kullanmanızı sağlar.

### Özellikler

#### 🤖 Çoklu AI Modelleri
8 farklı OpenAI modelinden seçim yapabilirsiniz:
- **GPT-4o** - En iyi genel denge
- **GPT-4o Mini** - Hızlı ve ekonomik
- **GPT-4 Turbo** - Gelişmiş analiz
- **GPT-4** - En yüksek doğruluk
- **GPT-3.5 Turbo** - Çok hızlı
- **O1 / O1 Mini** - En yeni modeller

#### 💬 Akıllı Konuşmalar
- Konuşma geçmişini hatırlar
- Bağlamı korur
- Gerçek zamanlı yanıtlar
- Geçmiş temizleme özelliği

#### 🎨 Modern Arayüz
- Güzel gradient tasarım
- Akıcı animasyonlar
- Kod vurgulama
- Tek tıkla kod kopyalama
- Markdown desteği

### Kurulum

#### Linux Otomatik Kurulum

```bash
cd zabbix-widget-chatgpt
sudo chmod +x install.sh
sudo ./install.sh
```

#### Manuel Kurulum

```bash
# Widget'ı Zabbix modül dizinine kopyalayın
sudo cp -r zabbix-widget-chatgpt /usr/share/zabbix/modules/openai-assistant

# İzinleri ayarlayın
sudo chown -R www-data:www-data /usr/share/zabbix/modules/openai-assistant
sudo chmod -R 755 /usr/share/zabbix/modules/openai-assistant

# Servisleri yeniden başlatın
sudo systemctl restart zabbix-server
sudo systemctl restart apache2
```

### Başlangıç

#### 1. Adım: API Anahtarı Alın
1. https://platform.openai.com/api-keys adresine gidin
2. Yeni API anahtarı oluşturun
3. Kopyalayın (`sk-...` ile başlar)

#### 2. Adım: Widget'ı Ekleyin
1. Zabbix dashboard'unu açın
2. "Dashboard'u düzenle" tıklayın
3. Widget ekle → "OpenAI Assistant" bulun
4. API anahtarınızı yapıştırın

#### 3. Adım: Model Seçin
1. Widget ayarlarını açın (dişli ikonu)
2. "Gelişmiş Yapılandırma" bölümünü genişletin
3. İstediğiniz modeli seçin
4. Kaydedin

#### 4. Adım: Sohbet Başlayın!
Sorunuzu yazın ve Enter'a basın!

### Örnek Sorular

#### Monitoring İçin
```
Zabbix'te veritabanı performansını izlemek için en iyi yöntemler nelerdir?
```

#### Sorun Giderme İçin
```
Web sunucum yanıt süresi %300 arttı. CPU normal ama bellek %90'da. 
Neyi kontrol etmeliyim?
```

#### Yapılandırma İçin
```
Disk alanı %10'un altına düştüğünde ve 30 dakika boyunca 
azalmaya devam ettiğinde uyarı veren bir trigger nasıl oluştururum?
```

### Yapılandırma

#### Sıcaklık (Temperature)
Yanıtlardaki rastgeleliği kontrol eder (0.0 - 2.0):
- **0.0-0.3**: Daha odaklı, tutarlı
- **0.7-0.9**: Dengeli (önerilen)
- **1.5-2.0**: Daha yaratıcı

#### Maksimum Token
Yanıt uzunluğu:
- **512**: Kısa yanıtlar
- **2048**: Orta (önerilen)
- **4096**: Uzun, detaylı yanıtlar

#### Sistem İstemi Örnekleri

**Zabbix Uzmanı:**
```
Sen Zabbix monitoring konusunda uzman bir asistansın. Metrikler, 
tetikleyiciler ve sorun analizi hakkında açık ve uygulanabilir 
öneriler sun. Her zaman örneklerle açıkla.
```

**Güvenlik Analisti:**
```
Sen siber güvenlik uzmanısın. Tehdit tespiti ve olay müdahalesi 
konusunda uzmansın. Güvenlik olaylarını analiz et ve çözüm stratejileri sun.
```

### Model Karşılaştırması

| Model | Hız | Kalite | Maliyet (1K token) |
|-------|-----|--------|-------------------|
| GPT-4o Mini | ⚡⚡⚡⚡ | ⭐⭐⭐ | $0.00015 |
| GPT-4o | ⚡⚡⚡ | ⭐⭐⭐⭐ | $0.005 |
| GPT-4 Turbo | ⚡⚡ | ⭐⭐⭐⭐⭐ | $0.01 |
| GPT-4 | ⚡ | ⭐⭐⭐⭐⭐ | $0.03 |

### Kullanım Senaryoları

**GPT-4o Mini için:**
- Hızlı monitoring sorguları
- Log analizi
- Basit sorun giderme
- Yüksek hacimli istekler

**GPT-4o için:**
- Olay araştırması
- Performans analizi
- Yapılandırma yardımı
- Genel amaçlı kullanım

**GPT-4 Turbo için:**
- Karmaşık problem çözme
- Kök neden analizi
- Stratejik planlama
- Detaylı raporlama

### Güvenlik

- API anahtarınızı asla paylaşmayın
- Hassas bilgileri AI'ya göndermeyin
- Hassas verilerle çalışırken geçmişi temizleyin
- API anahtarlarını düzenli olarak yenileyin
- API kullanımını OpenAI dashboard'undan izleyin

### Sorun Giderme

#### Widget görünmüyor
```bash
# İzinleri kontrol edin
ls -la /usr/share/zabbix/modules/openai-assistant/

# Zabbix loglarını kontrol edin
tail -f /var/log/zabbix/zabbix_server.log

# Tarayıcı önbelleğini temizleyin
Ctrl + Shift + Delete
```

#### API Hataları
- ✅ API anahtarının doğru olduğunu kontrol edin
- ✅ OpenAI hesabınızda kredi olduğunu kontrol edin
- ✅ Endpoint URL'ini doğrulayın
- ✅ İnternet bağlantısını kontrol edin

#### Yavaş Yanıtlar
- Daha hızlı model seçin (GPT-4o Mini)
- Streaming'i etkinleştirin
- max_tokens değerini azaltın
- Ağ gecikmesini kontrol edin

### Performans İpuçları

#### Hız İçin
- **GPT-4o Mini** veya **GPT-3.5 Turbo** kullanın
- Streaming'i etkinleştirin
- Max tokens: 1024

#### Kalite İçin
- **GPT-4 Turbo** veya **GPT-4** kullanın
- Max tokens: 4096
- Temperature: 0.3

#### Maliyet İçin
- **GPT-4o Mini** kullanın
- Max tokens: 512-1024
- Göreve uygun model seçin

### Özellik Karşılaştırması

| Özellik | Bu Widget | ChatGPT Ücretsiz Widget |
|---------|-----------|------------------------|
| Çoklu Model | ✅ 8 model | ❌ Sabit model |
| Model Seçimi | ✅ Tam seçim | ❌ Sınırlı |
| Konuşma Geçmişi | ✅ Kaydediliyor | ❌ Yok |
| Özel Sistem İstemleri | ✅ Evet | ❌ Hayır |
| Akış Kontrolü | ✅ Açma/Kapama | ⚠️ Sadece akış |
| Kod Kopyalama | ✅ Evet | ❌ Hayır |
| Geçmiş Temizleme | ✅ Evet | ❌ Hayır |
| Sıcaklık Kontrolü | ✅ Ayarlanabilir | ❌ Sabit |
| Özel Endpoint | ✅ Evet | ❌ Sadece OpenAI |
| Türkçe Dil | ✅ Evet | ⚠️ Sınırlı |

### Sık Sorulan Sorular

**S: Widget ne kadar maliyetli?**
C: Widget ücretsizdir. Sadece OpenAI API kullanımı için ödeme yaparsınız. GPT-4o Mini çok ekonomiktir (1000 token = $0.00015).

**S: Konuşma geçmişi nerede saklanır?**
C: Tarayıcınızın localStorage'ında. Sunucuya gönderilmez.

**S: Türkçe soru sorabilir miyim?**
C: Evet! OpenAI modelleri Türkçe'yi mükemmel şekilde destekler.

**S: Hangi modeli kullanmalıyım?**
C: Çoğu kullanım için GPT-4o Mini yeterlidir. Karmaşık analizler için GPT-4 Turbo kullanın.

**S: API anahtarım güvende mi?**
C: Evet, widget içinde şifrelenerek saklanır. Asla başka yerlere gönderilmez.

### Lisans

MIT License - Özgürce kullanabilir, değiştirebilir ve dağıtabilirsiniz!

---

**Zabbix topluluğu için ❤️ ile yapıldı**

AI destekli monitoring asistanınızın keyfini çıkarın! 🤖✨

