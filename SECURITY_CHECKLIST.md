# Security Checklist
## Laravel Email Change Confirmation Package

This checklist helps ensure your implementation of the Laravel Email Change Confirmation package follows security best practices.

---

## 🔒 Essential Security Configuration

### 1. **Set Hash Secret (CRITICAL)**
```bash
# Add to your .env file
EMAIL_CHANGE_HASH_SECRET=your-random-secret-key-here
```

**Why:** Without this, email hashes use weak SHA-256 instead of secure HMAC.

**Requirements for EMAIL_CHANGE_HASH_SECRET:**
- **Minimum length**: 16 characters (but 32+ strongly recommended)
- **Maximum length**: No technical limit (64 characters is more than sufficient)
- **Allowed characters**: Any printable ASCII characters (a-z, A-Z, 0-9, symbols)
- **Forbidden characters**: None, but avoid characters that might cause shell/config issues
- **Recommended format**: Base64-encoded string for maximum entropy

**Generate a secure secret:**
```bash
# Recommended: 32-byte base64-encoded (44 characters)
php -r "echo 'EMAIL_CHANGE_HASH_SECRET=' . base64_encode(random_bytes(32)) . PHP_EOL;"

# Alternative: 64-character hex string
php -r "echo 'EMAIL_CHANGE_HASH_SECRET=' . bin2hex(random_bytes(32)) . PHP_EOL;"

# Alternative: 32-character alphanumeric
php -r "echo 'EMAIL_CHANGE_HASH_SECRET=' . substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 32)), 0, 32) . PHP_EOL;"
```

**Security Notes:**
- Never use predictable values like "secret", "password", or your app name
- Never commit this value to version control
- Rotate this secret periodically (quarterly recommended)
- Use different secrets for different environments (dev, staging, production)

### 2. **Configure Reasonable Expiration Time**
```php
// config/email-change-confirmation.php
'confirmation_email_expire_minutes' => 30, // Recommended: 30 minutes or less
```

**Why:** Shorter expiration reduces the window for potential attacks.

### 3. **Set Up Rate Limiting**
```php
// config/email-change-confirmation.php
'max_requests_per_hour' => 5, // Recommended: 5 or less
```

**Why:** Prevents email bombing and abuse.

### 4. **Configure Blocked Domains (Optional)**
```php
// config/email-change-confirmation.php
'blocked_domains' => [
    'tempmail.com',
    '10minutemail.com',
    'guerrillamail.com',
    // Add other disposable email domains
],
```

**Why:** Prevents use of temporary/disposable email addresses.

---

## 🛡️ Laravel Security Settings

### 1. **Ensure Proper Middleware**
The package uses these middleware by default:
```php
'middleware' => ['web', 'auth', 'signed'],
```

**Verify in your routes:**
- `web` - Provides CSRF protection and session handling
- `auth` - Ensures user is authenticated
- `signed` - Validates URL signatures

### 2. **HTTPS in Production**
```php
// config/app.php
'url' => 'https://yourdomain.com',

// Force HTTPS
'force_https' => true,
```

**Why:** Email confirmation links must be transmitted securely.

### 3. **Secure Session Configuration**
```php
// config/session.php
'secure' => true,        // HTTPS only
'http_only' => true,     // Prevent XSS
'same_site' => 'strict', // CSRF protection
```

---

## 📧 Email Security

### 1. **Verify Email Configuration**
```php
// config/mail.php
'from' => [
    'address' => 'noreply@yourdomain.com',
    'name' => 'Your App Name',
],
```

### 2. **Use Authenticated SMTP**
Avoid using `sendmail` or `mail` drivers in production:
```php
// .env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-server.com
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
```

---

## 🔍 Monitoring & Logging

### 1. **Enable Security Logging**
The package automatically logs security events. Ensure your logging is configured:

```php
// config/logging.php
'channels' => [
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'info',
        'days' => 30,
    ],
],
```

### 2. **Monitor These Events**
- Failed confirmation attempts
- Rate limit violations
- Invalid hash attempts
- Configuration warnings

### 3. **Set Up Alerts**
Consider setting up alerts for:
- Multiple failed attempts from same IP
- Unusual patterns in email change requests
- Configuration security warnings

---

## 🧪 Security Testing

### 1. **Test Authentication**
```bash
# Test that unauthenticated users cannot access endpoints
curl -X POST https://yourdomain.com/email-change/request
# Should return 401/403
```

