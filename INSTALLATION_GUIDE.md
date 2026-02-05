# 🚀 Quick Installation Guide

## Prerequisites
- PHP 7.4+
- MySQL 5.7+
- Apache with mod_rewrite
- Web browser

## Installation Steps

### 1️⃣ Setup Database

```bash
# Login to MySQL
mysql -u root -p

# Create database
CREATE DATABASE undangan_online CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Exit MySQL
exit;

# Import schema
mysql -u root -p undangan_online < database.sql
```

### 2️⃣ Configure Application

Edit `config/config.php`:
```php
define('ENVIRONMENT', 'development');
define('BASE_URL', 'http://localhost/undangan-online/');
```

Edit `config/database.php` (if needed):
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'undangan_online');
```

### 3️⃣ Set Permissions

```bash
chmod 755 uploads/
chmod 755 uploads/undangan/
chmod 755 uploads/qr/
chmod 755 uploads/gallery/
chmod 755 uploads/music/
```

### 4️⃣ Access Application

Open your browser and navigate to:
```
http://localhost/undangan-online/
```

### 5️⃣ Login

**Admin Access:**
- URL: `http://localhost/undangan-online/login-admin`
- Username: `admin`
- Password: `password`

**⚠️ IMPORTANT:** Change admin password immediately after first login!

## 📱 Testing

### Test Admin Panel
1. Login as admin
2. Go to "Kelola User" and create a test customer
3. Go to "Undangan List"

### Test Customer Panel
1. Login as customer
2. Check if undangan is assigned
3. Try editing undangan details
4. Add guest links
5. Add gift information

### Test Public View
1. Get invitation URL from customer dashboard
2. Open in new tab/incognito
3. Test RSVP form
4. Test all sections

## 🔧 Troubleshooting

### URL Rewriting Not Working
```bash
sudo a2enmod rewrite
sudo service apache2 restart
```

### Upload Permission Issues
```bash
sudo chown -R www-data:www-data uploads/
sudo chmod -R 755 uploads/
```

### Database Connection Failed
- Check MySQL is running: `sudo service mysql status`
- Verify credentials in `config/database.php`
- Check database exists: `mysql -u root -p -e "SHOW DATABASES;"`

## ✅ Verification Checklist

- [ ] Database imported successfully
- [ ] Homepage loads without errors
- [ ] Admin login works
- [ ] Customer login works
- [ ] Can create/edit users
- [ ] File uploads work
- [ ] Public invitation view works
- [ ] RSVP form submits
- [ ] All images load properly

## 🎉 Success!

If all checks pass, your application is ready to use!

For detailed documentation, see `README.md` and `PROJECT_SUMMARY.md`.

---
**Support:** support@muza-project.com | **WhatsApp:** +62 851 7966 9566
