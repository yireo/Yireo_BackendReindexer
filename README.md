# Yireo BackendReindexer

<!-- badges.specs.start -->
<div class="github-specs-badges">

![Magento version](https://img.shields.io/badge/Magento-2.4.6%20%7C%202.4.9-orange)
![PHP version](https://img.shields.io/badge/PHP-8.2%E2%80%938.5-777BB4)
![License](https://img.shields.io/badge/License-OSL--3.0-blue)
![Latest Version](https://img.shields.io/packagist/v/yireo/magento2-backend-reindexer)

</div>
<!-- badges.specs.end -->

Magento 2 by default has no way to reindex from within the Magento Admin Panel.
This module adds a massaction to reindex from the backend anyway.

## !!! WARNING !!!
From a technical point of view, there is a really good reason that Magento 2 offers no way to reindex indexes using the Magento backend: Using the Magento Admin Panel from this task may lead to timeouts, memory issues, and because of this, potential data inconsistancy (corrupt data). Make sure to know what you're doing before using this module.

The advice is to index through the CLI task and/or through cronjob. However, this module might still prove useful to small shops, testing environments with no developer present, etcetera.

## Security
- The mass action only accepts POST requests with a valid form key.
- Access requires both the ACL resources `Magento_Indexer::index` and `Yireo_BackendReindexer::reindex`.
- Only known indexer IDs are reindexed. At most 5 indexers can be reindexed per request, because every reindex runs synchronously within the web request. Change this limit with the DI argument `maxIndexersPerRequest` of `Yireo\BackendReindexer\Controller\Adminhtml\Indexer\MassReindex`.
- Errors are logged to `var/log/system.log`, while the Admin Panel only shows a generic message.

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

## Current status

<!-- badges.test.start -->
<div class="github-pipeline-badges">

![Static Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_BackendReindexer/static-tests.yml?label=static-tests)
![Unit Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_BackendReindexer/unit-tests.yml?label=unit-tests)
![Integration Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_BackendReindexer/integration-tests.yml?label=integration-tests)
![Playwright](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_BackendReindexer/playwright.yml?label=playwright)
![DI Compilation](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_BackendReindexer/compile.yml?label=compile)

</div>
<!-- badges.test.end -->
