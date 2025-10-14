# Care CMS - Production Deployment Guide

## Overview
This guide will help you deploy your Care CMS system to Hostinger with GitHub live updates.

## Prerequisites
- Hostinger hosting account with domain: olshacare.com
- GitHub account
- SSH access to Hostinger (if available)
- Database credentials from Hostinger

## Step 1: Hostinger Configuration

### 1.1 Database Setup
1. Log into your Hostinger control panel
2. Go to "Databases" → "MySQL Databases"
3. Create a new database:
   - Database name: `u[your_user_id]_care_cms`
   - Username: `u[your_user_id]_care`
   - Password: Generate a strong password
4. Note down these credentials

### 1.2 File Manager Access
1. Go to "File Manager" in Hostinger control panel
2. Navigate to `/public_html/` directory
3. This is where your website files will be uploaded

### 1.3 SSH Access (Optional but Recommended)
1. Go to "Advanced" → "SSH Access"
2. Enable SSH access
3. Generate SSH key pair
4. Download the private key

## Step 2: GitHub Repository Setup

### 2.1 Create GitHub Repository
1. Go to GitHub.com and create a new repository
2. Name it: `care-cms-production`
3. Make it private (recommended for production)
4. Don't initialize with README (we'll upload existing code)

### 2.2 Upload Your Code
```bash
# In your local Care directory
git init
git add .
git commit -m "Initial commit - Care CMS system"
git branch -M main
git remote add origin https://github.com/[your-username]/care-cms-production.git
git push -u origin main
```

### 2.3 Configure GitHub Secrets
Go to your GitHub repository → Settings → Secrets and variables → Actions

Add these secrets:
- `HOSTINGER_HOST`: Your Hostinger server IP or hostname
- `HOSTINGER_USERNAME`: Your Hostinger username (usually starts with 'u')
- `HOSTINGER_SSH_KEY`: Your SSH private key content

## Step 3: Environment Configuration

### 3.1 Update Production Config
Edit `core/config_production.php` and update:
- Database credentials
- Encryption keys
- Email settings
- Domain settings

### 3.2 Database Credentials
Update these values in `core/config_production.php`:
```php
$DB_HOST = 'localhost';
$DB_NAME = 'u[your_user_id]_care_cms';
$DB_USER = 'u[your_user_id]_care';
$DB_PASS = 'your_secure_password';
```

## Step 4: Deployment Process

### 4.1 Automatic Deployment
The GitHub Actions workflow will automatically deploy when you push to the `main` branch.

### 4.2 Manual Deployment
If you prefer manual deployment:

1. **Build CSS:**
   ```bash
   cd assets
   npm install
   npm run build-css-prod
   ```

2. **Upload Files:**
   - Use Hostinger File Manager
   - Upload all files to `/public_html/`
   - Exclude: `node_modules/`, `.git/`, `*.sql`, `logs/`

3. **Set Permissions:**
   - Files: 644
   - Directories: 755
   - Logs directory: 755

### 4.3 Database Setup
1. Upload `database/care_cms_database.sql` to Hostinger
2. Import it through phpMyAdmin or MySQL command line
3. Or run the setup script: `https://olshacare.com/setup_database.php`

## Step 5: Security Configuration

### 5.1 SSL Certificate
1. Enable SSL in Hostinger control panel
2. Force HTTPS redirects (handled by .htaccess)

### 5.2 Security Headers
The deployment includes security headers in `.htaccess`:
- X-Content-Type-Options
- X-Frame-Options
- X-XSS-Protection
- Strict-Transport-Security

### 5.3 File Protection
Sensitive files are protected:
- *.sql files
- *.log files
- .env files
- Configuration files

## Step 6: Testing

### 6.1 Basic Tests
1. Visit `https://olshacare.com`
2. Test login functionality
3. Check database connectivity
4. Verify file uploads
5. Test RFID functionality

### 6.2 Performance Tests
1. Check page load times
2. Test with multiple users
3. Monitor database performance
4. Check error logs

## Step 7: Monitoring and Maintenance

### 7.1 Log Monitoring
- Check `/logs/` directory regularly
- Monitor error logs
- Review security logs

### 7.2 Backup Strategy
- Automatic daily backups
- Manual backups before updates
- Database exports
- File system backups

### 7.3 Updates
- Push changes to GitHub
- GitHub Actions will auto-deploy
- Test in staging first
- Monitor after deployment

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check credentials in config
   - Verify database exists
   - Check Hostinger database status

2. **Permission Denied**
   - Check file permissions
   - Verify directory permissions
   - Check .htaccess rules

3. **CSS Not Loading**
   - Rebuild CSS: `npm run build-css-prod`
   - Check file paths
   - Verify .htaccess compression

4. **SSL Issues**
   - Enable SSL in Hostinger
   - Check certificate status
   - Verify HTTPS redirects

### Support Resources
- Hostinger Support: https://support.hostinger.com
- GitHub Actions Documentation: https://docs.github.com/en/actions
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

## Maintenance Schedule

### Daily
- Check error logs
- Monitor system performance
- Verify backups

### Weekly
- Review security logs
- Check for updates
- Test functionality

### Monthly
- Full system backup
- Performance review
- Security audit
- Update dependencies

---

**Note:** This deployment guide assumes you have basic knowledge of web hosting and Git. If you need assistance with any step, please refer to the respective documentation or contact support.
