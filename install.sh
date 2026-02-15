#!/bin/bash

# OpenAI Assistant Widget Installation Script for Zabbix 7.0
# This script installs the widget to Zabbix modules directory

set -e

echo "=================================================="
echo "OpenAI Assistant Widget - Installation Script"
echo "=================================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Error: Please run as root or with sudo${NC}"
    exit 1
fi

# Detect Zabbix installation directory
ZABBIX_DIRS=(
    "/usr/share/zabbix/modules"
    "/usr/local/share/zabbix/modules"
    "/var/www/html/zabbix/modules"
    "/opt/zabbix/modules"
)

ZABBIX_MODULE_DIR=""

echo "Searching for Zabbix modules directory..."

for dir in "${ZABBIX_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        ZABBIX_MODULE_DIR="$dir"
        echo -e "${GREEN}Found Zabbix modules directory: $ZABBIX_MODULE_DIR${NC}"
        break
    fi
done

if [ -z "$ZABBIX_MODULE_DIR" ]; then
    echo -e "${YELLOW}Could not automatically detect Zabbix modules directory.${NC}"
    read -p "Please enter the full path to Zabbix modules directory: " ZABBIX_MODULE_DIR
    
    if [ ! -d "$ZABBIX_MODULE_DIR" ]; then
        echo -e "${RED}Error: Directory $ZABBIX_MODULE_DIR does not exist${NC}"
        exit 1
    fi
fi

# Widget installation directory
WIDGET_DIR="$ZABBIX_MODULE_DIR/openai-assistant"

# Check if widget already exists
if [ -d "$WIDGET_DIR" ]; then
    echo -e "${YELLOW}Widget already exists at $WIDGET_DIR${NC}"
    read -p "Do you want to overwrite it? (y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Installation cancelled."
        exit 0
    fi
    rm -rf "$WIDGET_DIR"
fi

# Create widget directory
echo "Creating widget directory..."
mkdir -p "$WIDGET_DIR"

# Copy files
echo "Copying widget files..."
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

cp -r "$SCRIPT_DIR"/* "$WIDGET_DIR/"

# Remove installation script from widget directory
rm -f "$WIDGET_DIR/install.sh"

# Detect web server user
WEB_USER="www-data"
if id "apache" &>/dev/null; then
    WEB_USER="apache"
elif id "nginx" &>/dev/null; then
    WEB_USER="nginx"
fi

echo "Setting permissions (web user: $WEB_USER)..."
chown -R "$WEB_USER:$WEB_USER" "$WIDGET_DIR"
chmod -R 755 "$WIDGET_DIR"

# Find specific files that should not be executable
find "$WIDGET_DIR" -type f \( -name "*.php" -o -name "*.js" -o -name "*.css" -o -name "*.json" -o -name "*.md" -o -name "*.yaml" \) -exec chmod 644 {} \;

echo ""
echo -e "${GREEN}=================================================="
echo "Installation completed successfully!"
echo "==================================================${NC}"
echo ""
echo "Widget installed to: $WIDGET_DIR"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Restart Zabbix server:"
echo "   sudo systemctl restart zabbix-server"
echo ""
echo "2. Restart web server:"
if command -v apache2 &> /dev/null || command -v httpd &> /dev/null; then
    echo "   sudo systemctl restart apache2  # or httpd"
elif command -v nginx &> /dev/null; then
    echo "   sudo systemctl restart nginx"
    echo "   sudo systemctl restart php-fpm"
fi
echo ""
echo "3. Clear browser cache and refresh Zabbix dashboard"
echo ""
echo "4. Add 'OpenAI Assistant' widget to your dashboard"
echo ""
echo "5. Configure with your OpenAI API key from:"
echo "   https://platform.openai.com/api-keys"
echo ""
echo -e "${GREEN}Enjoy your AI assistant!${NC}"

