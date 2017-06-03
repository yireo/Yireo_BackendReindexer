# Yireo BackendReindexer
Magento 2 by default has no way to reindex from within the Magento Admin Panel.
This module adds a massaction to reindex from the backend anyway.

## Installation
Install this module within Magento 2 using composer:

    composer require yireo/magento2-backend-reindexer

After this, enable the module as usual:

    bin/magento module:enable Yireo_BackendReindexer
    bin/magento setup:upgrade
    bin/magento cache:clean


## Technical architecture
- This module adds a massaction through XML layout.
- It also adds a `Plugin` to check for the right ACL rules.
