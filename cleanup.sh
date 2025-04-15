#!/bin/bash

# Set your bucket name here
BUCKET_NAME="jkr"
PREFIX="visitkanab/images"

# List all objects in the bucket and filter JPG files that are 58 bytes or less
./myenv/bin/aws s3api list-objects-v2 --bucket "$BUCKET_NAME" --prefix "$PREFIX/" --query "Contents[?Size<=`58` && ends_with(Key, '.jpg')].Key" --output text | while read -r key; do
  # Check if we have a valid file (the read could be empty)
  if [ -n "$key" ]; then
    # Delete the file from S3
    echo "Deleting: $key"
#    aws s3 rm "s3://$BUCKET_NAME/$key"
  fi
done

echo "Finished deleting small JPG files."

