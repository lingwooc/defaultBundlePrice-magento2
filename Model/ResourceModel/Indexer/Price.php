<?php

namespace ThousandMonkeys\BundleDefaultPrice\Model\ResourceModel\Indexer;
/**
 * Price data parser used for grouped and bundled products.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Price implements Magento\Bundle\Model\ResourceModel\Indexer\Price
{
   /**
     * Calculate bundle product selections price by product type
     *
     * @param int $priceType
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _calculateBundleSelectionPrice($priceType)
    {
        echo 'hi guys!';
        $connection = $this->getConnection();

        if ($priceType == \Magento\Bundle\Model\Product\Price::PRICE_TYPE_FIXED) {
            $selectionPriceValue = $connection->getCheckSql(
                'bsp.selection_price_value IS NULL',
                'bs.selection_price_value',
                'bsp.selection_price_value'
            );
            $selectionPriceType = $connection->getCheckSql(
                'bsp.selection_price_type IS NULL',
                'bs.selection_price_type',
                'bsp.selection_price_type'
            );
            $priceExpr = new \Zend_Db_Expr(
                $connection->getCheckSql(
                    $selectionPriceType . ' = 1',
                    'ROUND(i.price * (' . $selectionPriceValue . ' / 100),4)',
                    $connection->getCheckSql(
                        'i.special_price > 0 AND i.special_price < 100',
                        'ROUND(' . $selectionPriceValue . ' * (i.special_price / 100),4)',
                        $selectionPriceValue
                    )
                ) . '* bs.selection_qty'
            );

            $tierExpr = $connection->getCheckSql(
                'i.base_tier IS NOT NULL',
                $connection->getCheckSql(
                    $selectionPriceType . ' = 1',
                    'ROUND(i.base_tier - (i.base_tier * (' . $selectionPriceValue . ' / 100)),4)',
                    $connection->getCheckSql(
                        'i.tier_percent > 0',
                        'ROUND((1 - i.tier_percent / 100) * ' . $selectionPriceValue . ',4)',
                        $selectionPriceValue
                    )
                ) . ' * bs.selection_qty',
                'NULL'
            );

            $priceExpr = $connection->getLeastSql([
                $priceExpr,
                $connection->getIfNullSql($tierExpr, $priceExpr),
            ]);
        } else {
            $price = 'idx.min_price * bs.selection_qty';
            $specialExpr = $connection->getCheckSql(
                'i.special_price > 0 AND i.special_price < 100',
                'ROUND(' . $price . ' * (i.special_price / 100), 4)',
                $price
            );
            $tierExpr = $connection->getCheckSql(
                'i.tier_percent IS NOT NULL',
                'ROUND((1 - i.tier_percent / 100) * ' . $price . ', 4)',
                'NULL'
            );
            $priceExpr = $connection->getLeastSql([
                $specialExpr,
                $connection->getIfNullSql($tierExpr, $price),
            ]);
        }

        $linkField = $this->getMetadataPool()->getMetadata(ProductInterface::class)->getLinkField();
        $select = $connection->select()->from(
            ['i' => $this->_getBundlePriceTable()],
            ['entity_id', 'customer_group_id', 'website_id']
        )->join(
            ['parent_product' => $this->getTable('catalog_product_entity')],
            'parent_product.entity_id = i.entity_id',
            []
        )->join(
            ['bo' => $this->getTable('catalog_product_bundle_option')],
            "bo.parent_id = parent_product.$linkField",
            ['option_id']
        )->join(
            ['bs' => $this->getTable('catalog_product_bundle_selection')],
            'bs.option_id = bo.option_id',
            ['selection_id','is_default']
        )->joinLeft(
            ['bsp' => $this->getTable('catalog_product_bundle_selection_price')],
            'bs.selection_id = bsp.selection_id AND bsp.website_id = i.website_id',
            ['']
        )->join(
            ['idx' => $this->getIdxTable()],
            'bs.product_id = idx.entity_id AND i.customer_group_id = idx.customer_group_id' .
            ' AND i.website_id = idx.website_id',
            []
        )->join(
            ['e' => $this->getTable('catalog_product_entity')],
            'bs.product_id = e.entity_id AND e.required_options=0',
            []
        )->where(
            'i.price_type=?',
            $priceType
        )->columns(
            [
                'group_type' => $connection->getCheckSql("bo.type = 'select' OR bo.type = 'radio'", '0', '1'),
                'is_required' => 'bs.is_default',
                'price' => $priceExpr,
                'tier_price' => $tierExpr,
            ]
        );

        $query = $select->insertFromSelect($this->_getBundleSelectionTable());
        $connection->query($query);

        return $this;
    }
}