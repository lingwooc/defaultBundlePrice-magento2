<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace thousandmonkeys\BundleDefaultPrice\Model\Product\Attribute\Source\Price;

use Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory;

/**
 * Bundle Price View Attribute Renderer
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class View extends \Magento\Bundle\Model\Product\Attribute\Source\Price\View
{
    /**
     * @param OptionFactory $optionFactory
     */
    public function __construct(OptionFactory $optionFactory)
    {
        View::__construct($optionFactory);
    }

    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        if (null === $this->_options) {
            $this->_options = [
                ['label' => __('Price Range'), 'value' => 0],
                ['label' => __('As Low as'), 'value' => 1],
                ['label' => __('Default'), 'value' => 2],
            ];
        }
        return $this->_options;
    }
}
