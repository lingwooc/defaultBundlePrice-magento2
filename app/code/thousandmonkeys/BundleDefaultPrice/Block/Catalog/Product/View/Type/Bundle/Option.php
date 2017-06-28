<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace thousandmonkeys\BundleDefaultPrice\Block\Catalog\Product\View\Type\Bundle;

use Magento\Framework\Pricing\Amount\AmountFactory;

/**
 * Bundle option renderer
 */
class Option extends \Magento\Bundle\Block\Catalog\Product\View\Type\Bundle\Option
{
    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $_imageHelper;

    protected $_productRepository;

    /**
     * @var AmountFactory
     */
    protected $amountFactory;

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
     * @param AmountFactory $amountFactory
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
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        AmountFactory $amountFactory,
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
        $this->amountFactory = $amountFactory;
        $this->_imageHelper = $imageHelper;
        $this->_productRepository = $productRepository;
    }

    /**
     * Get title price for selection product
     *
     * @param \Magento\Catalog\Model\Product $selection
     * @param bool $includeContainer
     * @return string
     */
    public function getSelectionTitlePrice($selection, $includeContainer = true)
    {
        $html = array();


        $html[] = '<span class="product-name">';
        // $html[] = '<a href="'.$selection->getProductUrl().'" target="_blank">';
        $html[] = $this->escapeHtml($selection->getName());
        $html[] = '</a>';
        $html[] = '</span>';
        $html[] = ' &nbsp; ';
        $html[] = ($includeContainer ? '<span class="price-notice">' : '');
        $html[] = $this->renderPriceString($selection, $includeContainer) . ($includeContainer ? '</span>' : '');
        return implode($html);
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
        $sign = '+';

        if($this->getProduct()->getPriceView()==2){
            $defaultSelection = $this->getOption()->getDefaultSelection();
            $defaultPrice = $price->getOptionSelectionAmount($defaultSelection)->getValue();
            $diff = $amount->getValue()-$defaultPrice;

            $absDiff = abs($diff);
            $sign = ($diff >= 0 ? '+' : '-');

            $amount = $this->amountFactory->create($absDiff);
        }

        $priceHtml = $this->getLayout()->getBlock('product.price.render.default')->renderAmount(
            $amount,
            $price,
            $selection,
            [
                'include_container' => $includeContainer
            ]
        );

        return $sign . $priceHtml;
    }

        /**
     * Retrieve product images in JSON format
     *
     * @return string
     */
    public function getGalleryImagesJson($selection)
    {
        $bundleBlock =  $this->getLayout()->createBlock('thousandmonkeys\BundleDefaultPrice\Block\Catalog\Product\View\Type\Bundle');
        return json_encode($bundleBlock->getGalleryImagesJson($selection));
    }
}
