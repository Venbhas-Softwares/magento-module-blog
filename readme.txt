Venbhas Blog Module
======================

DESCRIPTION
-----------
Magento 2 module for managing articles, categories, and comments. Provides admin
grids and forms for content management, frontend article/category pages, search,
and related products display.

REQUIREMENTS
------------
- Magento 2.4.x (tested with 2.4.8-p3)
- PHP 8.1, 8.2, or 8.3
- Dependencies: Magento_Store, Magento_Catalog, Magento_Backend, Magento_Ui

INSTALLATION
------------
1. Copy the module to app/code/Venbhas/Blog (or install via Composer).
2. Enable the module:
   php bin/magento module:enable Venbhas_Blog
3. Run setup upgrade:
   php bin/magento setup:upgrade
4. Compile and deploy (if needed):
   php bin/magento setup:di:compile
   php bin/magento setup:static-content:deploy -f

ADMIN FEATURES
--------------
- Articles: Content Management > Blog > Articles
- Categories: Content Management > Blog > Categories
- Comments: Content Management > Blog > Comments

FRONTEND
--------
- Article list and view pages
- Category list and view pages
- Article search
- Related products block on article view
- Comment form and list on article view

SUPPORT
-------
Copyright (c) Venbhas. All rights reserved.
