#!/bin/bash

# GoalieTron packaging script that respects .distignore

cd "$(dirname "$0")"

# Remove old zip if exists
rm -f goalietron-plugin.zip

# Create a temporary directory for the plugin
TEMP_DIR=$(mktemp -d)
PLUGIN_DIR="$TEMP_DIR/goalietron"

# Copy all files to temp directory
echo "Copying files..."
cp -r . "$PLUGIN_DIR"

# Remove files based on .distignore
if [ -f .distignore ]; then
    echo "Applying .distignore rules..."
    cd "$PLUGIN_DIR"
echo $PLUGIN_DIR

    # Read .distignore and remove files/directories
    while IFS= read -r pattern || [ -n "$pattern" ]; do
        # Skip empty lines and comments
        if [[ -z "$pattern" ]] || [[ "$pattern" =~ ^# ]]; then
            continue
        fi
        
        # Remove matching files/directories
        find . -name "$pattern" -exec rm -rf {} + 2>/dev/null || true
    done < $PLUGIN_DIR/.distignore
    
    cd - > /dev/null
fi

CURDIR=$PWD 

# Create the zip file
echo "Creating plugin package..."
cd "$TEMP_DIR"
zip -r goalietron-plugin.zip goalietron/ -x "*.DS_Store" -x "*/.DS_Store" -x "*/.*"

mv goalietron-plugin.zip $CURDIR

# Clean up
rm -rf "$TEMP_DIR"

echo "Created: goalietron-plugin.zip"
