#!/bin/sh
# Railway startup script - handles PORT variable properly

# Get PORT from environment, default to 8080
PORT=${PORT:-8080}

# Start PHP server
exec php -S 0.0.0.0:$PORT -t . index.php
