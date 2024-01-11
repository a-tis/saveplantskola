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
 * @category   BSS
 * @package    Bss_FastOrder
 * @author     Extension Team
 * @copyright  Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */

namespace Bss\FastOrder\Model\Search;

use Bss\FastOrder\Block\FastOrder;
use Bss\FastOrder\Controller\Index\Option;
use Bss\FastOrder\Helper\Data;
use Bss\FastOrder\Helper\HelperSearchSave;
use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;

/**
 * Class Save
 * @package Bss\FastOrder\Model\Search
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Save
{
    /**
     * @var Data
     */
    private $helperData;

    /**
     * @var HelperSearchSave
     */
    private $helperSave;

    /**
     * @var Option
     */
    private $optionLayout;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var CollectionFactory
     */
    private $productCollection;

    /**
     * @var ProductRepository
     */
    private $product;

    /**
     * @var StockItemRepository
     */
    private $stockItem;

    /**
     * @var \Magento\CatalogInventory\Helper\Stock
     */
    private $stockFilter;

    /**
     * Save constructor.
     * @param Data $helperData
     * @param HelperSearchSave $helperSave
     * @param Option $optionLayout
     * @param ProductRepository $productRepository
     * @param StockItemRepository $stockItemRepository
     * @param Registry $registry
     * @param ResourceConnection $resourceConnection
     * @param CollectionFactory $productCollection
     * @param \Magento\CatalogInventory\Helper\Stock $stockFilter
     */
    public function __construct(
        Data $helperData,
        HelperSearchSave $helperSave,
        Option $optionLayout,
        ProductRepository $productRepository,
        StockItemRepository $stockItemRepository,
        Registry $registry,
        ResourceConnection $resourceConnection,
        CollectionFactory $productCollection,
        \Magento\CatalogInventory\Helper\Stock $stockFilter
    ) {
        $this->helperData = $helperData;
        $this->helperSave = $helperSave;
        $this->optionLayout = $optionLayout;
        $this->product = $productRepository;
        $this->stockItem = $stockItemRepository;
        $this->registry = $registry;
        $this->resourceConnection = $resourceConnection;
        $this->productCollection = $productCollection;
        $this->stockFilter = $stockFilter;
    }

    /**
     * @return bool|int|mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getMaxResShow()
    {
        $maxRes = ($this->helperData->getConfig('max_results_show') > 0) ?
            $this->helperData->getConfig('max_results_show') : 5;
        return $maxRes;
    }

    /**
     * @param Product $product
     * @return int
     */
    private function getShowPopup($product)
    {
        $showPopup = 0;

        if ($product->getBssHidePrice()) {
            return 0;
        }

        if ($product->getHasOptions()) {
            $showPopup = 1;
        }
        if ($product->getTypeId() == 'configurable' || $product->getTypeId() == 'grouped') {
            $showPopup = 1;
        }
        if ($product->getTypeId() == 'downloadable' &&
            $product->getTypeInstance()->getLinkSelectionRequired($product)
        ) {
            $showPopup = 1;
        }
        return $showPopup;
    }

    /**
     * @param $collection
     * @param bool $includePopupHtml
     * @param string $image
     * @return mixed
     */
    public function getResData($collection, $includePopupHtml = false, $image = 'category_page_grid')
    {
        foreach ($collection as $product) {
            $this->_getProductData(
                $product,
                $includePopupHtml,
                $image
            );
        }
        return $collection;
    }

    /**
     * @param Product $product
     * @param bool $includePopupHtml
     * @param string $image
     * @return mixed
     */
    public function _getProductData($product, $includePopupHtml = false, $image = 'category_page_grid')
    {
        $this->helperData->getEventManager()->dispatch('bss_prepare_product_price', ['product' => $product]);
        $showPopup = $this->getShowPopup($product);
        $productUrl = $product->getUrlModel()->getUrl($product);
        $productThumbnail = $this->helperSave->getImageHelper()->init($product, $image)->getUrl();
        $productPrice = '';
        $productPriceHtml = '';
        $this->helperData->getPriceHtml($product, $productPriceHtml, $productPrice);

        $productPriceExcTaxHtml = '';
        $productPriceExcTax = '';
        $this->helperData->getTaxHtml($product, $productPriceExcTaxHtml, $productPriceExcTax);
        $product->setData('product_hide_price', $product->getBssHidePrice() ? 1 : 0);
        $product->setData('product_hide_html', $product->getBssHidePriceHtml());
        $product->setData('product_thumbnail', $productThumbnail);
        $product->setData('product_url', $productUrl);
        $product->setData('popup', $showPopup);

        if (!$product->getBssHidePrice()) {
            $product->setData('product_price', $productPriceHtml);
            $product->setData('product_price_amount', $productPrice);
            $product->setData('product_price_exc_tax_html', $productPriceExcTaxHtml);
            $product->setData('product_price_exc_tax', $productPriceExcTax);
        }

        if ($showPopup && $includePopupHtml) {
            $this->addPopupHtmlToResult($product);
        }

        return $product;
    }

    /**
     * @param $sku
     * @return ProductInterface|Product|null
     */
    public function getProductBySku($sku){
        try {
            return $this->product->get($sku);
        } catch (NoSuchEntityException $e) {
            $this->helperData->getMessage()
                ->addErrorMessage(__('Product Sku is not valid'));
        }
    }
    /**
     * @param $sku
     * @return int
     */
    public function checkDisableProduct($sku)
    {
        return $this->getProductBySku($sku)->getStatus();
    }


    /**
     * @param $sku
     * @return bool|int
     */
    public function checkStockStatus($sku)
    {
        $id = $this->getProductBySku($sku)->getId();
        try {
            return $this->stockItem->get($id)->getIsInStock();
        } catch (NoSuchEntityException $e) {
            $this->helperData->getMessage()
                ->addErrorMessage(__('Product is not valid'));
        }
    }
    /**
     * @param string $sku
     * @return string
     */
    public function checkSkuExist($sku)
    {
        $connection = $this->resourceConnection->getConnection();
        $catalogTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $selectSkuQuery = "(SELECT sku FROM $catalogTable WHERE $catalogTable.sku='$sku')";

        return $connection->fetchOne($selectSkuQuery);
    }

    /**
     * @param Product $product
     */
    public function addPopupHtmlToResult(&$product)
    {
        try {
            $this->registry->unregister('current_product');
            $this->registry->register('current_product', $product);

            $layout = $this->optionLayout->getLayout();
            $layout->getUpdate()->addHandle('default');
            $popupHtml = $layout->createBlock(
                FastOrder::class,
                'fastorder.popup.data',
                ['data' => ['is_edit' => false, 'sort_order' => 0]]
            )
                ->setProduct($product)
                ->setTemplate('Bss_FastOrder::option.phtml')
                ->toHtml();
            $product->setData('popup_html', $popupHtml);
        } catch (Exception $exception) {

        }
    }

    /**
     * @param array $skuData
     * @param bool $includePopupHtml
     * @return Collection|mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getStandardProductCollection($skuData, $includePopupHtml = false)
    {
        /** @var Collection $productCollection */
        $productCollection = $this->productCollection->create();

        $productCollection
            ->addFieldToSelect('*')
            ->addAttributeToFilter('type_id', ['neq' => ProductType::TYPE_BUNDLE])
            ->addFieldToFilter('sku', ['in' => $skuData]);
        $productCollection->setFlag('has_stock_status_filter', false);
        $this->stockFilter->addInStockFilterToCollection($productCollection);
        $productCollection = $this->getResData($productCollection, $includePopupHtml);

        return $productCollection;
    }
}
