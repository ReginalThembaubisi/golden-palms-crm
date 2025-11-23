#!/bin/sh
# Railway start script - simple and reliable
PORT="${PORT:-8080}"
exec php -S "0.0.0.0:${PORT}" -t . index.php

