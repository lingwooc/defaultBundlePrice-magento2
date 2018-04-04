<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ThousandMonkeys\BundleDefaultPrice\Block\Catalog\Product\View\Type;

use Magento\Bundle\Model\Option;
use Magento\Catalog\Model\Product;

/**
 * Catalog bundle product info block
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Bundle extends \Magento\Bundle\Block\Catalog\Product\View\Type\Bundle
{
    protected $_template = 'catalog/product/view/type/bundle.phtml';
    protected $_imageHelper;
    protected $_productRepository;
    
    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Stdlib\ArrayUtils $arrayUtils
     * @param \Magento\Catalog\Helper\Product $catalogProduct
     * @param \Magento\Bundle\Model\Product\PriceFactory $productPrice
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Stdlib\ArrayUtils $arrayUtils,
        \Magento\Catalog\Helper\Product $catalogProduct,
        \Magento\Bundle\Model\Product\PriceFactory $productPrice,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Framework\Pricing\Amount\AmountFactory $amountFactory,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        array $data = []
    ) {
        $this->amountFactory = $amountFactory;
        $this->_imageHelper = $context->getImageHelper();
        $this->_productRepository = $productRepository;
        parent::__construct(
            $context,
            $arrayUtils,
            $catalogProduct,
            $productPrice,
            $jsonEncoder,
            $localeFormat,
            $data
        );
    }

    /**
     * Returns JSON encoded config to be used in JS scripts
     *
     * @return string
     *
     */
    public function getJsonConfig()
    {
        /** @var Option[] $optionsArray */
        $optionsArray = $this->getOptions();
        $options = [];
        $currentProduct = $this->getProduct();

        $defaultValues = [];
        $preConfiguredFlag = $currentProduct->hasPreconfiguredValues();
        /** @var \Magento\Framework\DataObject|null $preConfiguredValues */
        $preConfiguredValues = $preConfiguredFlag ? $currentProduct->getPreconfiguredValues() : null;

        $position = 0;
        foreach ($optionsArray as $optionItem) {
            /* @var $optionItem Option */
            if (!$optionItem->getSelections()) {
                continue;
            }
            $optionId = $optionItem->getId();
            $options[$optionId] = $this->getOptionItemData($optionItem, $currentProduct, $position);

            // Add attribute default value (if set)
            if ($preConfiguredFlag) {
                $configValue = $preConfiguredValues->getData('bundle_option/' . $optionId);
                if ($configValue) {
                    $defaultValues[$optionId] = $configValue;
                }
            }
            $position++;
        }
        $config = $this->getConfigData($currentProduct, $options);

        $configObj = new \Magento\Framework\DataObject(
            [
                'config' => $config,
            ]
        );

        //pass the return array encapsulated in an object for the other modules to be able to alter it eg: weee
        $this->_eventManager->dispatch('catalog_product_option_price_configuration_after', ['configObj' => $configObj]);
        $config=$configObj->getConfig();

        if ($preConfiguredFlag && !empty($defaultValues)) {
            $config['defaultValues'] = $defaultValues;
        }

        return $this->jsonEncoder->encode($config);
    }

    /**
     * Get formed data from option selection item
     *
     * @param Product $product
     * @param Product $selection
     * @return array
     */
    protected function getSelectionItemData(Option $option, Product $product, Product $selection)
    {
        $price = $this->getProduct()->getPriceInfo()->getPrice('bundle_option');
        $optionPriceAmount = $price
                    ->getOptionSelectionAmount($selection);

        if($this->getProduct()->getPriceView()==2){
            $defaultSelection = $option->getDefaultSelection();
            $defaultPrice = $price->getOptionSelectionAmount($defaultSelection)->getValue();
            $diff = $optionPriceAmount->getValue()-$defaultPrice;

            $absDiff = abs($diff);
            $sign = ($diff >= 0 ? '+' : '-');

            $amount = $this->amountFactory->create($absDiff);
        }

        $qty = ($selection->getSelectionQty() * 1) ?: '1';

        $optionPriceAmount = $price
            ->getOptionSelectionAmount($selection);
        $finalPrice = $optionPriceAmount->getValue();
        $basePrice = $optionPriceAmount->getBaseAmount();

        $selection = [
            'qty' => $qty,
            'customQty' => $selection->getSelectionCanChangeQty(),
            'optionId' => $selection->getId(),
            'prices' => [
                'oldPrice' => [
                    'amount' => $basePrice
                ],
                'basePrice' => [
                    'amount' => $basePrice
                ],
                'finalPrice' => [
                    'amount' => $finalPrice
                ]
            ],
            'priceType' => $selection->getSelectionPriceType(),
            'tierPrice' => $this->getTierPrices($product, $selection),
            'name' => $selection->getName(),
            'canApplyMsrp' => false,
            'images' => $this->getGalleryImagesJson($selection),
            'description' => $this->getDescription($selection),
            'url' => $selection->getProductUrl(),   
        ];
        return $selection;
    }

    /**
     * Get formed data from selections of option
     *
     * @param Option $option
     * @param Product $product
     * @return array
     */
    protected function getSelections(Option $option, Product $product)
    {
        $selections = [];
        $selectionCount = count($option->getSelections());
        foreach ($option->getSelections() as $selectionItem) {
            /* @var $selectionItem Product */
            $selectionId = $selectionItem->getSelectionId();
            $selections[$selectionId] = $this->getSelectionItemData($option, $product, $selectionItem);

            if (($selectionItem->getIsDefault() || $selectionCount == 1 && $option->getRequired())
                && $selectionItem->isSalable()
            ) {
                $this->selectedOptions[$option->getId()][] = $selectionId;
            }
        }
        return $selections;
    }

    /**
     * Get formed data from option
     *
     * @param Option $option
     * @param Product $product
     * @param int $position
     * @return array
     */
    protected function getOptionItemData(Option $option, Product $product, $position)
    {
        return [
            'selections' => $this->getSelections($option, $product),
            'title' => $option->getTitle(),
            'isMulti' => in_array($option->getType(), ['multi', 'checkbox']),
            'position' => $position
        ];
    }

    /**
     * Get formed config data from calculated options data
     *
     * @param Product $product
     * @param array $options
     * @return array
     */
    protected function getConfigData(Product $product, array $options)
    {
        $isFixedPrice = $this->getProduct()->getPriceType() == \Magento\Bundle\Model\Product\Price::PRICE_TYPE_FIXED;

        $productAmount = $product
            ->getPriceInfo()
            ->getPrice(\Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE)
            ->getPriceWithoutOption();

        $baseProductAmount = $product
            ->getPriceInfo()
            ->getPrice(\Magento\Catalog\Pricing\Price\RegularPrice::PRICE_CODE)
            ->getAmount();

        $config = [
            'options' => $options,
            'selected' => $this->selectedOptions,
            'bundleId' => $product->getId(),
            'priceFormat' => $this->localeFormat->getPriceFormat(),
            'prices' => [
                'oldPrice' => [
                    'amount' => $isFixedPrice ? $baseProductAmount->getValue() : 0
                ],
                'basePrice' => [
                    'amount' => $isFixedPrice ? $productAmount->getBaseAmount() : 0
                ],
                'finalPrice' => [
                    'amount' => $isFixedPrice ? $productAmount->getValue() : 0
                ]
            ],
            'priceType' => $product->getPriceType(),
            'isFixedPrice' => $isFixedPrice,
        ];

        return $config;
    }

        /**
     * Retrieve product images in JSON format
     *
     * @return string
     */
    public function getGalleryImagesJson($selection)
    {
        $imagesItems = [];
        foreach ($this->getGalleryImages($selection) as $image) {
            $imagesItems[] = [
                'thumb' => $image->getData('small_image_url'),
                'img' => $image->getData('medium_image_url'),
                'full' => $image->getData('large_image_url'),
                'caption' => $image->getLabel(),
                'position' => $image->getPosition(),
                'isMain' => $this->isMainImage($image, $selection),
            ];
        }
        if (empty($imagesItems)) {
            $imagesItems[] = [
                'thumb' => $this->_imageHelper->getDefaultPlaceholderUrl('thumbnail'),
                'img' => $this->_imageHelper->getDefaultPlaceholderUrl('image'),
                'full' => $this->_imageHelper->getDefaultPlaceholderUrl('image'),
                'caption' => '',
                'position' => '0',
                'isMain' => true,
            ];
        }
        return $imagesItems;
    }

    /**
     * Is product main image
     *
     * @param \Magento\Framework\DataObject $image
     * @return bool
     */
    public function isMainImage($image, $selection)
    {
        return $selection->getImage() == $image->getFile();
    }

    /**
     * Retrieve collection of gallery images
     *
     * @return Collection
     */
    public function getGalleryImages($selection)
    {
        $product = $this->_productRepository->get($selection->getSku());
        $images = $product->getMediaGalleryImages();
        if ($images instanceof \Magento\Framework\Data\Collection) {
            foreach ($images as $image) {
                /* @var \Magento\Framework\DataObject $image */
                $image->setData(
                    'small_image_url',
                    $this->_imageHelper->init($product, 'product_page_image_small')
                        ->setImageFile($image->getFile())
                        ->getUrl()
                );
                $image->setData(
                    'medium_image_url',
                    $this->_imageHelper->init($product, 'product_page_image_medium')
                        ->constrainOnly(true)->keepAspectRatio(true)->keepFrame(false)
                        ->setImageFile($image->getFile())
                        ->getUrl()
                );
                $image->setData(
                    'large_image_url',
                    $this->_imageHelper->init($product, 'product_page_image_large')
                        ->constrainOnly(true)->keepAspectRatio(true)->keepFrame(false)
                        ->setImageFile($image->getFile())
                        ->getUrl()
                );
            }
        }

        return $images;
    }

    public function getDescription($partialProduct){
        $product = $this->_productRepository->get($partialProduct->getSku());
        $description = $product->getData('short_description');
        return $description;
    }
}
