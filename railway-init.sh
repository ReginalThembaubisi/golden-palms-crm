#!/bin/bash
# Railway Database Initialization Script
# This runs after the build phase to set up the database

echo "Running database initialization..."

# Run the database init script
php database/init.php

# If init fails, it's okay - might be first run or tables already exist
if [ $? -eq 0 ]; then
    echo "✓ Database initialization completed"
else
    echo "⚠ Database initialization had issues (this is okay if tables already exist)"
fi

