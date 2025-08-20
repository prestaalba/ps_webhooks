[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.6.1-8892BF.svg)](https://php.net/)
[![Minimum PrestaShop Compatibility](https://img.shields.io/badge/prestashop-%3E%3D%201.7-8892BF.svg)](https://doc.prestashop.com/pages/viewpage.action?pageId=54264853)
[![GitHub release](https://img.shields.io/github/v/release/prestaalba/ps_webhooks)](https://github.com/prestaalba/ps_webhooks/releases)

# PrestaShop Webhooks

Trigger real-time notifications on PrestaShop events attaching object data in JSON format to the request.

## Install

[Donwload a zip release](https://github.com/prestaalba/ps_webhooks/releases) and install it like any other module.

## Use

Go to the module configuration page in your PrestaShop back office. Create as many webhooks as needed by selecting the PrestaShop action and entity you want to monitor, and defining the target URL. Whenever the event is triggered, the related data will be sent in real time via a POST request, using the following JSON format example:

```json
{
  "action": "update",
  "entity": "Customer",
  "data": {
    "id": 42,
    "id_gender": 1,
    "id_default_group": 3,
    "id_lang": null,
    "company": "Rolige",
    "firstname": "Wilson",
    "lastname": "Alba",
    "email": "wilson@example.com",
    "passwd": "$2y$10$abc123...",
    "birthday": "1990-05-20",
    "newsletter": true,
    "optin": false,
    "active": true,
    "is_guest": false,
    "deleted": false,
    "date_add": "2023-01-15 10:00:00",
    "date_upd": "2025-08-20 05:50:00"
  }
}
```

## Contribute

Any contributions are very welcome :)

## License

This module is released under an AFL license.
