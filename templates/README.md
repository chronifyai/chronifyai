# Mustache Template Examples

This directory contains Mustache templates and their example context files.

## Important: JSON Example Files

**CRITICAL**: The `.json` files in this directory MUST be committed to the repository.

These files provide example contexts for Mustache template validation and are required by the `moodle-plugin-ci mustache` linter.

### Files Required:
- layout.json
- test_connection_block.json
- wizard_navigation.json
- wizard_step1.json
- wizard_step2.json
- wizard_step3.json
- wizard_step4.json

### If JSON files are being ignored:

1. Check your repository's root `.gitignore` file
2. If it contains `*.json`, add an exception:
   ```
   # Allow template example files
   !local/chronifyai/templates/*.json
   ```

3. Force add the files:
   ```bash
   cd local/chronifyai/templates
   git add -f *.json
   git commit -m "Add mustache example contexts"
   ```

### JSON File Format

Each JSON file should follow this structure:
```json
{
    "examples": [
        {
            "variable_name": "example_value"
        }
    ]
}
```

This format is required by Moodle's mustache linter.
