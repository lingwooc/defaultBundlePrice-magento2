# defaultBundlePrice-magento2
This module allows you to show the default price of a bundle in the catalog and product pages (before customisation). It also shows the price difference on the option selectors and a thumbnail. Also fixes the indexer to have the minimum price as the default price. If you don't require this delete Model/ResourceMode/Indexer/Price.php and remove the last line from di.xml

# Installation
- composer require thousandmonkeys/m2-bundledefaultprice-module
- php bin/magento setup:upgrade
- php bin/magento setup:di:compile
- php bin/magento setup:static-content:deploy

# Usage
For your bundled product got to advanced pricing for "Price View" select "Default". This will show a price based on the default selection.  
- It only supports radio and select as of now.
- At the moment it will also display options as +/- the default price. This should be optional (but isn't). +/- price update for select but not for radio
- It also shows the option thumbnail and description underneath the radio/select. This also should be optional (but isn't).

