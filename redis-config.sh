#!/bin/bash
# Redis Configuration Helper for v01.13 Chat Application

echo "Installing and configuring Redis..."

# Install Redis (if not already installed)
if ! command -v redis-server &> /dev/null; then
    sudo apt update
    sudo apt install -y redis-server
fi

# Update Redis configuration for better performance
sudo tee /etc/redis/redis.conf > /dev/null << EOL
# Basic configuration
bind 127.0.0.1
port 6379
daemonize yes
supervised systemd

# Performance optimizations for chat app
maxmemory 256mb
maxmemory-policy allkeys-lru
appendonly yes
appendfsync everysec

# Connection settings
timeout 300
tcp-keepalive 60
EOL

# Restart Redis to apply changes
sudo systemctl restart redis-server

# Install PHP Redis extension
sudo apt install -y php8.2-redis

# Verify Redis is working
redis-cli ping

echo "Redis configuration completed successfully!" 