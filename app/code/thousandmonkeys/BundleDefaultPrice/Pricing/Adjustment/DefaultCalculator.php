<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace thousandmonkeys\BundleDefaultPrice\Pricing\Adjustment;

use Magento\Bundle\Pricing\Price\BundleSelectionFactory;
use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\Adjustment\Calculator as CalculatorBase;
use Magento\Framework\Pricing\Amount\AmountFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Tax\Helper\Data as TaxHelper;
use Magento\Bundle\Pricing\Adjustment\Calculator
/**
 * Bundle price calculator
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DefaultCalculator extends Calculator
{
    /**
     * @param CalculatorBase $calculator
     * @param AmountFactory $amountFactory
     * @param BundleSelectionFactory $bundleSelectionFactory
     * @param TaxHelper $taxHelper
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        CalculatorBase $calculator,
        AmountFactory $amountFactory,
        BundleSelectionFactory $bundleSelectionFactory,
        TaxHelper $taxHelper,
        PriceCurrencyInterface $priceCurrency
    ) {
        parent::__construct($calculator, $amountFactory, $bundleSelectionFactory, $taxHelper, $priceCurrency);
    }

    /**
     * Create selection price list for the retrieved options
     *
     * @param \Magento\Bundle\Model\Option $option
     * @param Product $bundleProduct
     * @param bool $useRegularPrice
     * @return \Magento\Bundle\Pricing\Price\BundleSelectionPrice[]
     */
    public function createSelectionPriceList($option, $bundleProduct, $useRegularPrice = false, $default = false)
    {
        $priceList = [];
        if($default)
            $selections = [$option->getDefaultSelection()];
        else
            $selections = $option->getSelections();

        if ($selections === null) {
            return $priceList;
        }
        /* @var $selection \Magento\Bundle\Model\Selection|\Magento\Catalog\Model\Product */
        foreach ($selections as $selection) {
            if (!$selection->isSalable()) {
                // @todo CatalogInventory Show out of stock Products
                continue;
            }
            $priceList[] = $this->selectionFactory->create(
                $bundleProduct,
                $selection,
                $selection->getSelectionQty(),
                [
                    'useRegularPrice' => $useRegularPrice,
                ]
            );
        }
        return $priceList;
    }


}
