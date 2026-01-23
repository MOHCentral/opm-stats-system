#!/bin/bash
# ==============================================================================
# OpenMOHAA Stats - SMF Package Builder
# ==============================================================================
# Zips the plugin content into a valid SMF package.

PLUGIN_DIR="opm-stats-smf-integration/smf-plugins/mohaa_stats_core"
OUTPUT_DIR="release"
VERSION="1.0.0"
PACKAGE_NAME="mohaa_stats_core_v${VERSION}.zip"

# Ensure output dir exists
mkdir -p $OUTPUT_DIR

echo "Building SMF Package: $PACKAGE_NAME"
echo "Source: $PLUGIN_DIR"

if [ ! -d "$PLUGIN_DIR" ]; then
    echo "Error: Plugin directory not found at $PLUGIN_DIR"
    exit 1
fi

# Change to plugin dir to zip contents (not the folder itself)
cd $PLUGIN_DIR

# Zip recursively
zip -r "../../../$OUTPUT_DIR/$PACKAGE_NAME" . -x "*.git*" -x "*.DS_Store*"

cd - > /dev/null

echo "Success! Package created at:"
echo "$OUTPUT_DIR/$PACKAGE_NAME"
echo "You can now upload this file via the SMF Package Manager."
