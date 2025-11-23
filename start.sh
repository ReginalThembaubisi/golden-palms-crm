#!/bin/bash
# Railway start script - properly expands PORT variable
# Railway sets PORT as environment variable
export PORT=${PORT:-8080}
exec php -S 0.0.0.0:${PORT} -t . index.php
