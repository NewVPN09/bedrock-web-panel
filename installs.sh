#!/bin/bash
set -e

echo "[+] Starting fully automated Bedrock Web Panel + Server installation..."

# --- Update & install dependencies ---
apt update -y
apt upgrade -y
apt install -y nginx php php-fpm php-cli php-curl php-mbstring php-zip ufw sudo unzip git wget netcat-openbsd

# --- Create minecraft user if it doesn't exist ---
if ! id -u minecraft &>/dev/null; then
    useradd -m -s /bin/bash minecraft
    echo "[+] Created user 'minecraft'"
else
    echo "[i] User 'minecraft' already exists, skipping creation"
fi

# --- Remove old installation ---
rm -rf /var/www/panel /etc/sudoers.d/bedrock-panel /etc/systemd/system/bedrock.service
mkdir -p /var/www/panel
mkdir -p /home/minecraft/Server/logs
mkdir -p /home/minecraft/backups

# --- Clone the panel repo (only the web folder) ---
git clone --depth 1 --branch main https://github.com/NewVPN09/bedrock-web-panel.git /tmp/bedrock-web-panel
cp -r /tmp/bedrock-web-panel/web/* /var/www/panel/
rm -rf /tmp/bedrock-web-panel

# --- Set correct ownership and permissions for Nginx ---
chown -R www-data:www-data /var/www/panel
chmod -R 755 /var/www/panel
chown -R minecraft:minecraft /home/minecraft/Server
chmod -R 755 /home/minecraft/Server

# --- Create config.php with default admin credentials ---
cat > /var/www/panel/config.php << 'EOF'
<?php
define('ADMIN_USER', 'admin');
define('ADMIN_PASS_HASH', password_hash('mika', PASSWORD_DEFAULT));
define('LOG_FILE', '/home/minecraft/Server/logs/bedrock.log');
EOF

# --- Install Minecraft Bedrock Server ---
cd /home/minecraft/Server
if [ ! -f "bedrock_server" ]; then
    echo "[+] Downloading Minecraft Bedrock Server..."
    wget -O bedrock-server.zip https://www.minecraft.net/bedrockdedicatedserver/bin-linux/bedrock-server-1.21.131.1.zip
    unzip -o bedrock-server.zip
    rm bedrock-server.zip
    chown -R minecraft:minecraft /home/minecraft/Server
fi

# --- Setup systemd service ---
cat > /etc/systemd/system/bedrock.service << 'EOF'
[Unit]
Description=Minecraft Bedrock Server
After=network.target

[Service]
User=minecraft
WorkingDirectory=/home/minecraft/Server
ExecStart=/home/minecraft/Server/bedrock_server
ExecStop=/bin/bash -c 'echo stop | /usr/bin/nc -u 127.0.0.1 19132'
Restart=on-failure
RestartSec=10
StandardOutput=append:/home/minecraft/Server/logs/bedrock.log
StandardError=append:/home/minecraft/Server/logs/error.log
LimitNOFILE=100000

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable bedrock

# --- Setup sudoers for panel control ---
cat > /etc/sudoers.d/bedrock-panel << 'EOF'
www-data ALL=(root) NOPASSWD: \
/bin/systemctl start bedrock, \
/bin/systemctl stop bedrock, \
/bin/systemctl restart bedrock, \
/bin/systemctl status bedrock
EOF
chmod 440 /etc/sudoers.d/bedrock-panel

# --- Setup Nginx site ---
cat > /etc/nginx/sites-available/panel << 'EOF'
server {
    listen 80;
    server_name _;

    root /var/www/panel;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php-fpm.sock;
    }

    location ~ /\. {
        deny all;
    }
}
EOF

ln -sf /etc/nginx/sites-available/panel /etc/nginx/sites-enabled/panel
rm -f /etc/nginx/sites-enabled/default

nginx -t
systemctl restart nginx
systemctl enable nginx

# --- Restart PHP-FPM ---
systemctl restart php8.2-fpm
systemctl enable php8.2-fpm

# --- Firewall ---
ufw allow 80/tcp
ufw allow 19132/udp
ufw --force enable

echo "[✓] Installation complete!"
echo "Open http://YOUR_SERVER_IP/ and login with admin / mika"
echo "You can start the Minecraft server from the panel after login."
