<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category  BSS
 * @package   Bss_FastOrder
 * @author    Extension Team
 * @copyright Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license   http://bsscommerce.com/Bss-Commerce-License.txt
 */

namespace Bss\FastOrder\Model\Search;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type as ProductType;

/**
 * Class ProductList
 *
 * @package Bss\FastOrder\Model\Search
 */
class ProductList
{
    /**
     * @var \Bss\FastOrder\Helper\Data
     */
    protected $helperBss;

    /**
     * @var Save
     */
    protected $searchModel;

    /**
     * @var \Magento\Catalog\Model\Layer\Resolver
     */
    protected $layerResolver;

    /**
     * @var \Magento\CatalogInventory\Helper\Stock
     */
    protected $stockFilter;

    /**
     * ProductList constructor.
     * @param Save $searchModel
     * @param \Bss\FastOrder\Helper\Data $helperBss
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param \Magento\CatalogInventory\Helper\Stock $stockFilter
     */
    public function __construct(
        Save $searchModel,
        \Bss\FastOrder\Helper\Data $helperBss,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\CatalogInventory\Helper\Stock $stockFilter
    ) {
        $this->searchModel = $searchModel;
        $this->helperBss = $helperBss;
        $this->layerResolver = $layerResolver;
        $this->stockFilter = $stockFilter;
    }

    /**
     * @param $queryText
     * @return array|bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSearchResult($queryText)
    {
        try {
            /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
            $productCollection = $this->layerResolver->get()->getProductCollection();

            $queryLike = $this->_getQueryPattern($queryText);

            $productCollection
                ->addFieldToFilter('status', Status::STATUS_ENABLED)
                ->addAttributeToFilter('type_id', ['neq' => ProductType::TYPE_BUNDLE])
                ->addAttributeToFilter(
                    [
                        ['attribute' => 'sku', 'like' => $queryLike],
                        ['attribute' => 'name', 'like' => $queryLike]
                    ]
                );
            $productCollection->getSelect()->limit($this->searchModel->getMaxResShow());

            $this->stockFilter->addInStockFilterToCollection($productCollection);
            $this->helperBss->getEventManager()->dispatch(
                'bss_prepare_product_collection',
                [
                    'collection' => $productCollection
                ]
            );

            $productCollection = $this->searchModel->getResData($productCollection);

            if (!empty($productCollection)) {
                $data = $productCollection->toArray([
                    'name',
                    'sku',
                    'entity_id',
                    'type_id',
                    'product_hide_price',
                    'product_hide_html',
                    'product_thumbnail',
                    'product_url',
                    'popup',
                    'product_price',
                    'product_price_amount',
                    'product_price_exc_tax_html',
                    'product_price_exc_tax',
                ]);
                return $data;
            }
            return false;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
            return false;
        }
    }

    /**
     * @param string $queryText
     * @return string
     */
    private function _getQueryPattern($queryText)
    {
        $queryText = preg_replace('/\s+/', '%', $queryText);
        $queryLike = '%' . $queryText . '%';
        return $queryLike;
    }
}
