# Twilio Business WhatsApp Setup Guide

This guide will walk you through setting up Twilio Business WhatsApp for your Addy Business application.

## Prerequisites

- A Twilio account (free trial available)
- A phone number that can receive SMS for verification
- Your business information ready for WhatsApp Business verification

## Step 1: Create a Twilio Account

1. **Sign up for Twilio:**
   - Go to [https://www.twilio.com/try-twilio](https://www.twilio.com/try-twilio)
   - Click "Sign up" and create your account
   - Verify your email address
   - Verify your phone number (you'll receive a verification code via SMS)

2. **Complete Account Setup:**
   - Fill in your account information
   - Choose your country/region
   - The free trial gives you $15.50 credit to test with

## Step 2: Get Your Twilio Credentials

1. **Navigate to Console Dashboard:**
   - After logging in, you'll be on the Twilio Console Dashboard
   - Your **Account SID** is displayed at the top (starts with `AC...`)
   - Your **Auth Token** is also shown (click "View" to reveal it)

2. **Save These Credentials:**
   - Account SID: `ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`
   - Auth Token: `your_auth_token_here`
   - ⚠️ **Keep these secure!** Never commit them to version control.

## Step 3: Set Up WhatsApp Sandbox (For Testing)

The WhatsApp Sandbox allows you to test WhatsApp messaging immediately without going through the full Business verification process.

### Option A: WhatsApp Sandbox (Quick Testing)

1. **Navigate to WhatsApp in Twilio Console:**
   - In the Twilio Console, go to **Messaging** → **Try it out** → **Send a WhatsApp message**
   - Or go to: [https://console.twilio.com/us1/develop/sms/try-it-out/whatsapp-learn](https://console.twilio.com/us1/develop/sms/try-it-out/whatsapp-learn)

2. **Join the Sandbox:**
   - You'll see a sandbox number (usually `+1 415 523 8886`)
   - Send a WhatsApp message to this number with the join code shown
   - Example: Send `join <code>` to `+1 415 523 8886` via WhatsApp
   - You'll receive a confirmation message

3. **Get Your Sandbox Number:**
   - The sandbox "From" number format is: `whatsapp:+14155238886`
   - This is what you'll use for `TWILIO_WHATSAPP_FROM` in development

### Option B: WhatsApp Business API (Production)

For production use, you need to get your WhatsApp Business Account approved:

1. **Apply for WhatsApp Business API:**
   - Go to **Messaging** → **WhatsApp** → **Senders**
   - Click **"Get started with WhatsApp"**
   - Choose **"Twilio"** as your provider

2. **Complete Business Verification:**
   - Provide your business information:
     - Business name
     - Business address
     - Business website
     - Business description
     - Business category
   - Upload business documents (if required)
   - Provide a phone number for your business

3. **Wait for Approval:**
   - Twilio will review your application
   - This can take 1-3 business days
   - You'll receive email updates on the status

4. **Get Your WhatsApp Business Number:**
   - Once approved, you'll get a WhatsApp Business number
   - Format: `whatsapp:+1234567890` (your actual number)

## Step 4: Configure Your Laravel Application

1. **Add Environment Variables:**
   
   Open your `.env` file and add the following:

   ```env
   # WhatsApp Provider
   WHATSAPP_PROVIDER=twilio

   # Twilio Configuration
   TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
   TWILIO_AUTH_TOKEN=your_auth_token_here
   
   # For Sandbox (Testing):
   TWILIO_WHATSAPP_FROM=whatsapp:+14155238886
   
   # For Production (After Business Approval):
   # TWILIO_WHATSAPP_FROM=whatsapp:+1234567890
   ```

2. **Update Configuration:**
   
   The configuration is already set up in `config/services.php`. Just make sure your `.env` file has the correct values.

3. **Clear Configuration Cache (if in production):**
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

## Step 5: Test Your Setup

### Test 1: Send a Test Message

1. **Use the Login/Register Endpoints:**
   - Make sure you're using a phone number that has joined the sandbox (for testing)
   - Send a POST request to `/login/whatsapp/send-code` or `/register/whatsapp/send-code`
   - Check your WhatsApp for the verification code

2. **Check Logs:**
   - Check `storage/logs/laravel.log` for any errors
   - Look for "Twilio API error" or "Twilio API exception" messages

### Test 2: Verify Phone Number Format

Your phone numbers should be in E.164 format:
- ✅ Correct: `260973660337` (Zambia example)
- ✅ Correct: `+260973660337`
- ❌ Wrong: `0973660337` (missing country code)
- ❌ Wrong: `+260 973 660 337` (has spaces)

The `WhatsAppService` automatically formats numbers, but ensure your input is correct.

## Step 6: Production Checklist

Before going live:

- [ ] Business WhatsApp account approved by Twilio
- [ ] Production WhatsApp number obtained
- [ ] Environment variables updated with production credentials
- [ ] `TWILIO_WHATSAPP_FROM` updated to production number
- [ ] Test messages sent and received successfully
- [ ] Error handling tested
- [ ] Rate limiting configured (if needed)
- [ ] Monitoring/logging set up

## Troubleshooting

### Issue: "Twilio credentials not configured"
**Solution:** 
- Check that all three environment variables are set: `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_WHATSAPP_FROM`
- Clear config cache: `php artisan config:clear`

### Issue: "Message not received"
**Solution:**
- For sandbox: Make sure you've joined the sandbox by sending the join code
- Check phone number format (must be E.164: country code + number)
- Verify the `TWILIO_WHATSAPP_FROM` number is correct
- Check Twilio Console → Monitor → Logs for API errors

### Issue: "401 Unauthorized"
**Solution:**
- Verify your Account SID and Auth Token are correct
- Make sure there are no extra spaces in the credentials
- Check that your Twilio account is active (not suspended)

### Issue: "21211 Invalid 'To' Phone Number"
**Solution:**
- Phone number must be in E.164 format (country code + number, no spaces)
- For sandbox: Recipient must have joined the sandbox
- For production: Number must be a valid WhatsApp number

### Issue: "21608 Unsubscribed recipient"
**Solution:**
- Recipient has opted out of receiving messages
- For sandbox: They need to rejoin by sending the join code again
- For production: They need to opt back in

## Twilio Console Resources

- **Dashboard:** [https://console.twilio.com](https://console.twilio.com)
- **WhatsApp Senders:** [https://console.twilio.com/us1/develop/sms/senders/whatsapp-senders](https://console.twilio.com/us1/develop/sms/senders/whatsapp-senders)
- **API Logs:** [https://console.twilio.com/us1/monitor/logs/api](https://console.twilio.com/us1/monitor/logs/api)
- **Usage & Billing:** [https://console.twilio.com/us1/develop/billing/usage](https://console.twilio.com/us1/develop/billing/usage)

## Pricing

- **Sandbox:** Free for testing (limited to sandbox participants)
- **Production:** Pay-as-you-go pricing
  - WhatsApp messages: ~$0.005 - $0.01 per message (varies by country)
  - Check current pricing: [https://www.twilio.com/whatsapp/pricing](https://www.twilio.com/whatsapp/pricing)

## Next Steps

1. **Set up webhooks** (optional) to receive incoming WhatsApp messages
2. **Configure message templates** for production (required for certain message types)
3. **Set up monitoring** and alerts for failed messages
4. **Implement rate limiting** to prevent abuse
5. **Add message templates** for common use cases

## Support

- **Twilio Support:** [https://support.twilio.com](https://support.twilio.com)
- **Twilio Docs:** [https://www.twilio.com/docs/whatsapp](https://www.twilio.com/docs/whatsapp)
- **Twilio Community:** [https://www.twilio.com/community](https://www.twilio.com/community)

---

**Note:** This guide covers the basic setup. For advanced features like message templates, media messages, or webhooks, refer to the Twilio WhatsApp API documentation.

