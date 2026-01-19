#!/bin/bash
set -e

echo "[+] Installing Bedrock Web Panel (FULL)"

apt update -y
apt install -y nginx php php-fpm php-cli php-curl php-mbstring php-zip php-json ufw sudo unzip zip tar certbot python3-certbot-nginx netcat-openbsd

id minecraft &>/dev/null || useradd -m -s /bin/bash minecraft

mkdir -p /home/minecraft/Server/logs
mkdir -p /home/minecraft/backups
chown -R minecraft:minecraft /home/minecraft

cp systemd/bedrock.service /etc/systemd/system/bedrock.service
systemctl daemon-reload
systemctl enable bedrock

cp sudo/bedrock-panel /etc/sudoers.d/bedrock-panel
chmod 440 /etc/sudoers.d/bedrock-panel

rm -rf /var/www/panel
mkdir -p /var/www/panel
cp -r web/* /var/www/panel/
chown -R www-data:www-data /var/www/panel

ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 19132/udp
ufw --force enable

echo "[✓] Install complete"
echo "Open: http://YOUR_SERVER_IP/"
echo "After DNS is set, run: certbot --nginx"
