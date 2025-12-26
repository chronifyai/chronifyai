#!/bin/bash
# Force add JSON template example files to git
# Run this script from the plugin root directory

echo "================================================"
echo "Force Adding Mustache Example JSON Files"
echo "================================================"

cd templates

echo ""
echo "JSON files to add:"
ls -la *.json

echo ""
echo "Force adding JSON files..."
git add -f layout.json
git add -f test_connection_block.json
git add -f wizard_navigation.json
git add -f wizard_step1.json
git add -f wizard_step2.json
git add -f wizard_step3.json
git add -f wizard_step4.json

echo ""
echo "✅ JSON files added (forced)"
echo ""
echo "Files staged:"
git ls-files --stage | grep "\.json$"

echo ""
echo "================================================"
echo "Next steps:"
echo "================================================"
echo "1. git commit -m 'Add mustache example context files'"
echo "2. git push"
echo "================================================"
