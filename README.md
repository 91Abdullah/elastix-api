# Elastix API
Elastix and Asterisk API to make FreePBX easy to manage, written using PHP4.

## Installation
Open your Elastix (CentOS) terminal, and go to elastix directory, then download it:

```
cd /var/www/html
git clone https://github.com/tarek-aec/elastix-api.git
```

Open `api.php` and generate token key then replace it in:

```php
$this->key = 'YOUR_SECRETKEY_50_RANDOM_CHARS';
```

Now you have elastix api ready to use it.

## Useful API functions
This api package can implement:

* Get Authentication
* Get SIP Peers
* Get SIP Extensions
* Check System Resources
* Get CDR Report
* Get `*.wav` files
* Get Hard Driver State
* Check IPTable Status
* Get Active Call (Live Calls)
* SIP Trunk / Extension Management (Create, Update, Delete, Read)
* Follow Me Extension Management (Create, Update, Delete, Read)

## Contribute

Thank you for amazing community of FreePBX for useful information.
