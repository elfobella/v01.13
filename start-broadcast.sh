#!/bin/bash

# Start the Laravel queue worker for broadcast processing
echo "Starting Laravel Queue Worker for broadcast..."
echo "This will process broadcast events in the background."
echo "Press Ctrl+C to stop"

# Run queue worker
php artisan queue:work --queue=broadcast 