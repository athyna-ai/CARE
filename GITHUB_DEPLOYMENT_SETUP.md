# 🚀 Complete GitHub Deployment Setup for CARE System

## 📋 Prerequisites Checklist

Before starting, make sure you have:
- [ ] GitHub account
- [ ] Hostinger hosting account
- [ ] Your CARE system code in a GitHub repository
- [ ] Access to Hostinger control panel

---

## 🔍 Step 1: Find Your Hostinger FTP Details

### Method 1: Hostinger Control Panel
1. **Login**: Go to [hpanel.hostinger.com](https://hpanel.hostinger.com)
2. **Navigate**: Click "File Manager" or "FTP Accounts"
3. **Find Details**: Look for:
   - **FTP Server**: Usually `ftp.olshacare.com` or `olshacare.com`
   - **Username**: Usually starts with `u` + numbers (like `u258651435`)
   - **Password**: Your FTP password (may differ from hosting password)
   - **Port**: Usually `21` for FTP

### Method 2: Check Your Domain
For `olshacare.com`, your details are likely:
- **FTP Server**: `ftp.olshacare.com` or `olshacare.com`
- **Username**: `u258651435`
- **Password**: [Your Hostinger FTP password]
- **Port**: `21`

### Method 3: Contact Support
If you can't find details, contact Hostinger support for your exact FTP credentials.

---

## ⚙️ Step 2: Configure GitHub Secrets

### 2.1 Go to Your GitHub Repository
1. Open your GitHub repository
2. Click **Settings** tab
3. In the left sidebar, click **Secrets and variables** → **Actions**

### 2.2 Add Required Secrets
Click **New repository secret** for each:

#### Secret 1: HOSTINGER_FTP_SERVER
- **Name**: `HOSTINGER_FTP_SERVER`
- **Value**: `ftp.olshacare.com` (or your actual FTP server)

#### Secret 2: HOSTINGER_FTP_USERNAME
- **Name**: `HOSTINGER_FTP_USERNAME`
- **Value**: `u258651435` (your FTP username)

#### Secret 3: HOSTINGER_FTP_PASSWORD
- **Name**: `HOSTINGER_FTP_PASSWORD`
- **Value**: `[Your actual FTP password]`

#### Secret 4: HOSTINGER_DOMAIN
- **Name**: `HOSTINGER_DOMAIN`
- **Value**: `olshacare.com`

### 2.3 Verify Secrets
You should have 4 secrets total:
- ✅ HOSTINGER_FTP_SERVER
- ✅ HOSTINGER_FTP_USERNAME
- ✅ HOSTINGER_FTP_PASSWORD
- ✅ HOSTINGER_DOMAIN

---

## 🔧 Step 3: Update Configuration Files

### 3.1 Update deploy_config.json
Open `deploy_config.json` and update:
```json
{
  "deployment": {
    "hostinger": {
      "domain": "olshacare.com",
      "ftp_server": "ftp.olshacare.com",
      "ftp_username": "u258651435",
      "ftp_password": "YOUR_ACTUAL_FTP_PASSWORD",
      "ftp_port": 21,
      "server_path": "/public_html"
    }
  }
}
```

### 3.2 Update Webhook Secret
In `webhook_deploy.php`, change this line:
```php
'webhook_secret' => 'olsh_care_webhook_secret_2024_secure', // CHANGE THIS!
```
To a secure random string (at least 32 characters).

---

## 📤 Step 4: Upload Webhook to Hostinger

### 4.1 Upload webhook_deploy.php
1. **Login to Hostinger**: Go to File Manager
2. **Navigate**: Go to `/public_html/` folder
3. **Upload**: Upload `webhook_deploy.php` to `/public_html/`
4. **Verify**: Check that file is accessible at `https://olshacare.com/webhook_deploy.php`

### 4.2 Test Webhook Access
Visit: `https://olshacare.com/webhook_deploy.php`
- Should show "Method not allowed" (this is correct for GET requests)

---

## 🔗 Step 5: Configure GitHub Webhook

### 5.1 Go to Repository Settings
1. Open your GitHub repository
2. Click **Settings** tab
3. In left sidebar, click **Webhooks**
4. Click **Add webhook**

### 5.2 Configure Webhook
Fill in the form:
- **Payload URL**: `https://olshacare.com/webhook_deploy.php`
- **Content type**: `application/json`
- **Secret**: Use the same secret from `webhook_deploy.php`
- **Events**: Select "Just the push event"
- **Active**: ✅ Checked

### 5.3 Test Webhook
1. Click **Add webhook**
2. GitHub will test the webhook
3. You should see a green checkmark ✅

---

## 🧪 Step 6: Test Deployment

### 6.1 Make a Test Change
1. **Edit a file**: Make a small change to any file (like adding a comment)
2. **Commit**: 
   ```bash
   git add .
   git commit -m "Test deployment setup"
   git push origin main
   ```

### 6.2 Monitor Deployment
1. **GitHub Actions**: Go to your repository → **Actions** tab
2. **Watch**: You should see "Deploy CARE System to Hostinger" workflow running
3. **Check logs**: Click on the workflow to see detailed logs

### 6.3 Verify Success
- ✅ GitHub Actions shows green checkmark
- ✅ Website loads at `https://olshacare.com`
- ✅ Your changes are visible on the live site

---

## 🚨 Troubleshooting

### Common Issues:

#### 1. FTP Connection Failed
**Error**: `FTP connection failed`
**Solution**: 
- Verify FTP credentials in GitHub Secrets
- Check if FTP is enabled in Hostinger
- Try different FTP server format (`ftp.olshacare.com` vs `olshacare.com`)

#### 2. Permission Denied
**Error**: `Permission denied`
**Solution**:
- Check file permissions on Hostinger
- Ensure FTP user has write access to `/public_html`

#### 3. Webhook Not Triggering
**Error**: Webhook not receiving requests
**Solution**:
- Verify webhook URL is accessible
- Check webhook secret matches
- Review GitHub webhook delivery logs

#### 4. Deployment Fails
**Error**: Deployment workflow fails
**Solution**:
- Check GitHub Actions logs for specific error
- Verify all secrets are set correctly
- Test FTP connection manually

---

## 📊 Monitoring & Logs

### GitHub Actions Logs
- **Location**: Repository → Actions tab
- **Details**: Click on any workflow run to see logs

### Webhook Logs
- **Location**: `/logs/webhook_deployment.log` on your server
- **Access**: Via Hostinger File Manager

### Email Notifications
- **Success**: You'll receive email when deployment succeeds
- **Failure**: You'll receive email when deployment fails

---

## 🔄 Your New Workflow

Once set up, your workflow becomes:

```bash
# Make changes to your code
git add .
git commit -m "Fixed patient registration bug"
git push origin main

# → Automatic deployment to olshacare.com happens!
```

---

## 📞 Support

If you need help:
1. **Check logs** first (GitHub Actions + webhook logs)
2. **Verify credentials** (FTP details, GitHub secrets)
3. **Test manually** (try FTP connection with FileZilla)
4. **Contact support** if issues persist

---

## ✅ Final Checklist

Before going live:
- [ ] All 4 GitHub secrets configured
- [ ] `deploy_config.json` updated with real password
- [ ] `webhook_deploy.php` uploaded to Hostinger
- [ ] GitHub webhook configured and tested
- [ ] Test deployment completed successfully
- [ ] Website accessible at `https://olshacare.com`

**🎉 You're all set! Your CARE system will now automatically deploy when you push to GitHub!**
