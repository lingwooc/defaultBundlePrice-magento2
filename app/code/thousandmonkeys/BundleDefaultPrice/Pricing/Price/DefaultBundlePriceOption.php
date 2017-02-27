<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Bundle\Pricing\Price;

use Magento\Bundle\Pricing\Adjustment\BundleCalculatorInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\Price\AbstractPrice;
use Magento\Bundle\Pricing\Price\BundlePriceOption as BundleOptionPrice;
/**
 * Bundle option price model
 */
class DefaultBundleOptionPrice extends BundleOptionPrice implements BundleOptionPriceInterface
{
    /**
     * @param Product $saleableItem
     * @param float $quantity
     * @param BundleCalculatorInterface $calculator
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param BundleSelectionFactory $bundleSelectionFactory
     */
    public function __construct(
        Product $saleableItem,
        $quantity,
        BundleCalculatorInterface $calculator,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        BundleSelectionFactory $bundleSelectionFactory
    ) {
        parent::__construct($saleableItem, $quantity, $calculator, $priceCurrency, $bundleSelectionFactory);
    }

    /**
     * Calculate maximal or minimal options value
     *
     * @param bool $searchMin
     * @return bool|float
     */
    protected function calculateOptions($searchMin = true)
    {
        $priceList = [];
        $showDefaultPrice = ($bundleProduct->getPriceView()==2) && $searchMin;

        /* @var $option \Magento\Bundle\Model\Option */
        foreach ($this->getOptions() as $option) {
            if ($searchMin && !$option->getRequired()) {
                continue;
            }
            
            $selectionPriceList = $this->calculator->createSelectionPriceList($option, $this->product, false, $showDefaultPrice);
            $selectionPriceList = $this->calculator->processOptions($option, $selectionPriceList, $searchMin);
            $priceList = array_merge($priceList, $selectionPriceList);
        }
        $amount = $this->calculator->calculateBundleAmount(0., $this->product, $priceList);
        return $amount->getValue();
    }
}
