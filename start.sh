#!/bin/sh
# Railway startup script
# Handles PORT environment variable properly

PORT=${PORT:-8080}
exec php -S 0.0.0.0:$PORT -t . index.php

