# Paystack Payment Gateway for XenForo 2.3

Developed by **Theophilus Adegbohungbe** — [theophilusadegbohungbe.com](https://theophilusadegbohungbe.com)  
Copyright (c) 2026 Theophilus Adegbohungbe.

This software is released under the **GNU General Public License v3.0**.

---

## Features

- Full integration with Paystack payment API
- Separate Live and Test API key fields with toggle switching
- Live/Test key prefix validation (prevents using wrong keys per mode)
- Secure webhook support with HMAC SHA-512 signature verification
- Paystack IP whitelisting for extra webhook security
- Instant `200 OK` webhook acknowledgement per Paystack official docs
- Rich product metadata sent to Paystack dashboard (`custom_fields`)
- Callback URL and Webhook URL displayed with one-click copy buttons
- Complete payment logging in XenForo admin panel
- Compatible with XenForo User Upgrades and Resource Manager purchases
- Test mode for safe development and testing

---

## Requirements

- XenForo 2.3 or higher
- PHP 7.2 or higher
- cURL extension enabled
- Valid Paystack account ([paystack.com](https://paystack.com))

---

## Installation

1. Upload the `TheophilusA` folder to your XenForo `src/addons/` directory:
   ```
   src/addons/TheophilusA/Paystack/
   ```
2. Go to **Admin CP > Add-ons**
3. Click **Install/Upgrade from File** and select the addon
4. Follow the on-screen prompts to complete installation

---

## Configuration

### 1. Get Your API Keys
- Log in to your [Paystack Dashboard](https://dashboard.paystack.com/#/settings/developers)
- Copy your **Live Secret Key** (`sk_live_...`), **Live Public Key** (`pk_live_...`)
- Copy your **Test Secret Key** (`sk_test_...`), **Test Public Key** (`pk_test_...`)

### 2. Create a Payment Profile
1. Go to **Admin CP > Setup > Payment profiles**
2. Click **Add payment profile** and select **Paystack**
3. Enter a **Title** (e.g. "Credit/Debit Card") and **Display title** (e.g. "Pay with Paystack")
4. Toggle **Test Mode** on or off:
   - **Test Mode ON** — enter your Test Secret Key and Test Public Key
   - **Test Mode OFF** — enter your Live Secret Key and Live Public Key
5. Click **Save**

> The addon validates that key prefixes match the selected mode — e.g. it will reject a `sk_test_` key if Live mode is selected.

### 3. Set Up Webhooks (Strongly Recommended)
Webhooks ensure payments are fulfilled even if a user closes their browser after paying on Paystack.

1. Go to [Paystack Dashboard > Settings > Webhooks](https://dashboard.paystack.com/#/settings/webhooks)
2. Add the **Webhook URL** displayed in your payment profile settings:
   ```
   https://yoursite.com/payment_callback.php?_xfProvider=paystack&type=webhook
   ```
3. Save the webhook in your Paystack dashboard
4. The addon automatically:
   - Sends `200 OK` immediately upon receipt (per Paystack docs)
   - Verifies the request signature using HMAC SHA-512
   - Validates the request originates from Paystack's official IPs

**Paystack Webhook IPs (whitelisted automatically):**
```
52.214.14.220
52.49.173.169
52.31.139.75
```

**Webhook Events Handled:**
| Event | Action |
|---|---|
| `charge.success` | Fulfills the purchase in XenForo |
| All other events | Acknowledged with `200 OK`, logged as info, no action taken |

### 4. Callback URL
The **Callback URL** (also shown in payment profile settings) is where Paystack redirects users after payment:
```
https://yoursite.com/payment_callback.php?_xfProvider=paystack
```
This is automatically passed to Paystack when initiating a transaction — no manual configuration needed.

---

## Payment Data Sent to Paystack

The following product and customer information is sent with every transaction and is visible in your Paystack dashboard:

| Field | Description |
|---|---|
| Product | Name of the item being purchased |
| Amount | Cost and currency |
| Username | XenForo username of the buyer |
| User ID | XenForo user ID of the buyer |
| Site | Your forum board title |
| cancel_action | Redirects user to your site if they cancel on Paystack checkout |

---

## Viewing Payment Logs

1. Go to **Admin CP > Setup > Payment profiles**
2. Click on your Paystack profile
3. Click **View log** to see all transaction records including:
   - Successful payments (redirect and webhook)
   - Signature verification failures
   - Amount/currency mismatches
   - Webhook event acknowledgements

---

## Testing

1. Enable **Test Mode** in the payment profile
2. Enter your test API keys (`sk_test_...` and `pk_test_...`)
3. Use Paystack's test card: **4084 0840 8408 4081** (any future date, any CVV)
4. Complete a test purchase on your forum
5. Verify the log shows **"Payment received successfully"**
6. Disable Test Mode and switch to live keys when ready for production

---

## Support

For support and enquiries, visit: [theophilusadegbohungbe.com](https://theophilusadegbohungbe.com)

---

## Author

**Theophilus Adegbohungbe**
[theophilusadegbohungbe.com](https://theophilusadegbohungbe.com)

---

## Version History

| Version | Notes |
|---|---|
| 1.0.0 | Initial release — full Paystack integration with webhook support |
