# ChronifyAI Moodle Plugin - CI Configuration

## Grunt Tasks

This plugin **does not have a custom Gruntfile.js**.

Moodle's built-in Grunt configuration will handle all tasks:
- JavaScript linting (eslint)
- CSS linting (stylelint)
- AMD building (rollup)

## Configuration Files

The plugin provides these configuration files for Moodle's Grunt:
- `.eslintrc` - JavaScript linting rules
- `.stylelintrc.json` - CSS linting rules
- `.eslintignore` - Files to ignore in JS linting
- `.stylelintignore` - Files to ignore in CSS linting

## Expected CI Behavior

When `moodle-plugin-ci grunt` runs:
1. May show warning: "Task amd not found" (harmless)
2. Will run: `stylelint:css` (CSS linting)
3. Will run: `eslint:amd` (JavaScript linting)
4. Should exit with code 0 (success)

## If You See "Task stylelint not found"

This means a **Gruntfile.js exists** that shouldn't be there!

**Solution**: 
1. Make sure you're using version 2.1.4 or later
2. Verify no Gruntfile.js exists in the plugin directory
3. Clear any cached files from previous versions

## Version History

- v2.1.3 and earlier: Had problematic Gruntfile.js
- **v2.1.4+**: No Gruntfile.js (correct approach)
