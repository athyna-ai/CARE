# Care CMS - Production Environment Setup

## Quick Setup Script for Hostinger

This script will help you set up your Care CMS system on Hostinger quickly and securely.

### Prerequisites
- Hostinger hosting account
- Domain: olshacare.com
- Database credentials from Hostinger

### Step 1: Database Configuration

1. **Get your Hostinger database credentials:**
   - Log into Hostinger control panel
   - Go to "Databases" → "MySQL Databases"
   - Create database: `u[your_user_id]_care_cms`
   - Create user: `u[your_user_id]_care`
   - Set a strong password

2. **Update the configuration:**
   Edit `core/config_production.php` and update these lines:
   ```php
   $DB_HOST = 'localhost';
   $DB_NAME = 'u[your_user_id]_care_cms';  // Replace with your actual database name
   $DB_USER = 'u[your_user_id]_care';      // Replace with your actual username
   $DB_PASS = 'your_secure_password';      // Replace with your actual password
   ```

### Step 2: GitHub Repository Setup

1. **Create a new GitHub repository:**
   - Name: `care-cms-production`
   - Make it private
   - Don't initialize with README

2. **Upload your code:**
   ```bash
   git init
   git add .
   git commit -m "Initial commit - Care CMS system"
   git branch -M main
   git remote add origin https://github.com/[your-username]/care-cms-production.git
   git push -u origin main
   ```

3. **Configure GitHub Secrets:**
   Go to Repository → Settings → Secrets and variables → Actions
   
   Add these secrets:
   - `HOSTINGER_HOST`: Your Hostinger server IP
   - `HOSTINGER_USERNAME`: Your Hostinger username (starts with 'u')
   - `HOSTINGER_SSH_KEY`: Your SSH private key

### Step 3: SSH Key Setup (Optional)

1. **Enable SSH in Hostinger:**
   - Go to "Advanced" → "SSH Access"
   - Enable SSH access
   - Generate SSH key pair
   - Download private key

2. **Add SSH key to GitHub Secrets:**
   - Copy the private key content
   - Add it as `HOSTINGER_SSH_KEY` secret

### Step 4: Environment Variables

Create a `.env` file in your project root (this will be ignored by git):
```env
# Database Configuration
DB_HOST=localhost
DB_NAME=u[your_user_id]_care_cms
DB_USER=u[your_user_id]_care
DB_PASS=your_secure_password

# Security Keys
ENCRYPTION_KEY=your_32_character_encryption_key_here
JWT_SECRET=your_jwt_secret_key_here

# Email Configuration
SMTP_HOST=smtp.hostinger.com
SMTP_PORT=587
SMTP_USER=your_email@olshacare.com
SMTP_PASS=your_email_password

# Domain Configuration
BASE_URL=https://olshacare.com
ADMIN_EMAIL=admin@olshacare.com
```

### Step 5: Build and Deploy

1. **Build CSS for production:**
   ```bash
   cd assets
   npm install
   npm run build-css-prod
   ```

2. **Deploy to Hostinger:**
   - Push to GitHub: `git push origin main`
   - GitHub Actions will automatically deploy
   - Or manually upload files via File Manager

### Step 6: Database Setup

1. **Import database schema:**
   - Upload `database/care_cms_database.sql` to Hostinger
   - Import through phpMyAdmin
   - Or run: `https://olshacare.com/setup_database.php`

2. **Verify database connection:**
   - Check if tables are created
   - Test login functionality
   - Verify data integrity

### Step 7: Security Configuration

1. **Enable SSL:**
   - Go to Hostinger control panel
   - Enable SSL certificate
   - Force HTTPS redirects

2. **Set file permissions:**
   - Files: 644
   - Directories: 755
   - Logs directory: 755

3. **Configure security headers:**
   - Already included in `.htaccess`
   - Verify in browser developer tools

### Step 8: Testing

1. **Basic functionality:**
   - Visit `https://olshacare.com`
   - Test login (admin@care.com / password)
   - Check patient management
   - Test medical records
   - Verify RFID functionality

2. **Performance testing:**
   - Check page load times
   - Test with multiple users
   - Monitor database performance

### Step 9: Monitoring Setup

1. **Error logging:**
   - Check `/logs/` directory
   - Monitor error logs
   - Set up log rotation

2. **Backup system:**
   - Automatic daily backups
   - Manual backups before updates
   - Test restore procedures

### Step 10: Maintenance

1. **Regular updates:**
   - Push changes to GitHub
   - Monitor deployment status
   - Test after updates

2. **Security monitoring:**
   - Review security logs
   - Check for vulnerabilities
   - Update dependencies

## Quick Commands

### Build CSS
```bash
cd assets
npm run build-css-prod
```

### Deploy
```bash
git add .
git commit -m "Update system"
git push origin main
```

### Check logs
```bash
tail -f logs/app.log
```

### Backup database
```bash
mysqldump -h localhost -u [username] -p[password] [database] > backup.sql
```

## Troubleshooting

### Common Issues

1. **Database connection failed:**
   - Check credentials in config
   - Verify database exists
   - Check Hostinger database status

2. **Permission denied:**
   - Check file permissions
   - Verify directory permissions
   - Check .htaccess rules

3. **CSS not loading:**
   - Rebuild CSS
   - Check file paths
   - Verify .htaccess

4. **SSL issues:**
   - Enable SSL in Hostinger
   - Check certificate status
   - Verify HTTPS redirects

### Support

- Hostinger Support: https://support.hostinger.com
- GitHub Actions: https://docs.github.com/en/actions
- PHP Documentation: https://www.php.net/docs.php

## Security Checklist

- [ ] SSL certificate enabled
- [ ] Database credentials secured
- [ ] File permissions set correctly
- [ ] Security headers configured
- [ ] Sensitive files protected
- [ ] Error reporting disabled
- [ ] Logging enabled
- [ ] Backup system working
- [ ] Rate limiting configured
- [ ] CSRF protection enabled

## Performance Checklist

- [ ] CSS minified
- [ ] Images optimized
- [ ] Compression enabled
- [ ] Caching configured
- [ ] Database indexed
- [ ] Error handling optimized
- [ ] Memory limits appropriate
- [ ] Execution time limits set

---

**Ready to deploy?** Follow these steps and your Care CMS system will be live on olshacare.com with automatic updates from GitHub!
