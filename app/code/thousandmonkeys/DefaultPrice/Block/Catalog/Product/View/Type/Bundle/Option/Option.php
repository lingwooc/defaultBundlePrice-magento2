<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Magento\Bundle\Block\Catalog\Product\View\Type\Bundle;

/**
 * Bundle option renderer
 */
class Option extends \Magento\Bundle\Block\Catalog\Product\Price
{
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Catalog\Helper\Data $catalogData
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param \Magento\Checkout\Helper\Cart $cartHelper
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Checkout\Helper\Cart $cartHelper,
        \Magento\Tax\Helper\Data $taxData,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $jsonEncoder,
            $catalogData,
            $registry,
            $string,
            $mathRandom,
            $cartHelper,
            $taxData,
            $pricingHelper,
            $data
        );
    }

    /**
     * Format price string
     *
     * @param \Magento\Catalog\Model\Product $selection
     * @param bool $includeContainer
     * @return string
     */
    public function renderPriceString($selection, $includeContainer = true)
    {
        
        /** @var \Magento\Bundle\Pricing\Price\BundleOptionPrice $price */
        $price = $this->getProduct()->getPriceInfo()->getPrice('bundle_option');
        $amount = $price->getOptionSelectionAmount($selection);

        if($this->getProduct()->getPriceView()==2){
            $defaultPrice = $this->getDefaultSelection()->getAmount();
            $amount = $amount-$defaultPrice;
        }

        $priceHtml = $this->getLayout()->getBlock('product.price.render.default')->renderAmount(
            $amount,
            $price,
            $selection,
            [
                'include_container' => $includeContainer
            ]
        );

        return $priceHtml;
    }
}
