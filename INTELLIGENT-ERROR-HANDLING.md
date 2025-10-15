# 🧠 Intelligent Error Handling System

## Overview

The plugin now features an intelligent error handling system that automatically manages API key failures, reduces wasted time on timeouts, and provides detailed feedback about errors.

## Key Features

### 1. **Temporary Key Blacklisting** 🚫

Keys that fail are temporarily blacklisted based on the error type, preventing repeated attempts that would waste time.

**Blacklist Durations:**
| Error Type | Duration | Reason |
|------------|----------|--------|
| **Quota Exceeded** | 1 hour | Google quota resets hourly |
| **Timeout** | 5 minutes | Network issues may be temporary |
| **API Overloaded** | 3 minutes | Google API recovers quickly |
| **Invalid Key** | 24 hours | Key likely permanently invalid |
| **Network Error** | 5 minutes | Local network may recover |

**Benefits:**
- ⚡ **No wasted time** on keys that will fail
- 🔄 **Automatic recovery** when blacklist expires
- 📊 **Visible in admin panel** (API Keys page)

### 2. **Exponential Backoff Retry** 🔄

For temporary errors (API overloaded), the system automatically retries with increasing delays:

```
Attempt 1: Immediate
Attempt 2: Wait 2 seconds
Attempt 3: Wait 4 seconds
```

**Retryable Errors:**
- ✅ "The model is overloaded. Please try again later."
- ✅ Temporary API congestion

**Non-Retryable Errors:**
- ❌ Quota exceeded (blacklisted instead)
- ❌ Invalid API key (blacklisted instead)
- ❌ Timeout (blacklisted instead)

### 3. **Network Issue Detection** 🌐

Detects network problems early to avoid wasting time on all keys:

**Logic:**
- If **3 consecutive keys** fail with timeout errors
- System recognizes this as a **network problem**
- Stops trying remaining keys immediately
- Shows clear error message: *"Network connectivity issue detected"*

**Before (Without Detection):**
```
Key 1: Timeout after 60s ❌
Key 2: Timeout after 60s ❌
Key 3: Timeout after 60s ❌
Key 4: Timeout after 60s ❌
Key 5: Timeout after 60s ❌
Key 6: Timeout after 60s ❌
Total time wasted: 6 minutes!
```

**After (With Detection):**
```
Key 1: Timeout after 60s ❌
Key 2: Timeout after 60s ❌
Key 3: Timeout after 60s ❌
⚠️ Network issue detected! Stopping.
Total time: 3 minutes (saved 3 minutes!)
```

### 4. **Smart Error Categorization** 🏷️

The system automatically categorizes errors:

| Category | Error Messages Detected | Action |
|----------|------------------------|--------|
| **Quota Exceeded** | "exceeded your current quota", "quota" | Blacklist 1 hour |
| **Timeout** | "timeout", "timed out", "cURL error 28" | Blacklist 5 minutes |
| **Overloaded** | "overloaded" | Retry with backoff, then blacklist 3 min |
| **Invalid Key** | "invalid", "not valid" | Blacklist 24 hours |
| **Network** | "cURL", "connection" | Blacklist 5 minutes |

### 5. **Detailed Error Logging** 📝

Enhanced logging provides clear information:

**Example Logs:**
```log
[timestamp] AMO: Key #3 failed (type: timeout): AIzaSyAkvm... - Error: cURL error 28
[timestamp] AMO: Key #3 blacklisted for 300 seconds due to: timeout
[timestamp] AMO: Key #5 skipped (blacklisted for 2847 more seconds): AIzaSyCf8k...
[timestamp] AMO: Network issue detected (3+ consecutive timeouts). Stopping key rotation.
[timestamp] AMO: Article generated successfully with key #2: AIzaSyAh_S...
```

### 6. **Admin Dashboard Visibility** 📊

**New Section: "Geçici Olarak Kara Listeye Alınan Anahtarlar"**

Located on the **API Anahtarları** page, shows:
- 🔑 Which keys are blacklisted
- ⏱️ Remaining time until they're tried again
- 🏷️ Error type (Quota, Timeout, Overloaded, etc.)
- 📝 Error message details

**Auto-refreshes** when you reload the page to show current status.

## How It Works

### Article Generation Flow

```
1. Get keyword from queue
   ↓
2. Load all active API keys
   ↓
3. For each key:
   a. Check if blacklisted → Skip if yes
   b. Try to generate content with retry logic
   c. On success → Clear blacklist, return article ✅
   d. On failure → Categorize error, blacklist key
   ↓
4. If 3+ timeouts → Stop (network issue)
   ↓
5. If all keys fail → Return detailed error report
```

### Blacklist Management

**Storage:** WordPress transient (`amo_key_blacklist`)
**Lifetime:** 24 hours (auto-cleanup)
**Structure:**
```php
[
    key_id => [
        'error_type' => 'timeout',
        'error_message' => 'cURL error 28: Operation timed out',
        'expiry' => timestamp,
        'blacklisted_at' => '2025-01-15 10:30:00'
    ]
]
```

**Automatic Expiry:**
- Keys are automatically checked on each request
- Expired entries are removed from blacklist
- No manual cleanup required

## Benefits of This System

### Time Savings

**Scenario from your logs:**
```
Old System:
- 4 timeouts × 60s = 240 seconds (4 minutes)
- 2 quota failures × 2s = 4 seconds
Total: 244 seconds per attempt

New System:
- 3 timeouts × 60s = 180 seconds (network issue detected, stops)
- Subsequent attempts skip blacklisted keys (instant)
Total: 180 seconds first attempt, then 0-2s for subsequent attempts
```

**Savings: ~60-240 seconds per article!**

### Improved Reliability

