#!/bin/bash

# GoalieTron Plugin Packaging Script
# Creates a clean zip file for WordPress plugin distribution

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Get the directory where the script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Plugin name and version
PLUGIN_NAME="goalietron"
VERSION=$(grep "Version:" goalietron.php | sed 's/.*Version: //' | tr -d '\r')

# Output filename with version
OUTPUT_FILE="${PLUGIN_NAME}-${VERSION}.zip"

echo -e "${GREEN}Creating GoalieTron Plugin Package${NC}"
echo "Version: $VERSION"
echo "Output: $OUTPUT_FILE"
echo ""

# Check if required files exist
echo "Checking required files..."
REQUIRED_FILES=(
    "goalietron.php"
    "PatreonClient.php"
    "readme.txt"
    "views/widget-form.html"
    "_inc/goalietron.js"
)

ALL_FILES_EXIST=true
for file in "${REQUIRED_FILES[@]}"; do
    if [ ! -f "$file" ]; then
        echo -e "${RED}✗ Missing required file: $file${NC}"
        ALL_FILES_EXIST=false
    else
        echo -e "${GREEN}✓ Found: $file${NC}"
    fi
done

if [ "$ALL_FILES_EXIST" = false ]; then
    echo -e "${RED}Error: Some required files are missing. Please check your installation.${NC}"
    exit 1
fi

echo ""
echo "Creating zip package..."

# Remove old zip if exists
if [ -f "$OUTPUT_FILE" ]; then
    rm "$OUTPUT_FILE"
    echo "Removed existing $OUTPUT_FILE"
fi

# Create the zip file with all necessary files
zip -r "$OUTPUT_FILE" \
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
    -x "*.DS_Store" \
    -x "*/.DS_Store" \
    -x "*/Thumbs.db" \
    -x "*/.git/*" \
    -x "*/.gitignore" \
    -x "*/node_modules/*" \
    -x "*/.idea/*" \
    -x "*/.vscode/*" \
    -x "*/tests/*" \
    -x "*/test-*" \
    -x "*.log" \
    -x "*.tmp" \
    -x "*.cache" \
    -x "patreon-goals.json" \
    -x "test-pages/*" \
    -x "test-page-generator.php" \
    -x "debug-*.php" \
    -x "create-plugin-zip.sh" \
    -q

# Check if zip was created successfully
if [ -f "$OUTPUT_FILE" ]; then
    # Get file size
    SIZE=$(ls -lh "$OUTPUT_FILE" | awk '{print $5}')
    echo -e "${GREEN}✓ Successfully created $OUTPUT_FILE ($SIZE)${NC}"
    echo ""
    echo "Package contents:"
    echo "- Main plugin file (goalietron.php)"
    echo "- Patreon client library (PatreonClient.php)"
    echo "- CLI tool (patreon-cli.php)"
    echo "- Block support (block.json, block-editor.js, block-render.php)"
    echo "- Classic widget enabler (enable-classic-widgets.php)"
    echo "- All views, assets, and CSS/JS files"
    echo ""
    echo -e "${YELLOW}Installation instructions:${NC}"
    echo "1. Upload $OUTPUT_FILE through WordPress admin (Plugins → Add New → Upload)"
    echo "2. Activate the plugin"
    echo "3. For block themes: Add 'GoalieTron Goal Display' block in Site Editor"
    echo "4. For classic themes: Add widget in Appearance → Widgets"
    echo "5. For widget support in block themes: Activate 'Enable Classic Widgets Menu' plugin"
else
    echo -e "${RED}✗ Error: Failed to create zip file${NC}"
    exit 1
fi

# Optional: Create a development version without CLI tools
read -p "Create a lite version without CLI tools? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    LITE_FILE="${PLUGIN_NAME}-${VERSION}-lite.zip"
    echo "Creating lite version: $LITE_FILE"
    
    zip -r "$LITE_FILE" \
        goalietron.php \
        PatreonClient.php \
        readme.txt \
        LICENSE \
        views/ \
        _inc/ \
        assets/ \
        block.json \
        block-editor.js \
        block-render.php \
        -x "*.DS_Store" \
        -x "*/.DS_Store" \
        -x "*/Thumbs.db" \
        -q
    
    if [ -f "$LITE_FILE" ]; then
        SIZE=$(ls -lh "$LITE_FILE" | awk '{print $5}')
        echo -e "${GREEN}✓ Created lite version: $LITE_FILE ($SIZE)${NC}"
    fi
fi

echo ""
echo -e "${GREEN}Done!${NC}"