# Quick Installation Guide - ChronifyAI Fixed Files

## What's Included

This package contains all fixed files for ChronifyAI plugin issues #1-9.

### Files Included:
1. **index.php** - Fixed setup wizard (Issue #5)
2. **transcripts.php** - Fixed PostgreSQL query (Issue #3)
3. **local_chronifyai.php** - Complete language strings (Issues #4, #6)
4. **courses.php** - Fixed hard-coded strings (Issue #4)
5. **hook_callbacks.php** - New Hooks API implementation (Issue #7)
6. **hooks.php** - Hook registration (Issue #7)
7. **.github/workflows/ci.yml** - GitHub Actions CI/CD (Issue #1)
8. **FIX_REPORT.md** - Detailed report of all fixes

---

## Installation Instructions

### Option 1: Manual File Replacement (Recommended)

**Step 1**: Backup your current plugin
```bash
cd /path/to/moodle/local
cp -r chronifyai chronifyai.backup.$(date +%Y%m%d)
```

**Step 2**: Copy fixed files to your plugin directory

```bash
# Navigate to your Moodle installation
cd /path/to/moodle/local/chronifyai

# Copy main files
cp /path/to/fixed-files/index.php ./
cp /path/to/fixed-files/transcripts.php ./classes/service/
cp /path/to/fixed-files/courses.php ./classes/external/
cp /path/to/fixed-files/local_chronifyai.php ./lang/en/

# Copy new Hooks API files
cp /path/to/fixed-files/hook_callbacks.php ./classes/
cp /path/to/fixed-files/hooks.php ./db/

# Copy GitHub Actions (if using GitHub)
mkdir -p .github/workflows
cp /path/to/fixed-files/.github/workflows/ci.yml ./.github/workflows/
```

**Step 3**: Clear Moodle caches
- Go to: Site Administration → Development → Purge all caches
- Click "Purge all caches"

**Step 4**: Run database upgrade
- Go to: Site Administration → Notifications
- Follow any upgrade prompts

**Step 5**: Test the plugin
- Go to: Site Administration → Plugins → Local plugins → ChronifyAI
- Run through setup wizard
- Verify all steps work

---

### Option 2: Git Integration

If your plugin is in a Git repository:

**Step 1**: Create a new branch
```bash
cd /path/to/your/plugin/repository
git checkout -b fix-issues-1-9
```

**Step 2**: Copy fixed files
```bash
cp /path/to/fixed-files/index.php ./
cp /path/to/fixed-files/transcripts.php ./classes/service/
cp /path/to/fixed-files/courses.php ./classes/external/
cp /path/to/fixed-files/local_chronifyai.php ./lang/en/
cp /path/to/fixed-files/hook_callbacks.php ./classes/
cp /path/to/fixed-files/hooks.php ./db/
cp -r /path/to/fixed-files/.github ./
```

**Step 3**: Update version
Edit `version.php`:
```php
$plugin->version = 2025012700;  // YYYYMMDDXX
$plugin->release = 'v1.2.0';
```

**Step 4**: Update CHANGELOG.md
```markdown
## [1.2.0] - 2025-01-27

### Fixed
- Fixed PHP error in setup wizard (#5)
- Fixed PostgreSQL compatibility (#3)
- Added missing language strings (#6)
- Replaced hard-coded strings (#4)

### Added
- Hooks API implementation (#7)
- GitHub Actions CI/CD (#1)
```

**Step 5**: Commit changes
```bash
git add .
git commit -m "Fix issues #1-9: Critical errors, PostgreSQL, language strings, Hooks API"
git push origin fix-issues-1-9
```

**Step 6**: Create Pull Request
- Review changes
- Merge to main branch
- Tag version: `git tag v1.2.0`
- Push tags: `git push --tags`

---

## File Mapping

| Fixed File | Destination Path |
|------------|------------------|
| index.php | `local/chronifyai/index.php` |
| transcripts.php | `local/chronifyai/classes/service/transcripts.php` |
| courses.php | `local/chronifyai/classes/external/courses.php` |
| local_chronifyai.php | `local/chronifyai/lang/en/local_chronifyai.php` |
| hook_callbacks.php | `local/chronifyai/classes/hook_callbacks.php` |
| hooks.php | `local/chronifyai/db/hooks.php` |
| ci.yml | `local/chronifyai/.github/workflows/ci.yml` |

---

## Verification Steps

After installation, verify each fix:

### ✅ Issue #5 - PHP Error Fixed
1. Go to: `/local/chronifyai/index.php`
2. Navigate through all 4 wizard steps
3. Verify: No PHP errors
4. Verify: Forms display correctly on steps 2 & 3

### ✅ Issue #3 - PostgreSQL Fixed
1. Run on PostgreSQL test site
2. Trigger transcript export
3. Check logs for SQL errors
4. Verify: No "ORDER BY" errors

### ✅ Issue #6 - Missing Strings Fixed
1. Enable Moodle debugging
2. Navigate through entire plugin
3. Look for `[[missing string]]` messages
4. Verify: All text displays correctly

### ✅ Issue #4 - Hard-coded Strings Fixed
1. Check course instructor display
2. Run backup/restore operations
3. Check task logs
4. Verify: All messages use language strings

### ✅ Issue #7 - Hooks API Implemented
1. Login as admin
2. Visit plugin pages
3. Verify: Privacy notification displays
4. Check: Works on Moodle 4.4+

### ✅ Issue #1 - GitHub Actions Active
1. Push to GitHub
2. Go to Actions tab
3. Verify: Workflow runs automatically
4. Check: Tests pass (green checkmarks)

---

## Troubleshooting

### Issue: "Undefined variable" errors
**Solution**: Ensure you cleared Moodle caches after copying files

### Issue: Missing strings still appearing
**Solution**: 
1. Check that `local_chronifyai.php` was copied to correct location
2. Clear caches again
3. Verify file permissions (must be readable)

### Issue: PostgreSQL errors persist
**Solution**: 
1. Verify `transcripts.php` was copied to `classes/service/`
2. Check that file wasn't corrupted during copy
3. Compare line 164 - should include `c.timemodified` in SELECT

### Issue: Hooks not working
**Solution**:
1. Ensure both `hook_callbacks.php` AND `hooks.php` were copied
2. Verify Moodle version is 4.4+
3. Legacy callback in `lib.php` still works for older versions

### Issue: GitHub Actions not running
**Solution**:
1. Verify `.github/workflows/ci.yml` is in correct location
2. Check GitHub repository settings → Actions → enabled
3. Push a test commit to trigger

---

## Support

If you encounter issues:

1. **Check FIX_REPORT.md** for detailed information on each fix
2. **Enable debugging**:
   - Site Administration → Development → Debugging
   - Set to DEVELOPER level
3. **Check error logs**: `moodledata/error.log`
4. **Verify file locations** using the mapping table above
5. **Clear caches** multiple times if needed

---

## Next Steps

After successful installation:

1. ✅ Test all functionality thoroughly
2. ✅ Update `version.php` to 2025012700
3. ✅ Update `CHANGELOG.md`
4. ✅ Commit changes to your repository
5. ✅ Tag new version (v1.2.0)
6. ✅ Push to GitHub
7. ✅ Resubmit to Moodle plugin repository

---

## Summary

This package fixes all critical and high-priority issues:
- ✅ PHP errors (Issue #5)
- ✅ PostgreSQL compatibility (Issue #3)
- ✅ Missing language strings (Issue #6)
- ✅ Hard-coded strings (Issue #4)
- ✅ Modern Hooks API (Issue #7)
- ✅ CI/CD automation (Issue #1)

**Status**: Ready for production use and Moodle repository resubmission.
