# Changelog

All notable changes to the Paystack Payment Gateway for XenForo will be documented in this file.

## [1.0.0] - 2026-03-02
### Added
- Initial release of the Paystack Payment Gateway for XenForo 2.3.
- Support for Live and Test API keys with a toggle switch in the Admin Control Panel.
- Automated callback handling for instant user upgrade activation.
- Secure signature verification for all Paystack webhooks.
- Comprehensive README documentation with installation and setup guides.

### Changed
- Optimized the payment callback routing to improve response times for XenForo's engine.
- Refined the UI for the payment selection screen to match native XenForo styling.

### Fixed
- Resolved a minor issue where transaction descriptions were truncated on the Paystack dashboard.
- Fixed a bug preventing test transactions from completing on specific server configurations.