#!/bin/bash
# Script to clean up duplicate config files on Hostinger

echo "Cleaning up duplicate config files..."

# Remove duplicate config file from root directory
rm -f /home/u258651435/domains/olshacare.com/public_html/config_production.php

# Remove any other duplicate config files
rm -f /home/u258651435/domains/olshacare.com/public_html/config.php

# Ensure only the correct config file exists in core folder
echo "Config cleanup completed!"

# List remaining config files
echo "Remaining config files:"
find /home/u258651435/domains/olshacare.com/public_html -name "config*.php" -type f
