# defaultBundlePrice-magento2
This module allows you to show the default price of a bundle in the catalog and product pages (before customisation). It also shows the price difference on the option selectors and a thumbnail.

# Installation
- composer require ThousandMonkeys/m2-bundledefaultprice-modul
- php bin/magento setup:upgrade
- php bin/magento setup:di:compile
- php bin/magento setup:static-content:deploy

# Usage
For your bundled product got to advanced pricing for "Price View" select "Default". This will show a price based on the default selection.  At the moment it will also display options as +/- the default price. This should be optional (but isn't).
