#!/bin/bash

for file in ./storage/gpx/*.gpx; do
  filename=$(basename "$file" .gpx)
  output="./storage/geojson/${filename}.geojson"

  if [ -f "$output" ]; then
    echo "Skipping $file — $output already exists."
  else
    echo "Converting $file → $output"
    ./node_modules/@mapbox/togeojson/togeojson "$file" > "$output"
  fi
done
