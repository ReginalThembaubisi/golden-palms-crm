#!/bin/sh
# Railway start script - simple and reliable
# Get PORT from environment (Railway sets this)
if [ -z "$PORT" ]; then
    PORT=8080
fi
echo "Starting PHP server on 0.0.0.0:$PORT"
exec php -S "0.0.0.0:$PORT" -t . index.php

