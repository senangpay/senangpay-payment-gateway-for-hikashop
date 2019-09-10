# senangPay for Hikashop 2.6
Integrate senangPay in Hikashop 2.6

# Requirement

  * Tested with Joomla 3.6
  * Tested with Hikashop Starter 2.6.4
  * Compatible with PHP 7.0 and 7.1
  * senangPay organization account

# Installation Instruction

  * Download this repository: https://github.com/senangpay/senangpay-payment-gateway-for-hikashop
  * Go to Joomla Administration >> Extension >> Manage >> Install
  * Upload package file >> Install
  * Enable the plugin >> Extension >> Plugin >> Hikashop senangPay Payment Plugin
  * Go to Hikashop Option >> System >> Payment Method >> New >> senangPay
  * Set the particular details (Secret Key, Merchant ID & etc)
  * Save & Close
  
# Specific Configuration

  * **Secret Key** : Get the Secret Key at senangPay Profile Page
  * **Merchant ID** : Get the Merchant ID at senangPay Profile Page
  * Mode : Only change to sandbox if you registered senangPay account at sandbox.senangpay.my. Otherwise, leave it as Production
  * Debug : No
  * Invalid status : Cancelled
  * Verified status : Confirmed
  
# Custom Image

  * Upload **logo-senangpay.png** file to **/media/com_hikashop/images/payment/**
  * Set it at Generic Configuration
  
