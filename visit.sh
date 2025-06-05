#!/bin/bash

URL="https://plan.knb/api/v1/itinerary?duration_days=30&adventurousness=0.8&solitariness=0.7&strenuousness=0.6"
INTERVAL=30 # seconds

echo "Starting repeated visits to: $URL"
echo "Visiting every $INTERVAL seconds. Press Ctrl+C to stop."

while true; do
    echo "----------------------------------------------------"
    echo "Visiting URL at $(date)"
    # Use curl to visit the URL.
    # -s: Silent mode, doesn't show progress meter or error messages.
    # -o /dev/null: Discards the output, as we only care about visiting.
    curl -s -k -o /dev/null "$URL"

    if [ $? -eq 0 ]; then
        echo "Successfully visited URL."
    else
        echo "Failed to visit URL. Check connectivity or URL correctness."
    fi

    echo "Waiting for $INTERVAL seconds..."
    sleep $INTERVAL
done