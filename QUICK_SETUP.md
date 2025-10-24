# 🎯 QUICK SETUP GUIDE - GitHub Deployment for CARE System

## ✅ Your FTP Details (Confirmed Working!)
- **FTP Server**: `145.79.25.112`
- **FTP Username**: `u258651435`
- **FTP Password**: `015hc@r3_Care`
- **FTP Port**: `21`
- **Upload Folder**: `public_html`

---

## 🚀 Step-by-Step Setup

### Step 1: Configure GitHub Secrets
1. **Go to your GitHub repository**
2. **Click Settings** → **Secrets and variables** → **Actions**
3. **Add these 4 secrets**:

| Secret Name | Value |
|-------------|-------|
| `HOSTINGER_FTP_SERVER` | `145.79.25.112` |
| `HOSTINGER_FTP_USERNAME` | `u258651435` |
| `HOSTINGER_FTP_PASSWORD` | `015hc@r3_Care` |
| `HOSTINGER_DOMAIN` | `olshacare.com` |

### Step 2: Upload Webhook to Hostinger
1. **Login to Hostinger File Manager**
2. **Go to `/public_html/` folder**
3. **Upload `webhook_deploy.php`** to `/public_html/`
4. **Test**: Visit `https://olshacare.com/webhook_deploy.php` (should show "Method not allowed")

### Step 3: Configure GitHub Webhook
1. **Go to your GitHub repository**
2. **Settings** → **Webhooks** → **Add webhook**
3. **Fill in**:
   - **Payload URL**: `https://olshacare.com/webhook_deploy.php`
   - **Content type**: `application/json`
   - **Secret**: `olsh_care_webhook_secret_2024_secure`
   - **Events**: "Just the push event"
   - **Active**: ✅ Checked

### Step 4: Test Deployment
1. **Make a small change** to any file
2. **Commit and push**:
   ```bash
   git add .
   git commit -m "Test deployment setup"
   git push origin main
   ```
3. **Check GitHub Actions** tab for deployment status
4. **Verify** your site at `https://olshacare.com`

---

## 📁 Files Created for You

✅ **`.github/workflows/deploy.yml`** - GitHub Actions workflow
✅ **`webhook_deploy.php`** - Webhook endpoint for automatic deployment
✅ **`deploy_config.json`** - Configuration file with your FTP details
✅ **`test_ftp_connection.php`** - FTP connection test (confirmed working!)
✅ **`GITHUB_DEPLOYMENT_SETUP.md`** - Detailed setup guide

---

## 🔄 Your New Workflow

Once set up, deploying becomes super simple:

```bash
# Make changes to your code
git add .
git commit -m "Fixed patient registration bug"
git push origin main

# → Automatic deployment to olshacare.com happens!
```

---

## 🧪 Test Your Setup

Run this command to test your FTP connection:
```bash
C:\xampp\php\php.exe test_ftp_connection.php
```

---

## 🆘 Need Help?

If something goes wrong:
1. **Check GitHub Actions logs** (Repository → Actions tab)
2. **Verify all 4 secrets are set** in GitHub
3. **Test FTP connection** with the test script
4. **Check webhook** at `https://olshacare.com/webhook_deploy.php`

---

## 🎉 You're Almost Done!

Just follow the 4 steps above and you'll have:
- ✅ Automatic deployment on every push
- ✅ Email notifications on success/failure
- ✅ Backup before each deployment
- ✅ Professional development workflow

**Your CARE system will automatically update on olshacare.com every time you push to GitHub!**