### 2. **Test Rate Limiting**
```bash
# Make multiple rapid requests
for i in {1..10}; do
  curl -X POST https://yourdomain.com/email-change/request \
    -H "Authorization: Bearer $TOKEN" \
    -d "email=test$i@example.com"
done
# Should be rate limited after configured limit
```

### 3. **Test Invalid Signatures**
Try accessing confirmation URLs with modified parameters - should fail.

---

## 🚨 Security Incident Response

### 1. **If You Suspect Compromise**
1. **Immediately revoke** all pending email changes:
   ```php
   EmailChange::pending()->update(['change_denied_at' => now()]);
   ```

2. **Check logs** for suspicious activity:
   ```bash
   grep "Email change" storage/logs/laravel.log
   ```

3. **Rotate secrets**:
   - Generate new `EMAIL_CHANGE_HASH_SECRET`
   - Update application key if needed

### 2. **Regular Security Maintenance**
- **Weekly:** Review security logs
- **Monthly:** Update blocked domains list
- **Quarterly:** Review and test security configuration

---

## 🔧 Environment-Specific Configuration

### Development Environment
```bash
# .env.local or .env.dev
EMAIL_CHANGE_HASH_SECRET=dev_secret_key_here_32_chars_min
```

### Staging Environment
```bash
# .env.staging
EMAIL_CHANGE_HASH_SECRET=staging_different_secret_key_here
```

### Production Environment
```bash
# .env.production
EMAIL_CHANGE_HASH_SECRET=production_highly_secure_secret_key
```

**Important:** Each environment should have a unique `EMAIL_CHANGE_HASH_SECRET`. Never reuse secrets across environments.

---

## ⚠️ Common Configuration Mistakes

### ❌ What NOT to do:
```bash
# Too short (less than 16 characters)
EMAIL_CHANGE_HASH_SECRET=secret123

# Predictable/weak
EMAIL_CHANGE_HASH_SECRET=myappname_secret_key

# Same across all environments
EMAIL_CHANGE_HASH_SECRET=same_secret_everywhere

# Contains problematic characters for shell
EMAIL_CHANGE_HASH_SECRET=secret"with'quotes$and&symbols

# Empty or commented out
# EMAIL_CHANGE_HASH_SECRET=
```

### ✅ What TO do:
```bash
# Strong, unique, base64-encoded
EMAIL_CHANGE_HASH_SECRET=YWJjZGVmZ2hpams1bG1ub3BxcnN0dXZ3eHl6QUJDREVGR0hJSktMTU5PUFFSU1RVVldYWVo=

# Or hex-encoded
EMAIL_CHANGE_HASH_SECRET=a1b2c3d4e5f6789012345678901234567890abcdef1234567890abcdef123456

# Environment-specific and unique
EMAIL_CHANGE_HASH_SECRET=prod_2024_secure_hash_secret_v1_a1b2c3d4e5f6
```

---

## ✅ Security Checklist

**Before Going Live:**
- [ ] `EMAIL_CHANGE_HASH_SECRET` is set and secure (minimum 16 chars, 32+ recommended)
- [ ] `EMAIL_CHANGE_HASH_SECRET` is unique per environment (dev/staging/prod)
- [ ] `EMAIL_CHANGE_HASH_SECRET` uses strong entropy (base64/hex recommended)
- [ ] Expiration time is 30 minutes or less
- [ ] Rate limiting is configured (≤5 requests/hour)
- [ ] HTTPS is enforced in production
- [ ] Proper middleware is configured (`web`, `auth`, `signed`)
- [ ] Email sending is properly configured (SMTP with TLS)
- [ ] Security logging is enabled
- [ ] Blocked domains are configured (if needed)
- [ ] Security tests have been run
- [ ] Configuration warnings have been addressed

**Regular Maintenance:**
- [ ] Security logs are monitored
- [ ] Configuration warnings are addressed
- [ ] Dependencies are kept updated
- [ ] Security tests are run regularly

---

## 🔗 Additional Resources

- [Laravel Security Documentation](https://laravel.com/docs/security)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Checklist](https://github.com/Checkmarx/laravel-security-checklist)

---

## 🆘 Getting Help

If you discover a security vulnerability:

1. **DO NOT** create a public issue
2. Email security concerns to the package maintainer
3. Include detailed information about the vulnerability
4. Allow time for the issue to be addressed before public disclosure

---

**Remember:** Security is an ongoing process, not a one-time setup. Regularly review and update your security configuration as your application evolves.