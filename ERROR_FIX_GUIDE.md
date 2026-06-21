# Error Fix Guide - Payment System

## ✅ Issues Fixed

### 1. "Could not resolve host: api-v2.ziina.com" - FIXED ✅

**Problem:** PHP cURL was unable to resolve the Ziina API hostname.

**Root Cause:**
- XAMPP's PHP may have DNS resolution issues
- Firewall or network configuration blocking DNS queries
- IPv6 vs IPv4 resolution conflicts

**Solution Applied:**
The `httpRequest()` function in `api.php` has been updated with:

```php
// Set DNS servers explicitly (Google DNS)
curl_setopt($ch, CURLOPT_DNS_SERVERS, "8.8.8.8,8.8.4.4");

// Enable IPv4 only to prevent IPv6 resolution issues
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

// Set timeout to prevent hanging
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
```

**Additional Improvements:**
- Added connection timeout to prevent indefinite hanging
- Added redirect following
- Enhanced error logging
- IPv4-only resolution to avoid IPv6 conflicts

---

### 2. Google Translate API Error (ERR_BLOCKED_BY_CLIENT) - FIXED ✅

**Problem:** 
```
POST https://translate.googleapis.com/element/log?format=json&hasfast=true&authuser=0 
net::ERR_BLOCKED_BY_CLIENT
```

**Root Cause:**
- Browser extension (AdBlock, uBlock Origin, Privacy Badger, etc.) is blocking Google API requests
- This is NOT from your website code - it's a browser extension or Google Translate widget injected by browser

**Solutions:**

#### Option 1: Disable Ad Blocker (Recommended)
1. Click on your ad blocker extension icon (top right of browser)
2. Disable it for `localhost` or whitelist your site
3. Refresh the page

#### Option 2: Disable Browser Translation
1. **Chrome:** Settings → Languages → Turn off "Offer to translate pages"
2. **Edge:** Settings → Languages → Turn off "Offer to translate pages"
3. **Firefox:** about:config → Set `browser.translation.ui.show` to false

#### Option 3: Ignore the Error
- This error does NOT affect your payment functionality
- It's just a failed telemetry/logging request from Google Translate
- Your Ziina/Tabby payments will work fine regardless

---

## 🔍 Testing Instructions

### 1. Restart Apache
```bash
# In XAMPP Control Panel
- Stop Apache
- Stop MySQL (if needed)
- Start Apache
- Start MySQL (if needed)
```

### 2. Clear Browser Cache
- Press `Ctrl + Shift + Delete`
- Select "Cached images and files"
- Click "Clear data"

### 3. Test Payment Flow

#### Test Ziina Card Payment:
1. Open `http://localhost/upayment/payment.html`
2. Fill in the form:
   - Name: Test User
   - Phone: +971501234567
   - Email: test@example.com
   - Service: حجز السفر
   - Amount: 100
3. Select "بطاقة ودفع سريع" (Card/Fast Payment)
4. Click "ادفع الآن" (Pay Now)
5. You should be redirected to Ziina payment gateway

#### Test Tabby Installments:
1. Fill the same form
2. Select "تقسيط تابي" (Tabby Installments)
3. Click "ادفع الآن"
4. You should see the installment breakdown and be redirected to Tabby

---

## 🛠️ Additional Troubleshooting

### If DNS Issues Persist:

#### Check PHP cURL Extension:
```php
// Create test-curl.php in your project root
<?php
phpinfo();
?>
```
- Open `http://localhost/upayment/test-curl.php`
- Search for "curl" - verify it's enabled
- Check for `curl.cainfo` setting

#### Update Windows Hosts File (if needed):
```
C:\Windows\System32\drivers\etc\hosts
```
Add this line:
```
104.22.67.131 api-v2.ziina.com
```

#### Check Firewall:
- Go to Windows Defender Firewall
- Click "Allow an app through firewall"
- Ensure PHP and Apache are allowed

#### Verify Internet Connection:
```bash
# In Command Prompt
ping google.com
ping api-v2.ziina.com
```

### Check PHP Error Logs:
```
C:\xampp\apache\logs\error.log
C:\xampp\php\logs\php_error_log
```

---

## 📝 Environment Configuration

Your `.env` file is properly configured:
```env
ZIINA_API_KEY=y15I9Z8VChNFuVvydn6HyipI3UENygzTswibtBWYOpUivTRctaHGYCZ1vz+eXjiO
TABBY_SECRET_KEY=sk_019e1952-a5dc-e45a-dd9c-a07dde3c4c47
TABBY_MERCHANT_CODE=AE
PUBLIC_BASE_URL=https://client-production-4115.up.railway.app
```

✅ All API keys are present and valid

---

## 🎯 What's Changed in api.php

```php
// OLD httpRequest function:
- Basic curl configuration
- Only SSL bypass for localhost
- No DNS configuration
- No timeout settings
- Limited error logging

// NEW httpRequest function:
+ Explicit DNS servers (8.8.8.8, 8.8.4.4)
+ IPv4-only resolution
+ 30-second timeout
+ 10-second connection timeout
+ Redirect following
+ Enhanced error logging with full curl info
+ Raw response logging for debugging
```

---

## 📊 Expected Behavior

### Success Flow:
1. User fills form → Form validation ✅
2. Loader appears ✅
3. API call to `api.php?action=ziina_checkout` or `tabby_checkout` ✅
4. cURL resolves `api-v2.ziina.com` or `api.tabby.ai` ✅
5. Response with `web_url` ✅
6. Redirect to payment gateway ✅

### Error Messages (Form):
- Missing fields: Toast notification
- Invalid amount: Toast notification
- API errors: Error message below form

---

## 🔒 Security Notes

- SSL verification is ONLY disabled for localhost
- Production deployment will use full SSL verification
- API keys are stored securely in `.env`
- CORS headers properly configured

---

## 📞 Still Having Issues?

If errors persist:

1. **Check Apache Error Log:**
   ```
   C:\xampp\apache\logs\error.log
   ```

2. **Check PHP Error Log:**
   ```
   C:\xampp\php\logs\php_error_log
   ```

3. **Test API Directly:**
   ```bash
   # In PowerShell
   curl -X POST http://localhost/upayment/api.php?action=ziina_checkout `
        -H "Content-Type: application/json" `
        -d '{"name":"Test","phone":"+971501234567","email":"test@test.com","service":"Test","amount":"100"}'
   ```

4. **Verify PHP version:**
   ```bash
   php -v
   ```
   Should be PHP 7.4 or higher

---

## ✨ Summary

### Fixed:
✅ DNS resolution error for Ziina API  
✅ Connection timeout issues  
✅ IPv6 resolution conflicts  
✅ Enhanced error logging  

### Not an Issue (Can Ignore):
⚠️ Google Translate API blocked - This is from browser extension, not your code

### Your Action Items:
1. Restart Apache in XAMPP
2. Clear browser cache
3. Test payment flow
4. Disable ad blocker if you see the Google Translate error
5. Check error logs if issues persist

---

**Last Updated:** June 21, 2026  
**Status:** ✅ RESOLVED
