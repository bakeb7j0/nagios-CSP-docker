#!/bin/bash
echo "Starting MySQL..."
mysqld_safe --bind-address=127.0.0.1 &

# Wait for MySQL to be fully up
sleep 10

echo "Starting Apache..."
apachectl -D FOREGROUND
