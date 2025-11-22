#!/bin/bash
# Render.com startup script
# Render automatically sets $PORT, so we just use it directly

php -S 0.0.0.0:$PORT -t . index.php