1. **No repeated failures** on same key
2. **Faster detection** of systemic issues (network, quota)
3. **Better resource utilization** (only use working keys)
4. **Automatic recovery** when issues resolve

### Better Debugging

1. **Clear categorization** of error types
2. **Visible blacklist** in admin panel
3. **Detailed logs** with error context
4. **User-friendly messages** for common issues

## Monitoring & Troubleshooting

### Check Blacklist Status

**Admin Panel:**
1. Go to **Makale Oluşturucu → API Anahtarları**
2. Scroll down to **"Geçici Olarak Kara Listeye Alınan Anahtarlar"**
3. View which keys are blacklisted and when they'll be tried again

**Via Debug Log:**
```log
Look for:
- "Key #X blacklisted for Y seconds due to: [error_type]"
- "Key #X skipped (blacklisted for Y more seconds)"
```

### Clear Blacklist Manually

**Method 1: Wait for expiry** (recommended)
- Keys automatically recover after blacklist duration

**Method 2: Via PHP** (if urgent)
```php
// In WordPress Admin → Tools → Site Health → Debug Information
// Or wp-admin/admin.php?page=debug with custom code:
delete_transient('amo_key_blacklist');
```

**Method 3: Test a key** (triggers re-check)
- Go to API Anahtarları page
- Click "Test" button on the key
- If it passes, it's immediately available

### Understanding Error Messages

**"All API keys failed. Most keys exceeded their quota."**
- **Cause:** Multiple keys hit Google's free tier quota
- **Solution:** Add more keys or wait for quota reset (hourly/daily)
- **Note:** Keys blacklisted for 1 hour, will auto-retry after

**"Network connectivity issue detected."**
- **Cause:** 3+ consecutive timeout errors
- **Solution:** Check internet connection, firewall, antivirus
- **Note:** Keys blacklisted for 5 minutes, will auto-retry after

**"Google API is overloaded. Try again in a few minutes."**
- **Cause:** Google's API is temporarily congested
- **Solution:** System auto-retries with backoff, then blacklists for 3 minutes
- **Note:** Wait 3-5 minutes and try again

**"X key(s) were temporarily blacklisted and skipped."**
- **Cause:** Keys failed recently and are in cooldown period
- **Solution:** Normal behavior! Check blacklist status in admin panel
- **Note:** Keys will auto-recover when blacklist expires

## Configuration

### Adjust Blacklist Durations

Edit `class-amo-api-handler.php`:

```php
// Around line 20-25
const BLACKLIST_QUOTA = 3600;      // 1 hour (default)
const BLACKLIST_TIMEOUT = 300;      // 5 minutes (default)
const BLACKLIST_OVERLOADED = 180;   // 3 minutes (default)
const BLACKLIST_INVALID = 86400;    // 24 hours (default)
```

**Recommendations:**
- **Don't reduce** BLACKLIST_QUOTA (Google's quota limit is hourly)
- **Increase** BLACKLIST_TIMEOUT if you have persistent network issues
- **Decrease** BLACKLIST_OVERLOADED if Google API is frequently congested in your region

### Adjust Retry Settings

```php
// Around line 27-28
const MAX_RETRIES = 2;              // Number of retries (default: 2)
const INITIAL_RETRY_DELAY = 2;      // Initial delay in seconds (default: 2)
```

**Retry formula:** Delay = INITIAL_RETRY_DELAY × 2^(attempt_number)
- Attempt 1: 2s
- Attempt 2: 4s
- Attempt 3: 8s (if MAX_RETRIES increased to 3)

### Network Issue Threshold

Currently hardcoded to 3 consecutive timeouts. To change:

Edit line ~461 in `class-amo-api-handler.php`:
```php
if ($timeout_count >= 3) { // Change 3 to your preferred threshold
```

## Best Practices

### 1. **Add Multiple Keys** 🔑
- **Minimum:** 3-5 keys
- **Recommended:** 10+ keys for high-volume usage
- **Why:** Distributes quota, reduces blacklist impact

### 2. **Monitor Blacklist** 👀
- Check admin panel regularly
- If many keys blacklisted → Add more keys
- If all timeout → Check network

### 3. **Use Different Google Accounts** 📧
- Each account gets separate quota
- Reduces risk of all keys failing simultaneously

### 4. **Review Logs** 📜
- Check `wp-content/debug.log` for patterns
- Look for recurring error types
- Adjust strategy based on common failures

### 5. **Test New Keys** ✅
- Always test new keys immediately after adding
- Don't wait for production use to discover invalid keys
- Remove permanently failing keys

## Technical Details

### Error Detection Logic

**Timeout Detection:**
```php
strpos($error_lower, 'timeout') !== false 
|| strpos($error_lower, 'timed out') !== false
|| strpos($error_lower, 'curl error 28') !== false
```

**Quota Detection:**
```php
strpos($error_lower, 'quota') !== false 
|| strpos($error_lower, 'exceeded') !== false
```

**Overload Detection:**
```php
strpos($error_lower, 'overloaded') !== false
```

### Blacklist Check Performance

**Transient Cache:**
- Single database query per request
- Cached in WordPress object cache
- Minimal performance impact

**Check Time:**
- ~0.001s per key check
- Negligible compared to 60s API timeout

### Memory Usage

**Blacklist Storage:**
- ~200 bytes per blacklisted key
- Maximum 10 KB for 50 keys
- Negligible memory footprint

---

## Support & Feedback

If you encounter issues or have suggestions for improving the error handling system:

1. **Check Debug Log:** `wp-content/debug.log`
2. **Check Blacklist:** Admin → API Anahtarları
3. **Review Error Types:** See categorization above
4. **Adjust Thresholds:** Based on your specific use case

**Last Updated:** October 15, 2025
**Version:** 2.0 (Intelligent Error Handling)
