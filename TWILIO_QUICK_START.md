# Twilio Business WhatsApp - Quick Start Guide

This is a condensed guide to get you up and running quickly. For detailed information, see `TWILIO_WHATSAPP_SETUP_GUIDE.md`.

## 🚀 Quick Setup (5 Minutes)

### Step 1: Create Twilio Account (2 minutes)
1. Go to [https://www.twilio.com/try-twilio](https://www.twilio.com/try-twilio)
2. Sign up and verify your email/phone
3. You'll get $15.50 free credit for testing

### Step 2: Get Your Credentials (1 minute)
1. In Twilio Console Dashboard, find:
   - **Account SID** (starts with `AC...`)
   - **Auth Token** (click "View" to reveal)

### Step 3: Join WhatsApp Sandbox (1 minute)
1. Go to: [https://console.twilio.com/us1/develop/sms/try-it-out/whatsapp-learn](https://console.twilio.com/us1/develop/sms/try-it-out/whatsapp-learn)
2. Send the join code shown to `+1 415 523 8886` via WhatsApp
3. You'll receive a confirmation

### Step 4: Configure Your App (1 minute)
1. Open your `.env` file
2. Add these lines:
   ```env
   WHATSAPP_PROVIDER=twilio
   TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
   TWILIO_AUTH_TOKEN=your_auth_token_here
   TWILIO_WHATSAPP_FROM=whatsapp:+14155238886
   ```
3. Replace the placeholder values with your actual credentials

### Step 5: Test It! (1 minute)
```bash
php artisan config:clear
php test-twilio-whatsapp.php
```

Or test via your app:
- Send a POST request to `/login/whatsapp/send-code` with a phone number
- Check WhatsApp for the verification code

## ✅ What You Should See

**In the test script:**
- ✅ Connection successful
- ✅ WhatsApp number format is correct
- ✅ (Optional) Test message sent successfully

**In your app:**
- Verification codes sent via WhatsApp
- Users can login/register with WhatsApp

## 🎯 Next Steps

### For Testing (Sandbox)
- ✅ You're all set! The sandbox works immediately
- ⚠️ Only works with numbers that have joined the sandbox
- ⚠️ Limited to testing purposes

### For Production
1. Apply for WhatsApp Business API approval
   - Go to: Messaging → WhatsApp → Senders
   - Complete business verification
   - Wait 1-3 business days for approval
2. Update your `.env` with production number:
   ```env
   TWILIO_WHATSAPP_FROM=whatsapp:+1234567890
   ```
3. Test with real phone numbers

## 📋 Files Created

- `TWILIO_WHATSAPP_SETUP_GUIDE.md` - Complete detailed guide
- `TWILIO_ENV_TEMPLATE.txt` - Environment variable template
- `test-twilio-whatsapp.php` - Test script to verify setup

## 🆘 Common Issues

**"Twilio credentials not configured"**
→ Check your `.env` file has all three variables set

**"Message not received"**
→ For sandbox: Make sure recipient joined the sandbox first

**"401 Unauthorized"**
→ Double-check your Account SID and Auth Token

**"21211 Invalid 'To' Phone Number"**
→ Phone number must be in E.164 format (country code + number, no spaces)

## 📚 Need More Help?

- See `TWILIO_WHATSAPP_SETUP_GUIDE.md` for detailed instructions
- Check Twilio Console: [https://console.twilio.com](https://console.twilio.com)
- Twilio Docs: [https://www.twilio.com/docs/whatsapp](https://www.twilio.com/docs/whatsapp)

---

**Your code is already set up!** Just add the credentials and you're good to go! 🎉

