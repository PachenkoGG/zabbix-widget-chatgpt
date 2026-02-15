#!/bin/bash

# Zabbix OpenAI Assistant Widget - Update Script
# Bu script widget'ı GitHub'dan güncelleyerek kurar

echo "======================================"
echo "Zabbix OpenAI Assistant Widget Update"
echo "======================================"
echo ""

# Root kontrolü
if [ "$EUID" -ne 0 ]; then 
    echo "Lütfen root olarak çalıştırın: sudo bash update_widget.sh"
    exit 1
fi

# Zabbix modules dizini
MODULES_DIR="/usr/share/zabbix/modules"
WIDGET_DIR="$MODULES_DIR/openai-assistant"

# Eski widget'ı yedekle (varsa)
if [ -d "$WIDGET_DIR" ]; then
    echo "Mevcut widget bulundu, yedekleniyor..."
    BACKUP_DIR="${WIDGET_DIR}_backup_$(date +%Y%m%d_%H%M%S)"
    mv "$WIDGET_DIR" "$BACKUP_DIR"
    echo "✓ Yedek oluşturuldu: $BACKUP_DIR"
fi

# Yeni widget'ı clone yap
echo ""
echo "GitHub'dan son versiyon indiriliyor..."
cd "$MODULES_DIR"
git clone https://github.com/PachenkoGG/zabbix-widget-chatgpt.git openai-assistant

if [ $? -ne 0 ]; then
    echo "✗ Git clone başarısız!"
    exit 1
fi

echo "✓ Widget indirildi"

# İzinleri ayarla
echo ""
echo "İzinler ayarlanıyor..."

# Web server user'ı tespit et
WEB_USER="www-data"
if id "apache" &>/dev/null; then
    WEB_USER="apache"
elif id "nginx" &>/dev/null; then
    WEB_USER="nginx"
fi

chown -R "$WEB_USER:$WEB_USER" "$WIDGET_DIR"
chmod -R 755 "$WIDGET_DIR"

echo "✓ İzinler ayarlandı (user: $WEB_USER)"

# Servisleri yeniden başlat
echo ""
echo "Servisler yeniden başlatılıyor..."

systemctl restart zabbix-server
if [ $? -eq 0 ]; then
    echo "✓ Zabbix server yeniden başlatıldı"
else
    echo "✗ Zabbix server yeniden başlatılamadı!"
fi

# Web server'ı yeniden başlat
if systemctl is-active --quiet apache2; then
    systemctl restart apache2
    echo "✓ Apache yeniden başlatıldı"
elif systemctl is-active --quiet nginx; then
    systemctl restart nginx
    systemctl restart php-fpm
    echo "✓ Nginx ve PHP-FPM yeniden başlatıldı"
fi

echo ""
echo "======================================"
echo "✓ Kurulum tamamlandı!"
echo "======================================"
echo ""
echo "Sonraki adımlar:"
echo "1. Tarayıcıda cache temizleyin (Ctrl + Shift + Delete)"
echo "2. Zabbix dashboard'a gidin"
echo "3. Widget'ı dashboard'a ekleyin veya ayarlarını güncelleyin"
echo "4. OpenAI API key'inizi girin"
echo "5. Advanced Configuration > Include Zabbix Data > Yes seçin"
echo ""
echo "Widget konumu: $WIDGET_DIR"
echo ""

