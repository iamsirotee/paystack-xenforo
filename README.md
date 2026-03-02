# Paystack Payment Gateway for XenForo 2.2

> **Developed by [Theophilus Adegbohungbe](https://theophilusadegbohungbe.com)**
> Copyright (c) 2026 Theophilus Adegbohungbe. All rights reserved.
> Unauthorized redistribution, resale or modification without explicit written permission is strictly prohibited.

---

A native XenForo 2.2 payment provider addon that integrates [Paystack](https://paystack.com) for accepting payments on your forum — supporting User Upgrades, Resource Manager purchases, and any other XenForo purchasable.

---

## Features

- Separate Live and Test API key fields with toggle switching
- Key prefix validation — rejects wrong key types per mode (e.g. `sk_test_` in live mode)
- Secure webhook support with HMAC SHA-512 signature verification
- Paystack IP whitelisting for extra webhook security
- Instant `200 OK` webhook acknowledgement per official Paystack docs
- Rich product metadata and `custom_fields` sent to Paystack dashboard
- Callback URL and Webhook URL with one-click copy buttons in Admin CP
- Full payment logging visible in XenForo Admin CP
- Compatible with XenForo 2.2+

---

## Requirements

- XenForo 2.2 or higher
- PHP 7.2 or higher
- cURL extension enabled
- Valid [Paystack](https://paystack.com) account

---

## Installation

1. Upload the `TheophilusA` folder into your XenForo `src/addons/` directory:
   ```
   src/addons/TheophilusA/Paystack/
   ```
2. Go to **Admin CP > Add-ons**
3. Click **Install/Upgrade from File** and follow the prompts

---

## Configuration

### API Keys
Go to **Admin CP > Setup > Payment profiles**, add a new profile and select **Paystack**.

- **Live Mode** — enter `sk_live_...` and `pk_live_...` keys
- **Test Mode** — enable the toggle and enter `sk_test_...` and `pk_test_...` keys

### Webhook Setup
Copy the **Webhook URL** shown in the payment profile and add it in your [Paystack Dashboard > Settings > Webhooks](https://dashboard.paystack.com/#/settings/webhooks):

```
https://yoursite.com/payment_callback.php?_xfProvider=paystack&type=webhook
```

The addon handles signature verification, IP whitelisting, and instant acknowledgement automatically.

**Paystack Webhook IPs (whitelisted automatically):**
```
52.214.14.220
52.49.173.169
52.31.139.75
```

---

## Testing

Enable **Test Mode** in the payment profile, enter your test keys, and use Paystack's test card:

- **Card:** `4084 0840 8408 4081`
- **Expiry:** Any future date
- **CVV:** Any 3 digits

---

## Documentation

Full documentation is available in [`addons/TheophilusA/Paystack/README.md`](addons/TheophilusA/Paystack/README.md).

---

## Support

For support and enquiries: [theophilusadegbohungbe.com](https://theophilusadegbohungbe.com)

---

## Author

**Theophilus Adegbohungbe**
[theophilusadegbohungbe.com](https://theophilusadegbohungbe.com)

---

## License

Copyright (c) 2026 Theophilus Adegbohungbe. All rights reserved.

This addon is proprietary software. Unauthorized redistribution, resale, modification, or use without explicit written permission from the author is strictly prohibited.

---

## Version History

| Version | Notes |
|---|---|
| 1.0.0 | Initial release — full Paystack integration with webhook support |
