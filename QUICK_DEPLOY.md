# ⚡ Quick Deploy Reference

Cheat sheet untuk deploy cepat ke cPanel.

---

## 🎯 Sebelum Deploy

### 1. Edit Config Production

```bash
# Frontend API URL
📝 cgi-bin/frontend/src/config/config.production.js
   baseUrl: "https://DOMAIN.com/app"

# Backend Database & CORS
📝 .env.production
   DB_USER="cpanel_user"
   DB_PASS="password"
   DB_NAME="cpanel_db"
   ALLOWED_ORIGINS="https://DOMAIN.com"
```

### 2. Build Frontend

```bash
cd cgi-bin/frontend
npm run build
```

---

## 📦 Upload ke cPanel

### File Manager → public_html/domain.com/

```
✅ Upload dari dist/:
   - index.html
   - manifest.webmanifest
   - sw.js
   - workbox-*.js
   - assets/ (folder)
   - *.png, *.svg (semua gambar)

✅ Upload dari root:
   - app/ (folder)
   - uploads/ (folder)
   - cache/ (folder)

✅ Upload & rename:
   - .env.production → .env
```

### Set Permissions

```
uploads/  → 755
cache/    → 755
```

---

## 🗄️ Database

### cPanel → MySQL Databases

```
1. Buat database: cpanel_dbname
2. Buat user: cpanel_user
3. Add user to database (ALL PRIVILEGES)
4. phpMyAdmin → Import create_database.sql
```

---

## ✅ Test

```
Backend:  https://domain.com/app/test_db_connection.php
Frontend: https://domain.com
```

---

## 🔧 Troubleshooting

| Error | Fix |
|-------|-----|
| CORS | `.env` → `ALLOWED_ORIGINS="https://domain.com"` |
| DB Failed | `.env` → Check DB credentials |
| 500 Error | `chmod 755 uploads/ cache/` |
| 404 | Check `index.html` di root |

---

## 📋 Checklist

- [ ] Edit `config.production.js`
- [ ] Edit `.env.production`
- [ ] `npm run build`
- [ ] Buat database di cPanel
- [ ] Import SQL
- [ ] Upload dist/ → root
- [ ] Upload app/ → root
- [ ] Upload .env.production → .env
- [ ] Buat folder uploads/ & cache/
- [ ] chmod 755
- [ ] Test backend
- [ ] Test frontend

---

**Done! 🎉**
