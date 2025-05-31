#!/bin/bash

# Simple GoalieTron packaging script - no parameters or prompts

cd "$(dirname "$0")"

# Remove old zip if exists
rm -f goalietron-plugin.zip

# Create zip with all plugin files
zip -r goalietron-plugin.zip \
    goalietron.php \
    PatreonClient.php \
    patreon-cli.php \
    readme.txt \
    LICENSE \
    views/ \
    _inc/ \
    assets/ \
    block.json \
    block-editor.js \
    block-render.php \
    enable-classic-widgets.php \
    example-patreon-goals.json \
    -x "*.DS_Store" \
    -x "*/.DS_Store"

echo "Created: goalietron-plugin.zip"