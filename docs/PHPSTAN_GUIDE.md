# PHPStan Configuration & Usage Guide

## 🎯 Current Status
- **PHPStan Level:** 9 (maximum strictness)
- **Current Issues:** Baseline captured (1047 legacy warnings)
- **New Code:** Must pass PHPStan analysis
- **Memory Allocation:** 2GB for large codebase analysis

## 🚀 Quick Commands

### Run PHPStan Analysis
```bash
# Standard analysis (with baseline)
composer phpstan

# Production analysis (same as standard - both use baseline)
composer phpstan-clean

# Regenerate baseline (when fixing legacy issues)
composer phpstan-baseline
```

**Note:** All commands use 2GB memory allocation and include the baseline via the `includes` section. Both `phpstan` and `phpstan-clean` should return exit code 0 when successful.

### Check Specific Files
```bash
vendor/bin/phpstan analyze path/to/file.php
```

## 📁 Configuration Files

### Main Config (`.phpstan.neon`)
- PHPStan uses `.phpstan.neon` (with dot prefix) as default config
- Level 9 analysis with baseline included as file
- 2GB memory allocation via composer scripts
- Used by `composer phpstan`

### Production Config (`phpstan-production.neon`)
- Simplified configuration with baseline included as file
- Used by `composer phpstan-clean`
- Identical behavior to main config

### Baseline (`phpstan-baseline.neon`)
- Contains 1047 legacy issues that are acceptable for now
- Included via `includes` section in both configs
- Generated with `composer phpstan-baseline`
- **Do not manually edit** - regenerate when fixing legacy issues

### Bootstrap (`phpstan-bootstrap.php`)
- Defines constants for analysis
- WordPress environment setup

## 🔧 Best Practices

### For New Code
1. Always run PHPStan before committing
2. Fix all new errors (don't add to baseline)
3. Use proper type hints and DocBlocks

### For Existing Code
1. Baseline captures current acceptable issues
2. Fix real bugs when you find them
3. Don't waste time on type narrowing warnings

### Common Fixes
```php
// ❌ PHPStan error: expects string, int given
echo esc_html($count);

// ✅ Fixed: cast to string
echo esc_html((string)$count);

// ❌ PHPStan error: undefined variable
if ($some_condition) {
    $variable = 'value';
}
echo $variable; // might not be defined

// ✅ Fixed: initialize variable
$variable = '';
if ($some_condition) {
    $variable = 'value';
}
echo $variable;
```

## 🎨 Integration

### Pre-commit Hook (Optional)
Add to `.git/hooks/pre-commit`:
```bash
#!/bin/sh
composer phpstan-clean
if [ $? -ne 0 ]; then
    echo "PHPStan analysis failed. Fix errors before committing."
    exit 1
fi
```

### VS Code Integration
Install "PHPStan" extension for real-time analysis.

## 🛠️ Troubleshooting

### Memory Limit Issues
If you see "PHPStan process crashed because it reached configured PHP memory limit":
```bash
# Use the composer scripts (they include --memory-limit=2G)
composer phpstan

# Or run directly with higher memory
vendor/bin/phpstan analyze --memory-limit=3G
```

### Configuration Errors
- **"Found section 'ignoreErrors' in configuration, but corresponding extension is missing"**
  - Remove any `ignoreErrors` sections from config files
  - Use baseline instead for ignoring legacy issues

- **"Invalid configuration: Unexpected item 'parameters › X'"**
  - Check YAML indentation in `.phpstan.neon`
  - Ensure proper structure (parameters section properly formatted)

### Baseline Issues
- **"Baseline generated with 0 errors"** 
  - Run PHPStan without baseline first to see actual errors
  - Some errors cannot be baselined (fix these first)
  - Use `-vv` flag for verbose output: `vendor/bin/phpstan analyze -vv`

### Performance Tips
- Use `--no-progress` flag for cleaner output in CI
- Consider excluding large vendor directories if analysis is slow
- For local development, use `composer phpstan-clean` for faster analysis

## 🚨 When to Update Baseline

**Only regenerate baseline when:**
- Fixing legacy issues (reducing errors)
- Major refactoring with many changes
- Upgrading PHPStan version

**Never regenerate to hide new errors!**

## 📊 Error Categories

### Real Issues (Fix These)
- Type mismatches
- Undefined variables
- Missing methods/classes
- Logic errors

### Acceptable (In Baseline)
- Type narrowing warnings
- WordPress-specific quirks
- Legacy code patterns
- Over-strict analysis artifacts
