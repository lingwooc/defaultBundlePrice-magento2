<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace thousandmonkeys\BundleDefaultPrice\Pricing\Adjustment;

use Magento\Bundle\Model\Product\Price;
use Magento\Bundle\Pricing\Price\BundleSelectionFactory as BundleSelectionFactory;
use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\Adjustment\Calculator as CalculatorBase;
use Magento\Framework\Pricing\Amount\AmountFactory as AmountFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface as PriceCurrencyInterface;
use Magento\Tax\Helper\Data as TaxHelper;
use Magento\Bundle\Pricing\Adjustment\Calculator as Calculator;
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
    public function createSelectionPriceList($option, $bundleProduct, $useRegularPrice = false, $searchMin = false)
    {
        $priceList = [];
        if($bundleProduct->getPriceView()==2 && $searchMin) {
            if(!is_null($option->getDefaultSelection()))
                $selections = [$option->getDefaultSelection()];
            else
                $selections = [];
        }
        else {
            $selections = $option->getSelections();
        }
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

   /**
     * Filter all options for bundle product
     *
     * @param Product $bundleProduct
     * @param bool $searchMin
     * @param bool $useRegularPrice
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function getSelectionAmounts(Product $bundleProduct, $searchMin, $useRegularPrice = false)
    {
        // Flag shows - is it necessary to find minimal option amount in case if all options are not required
        $shouldFindMinOption = false;
        if ($searchMin
            && $bundleProduct->getPriceType() == Price::PRICE_TYPE_DYNAMIC
            && !$this->hasRequiredOption($bundleProduct)
        ) {
            $shouldFindMinOption = true;
        }
        $canSkipRequiredOptions = $searchMin && !$shouldFindMinOption;

        $currentPrice = false;
        $priceList = [];
        foreach ($this->getBundleOptions($bundleProduct) as $option) {
            if ($this->canSkipOption($option, $canSkipRequiredOptions)) {
                continue;
            }
            $selectionPriceList = $this->createSelectionPriceList($option, $bundleProduct, $useRegularPrice, $searchMin);
            $selectionPriceList = $this->processOptions($option, $selectionPriceList, $searchMin);

            if(count($selectionPriceList) != 0) {
                $lastSelectionPrice = end($selectionPriceList);
                $lastValue = $lastSelectionPrice->getAmount()->getValue() * $lastSelectionPrice->getQuantity();
            }
            if ($shouldFindMinOption && (!$currentPrice || $lastValue < ($currentPrice->getAmount()->getValue() * $currentPrice->getQuantity()))
            ) {
                $currentPrice = end($selectionPriceList);
            } elseif (!$shouldFindMinOption) {
                $priceList = array_merge($priceList, $selectionPriceList);
            }
        }
        return $shouldFindMinOption ? [$currentPrice] : $priceList;
    }
}
