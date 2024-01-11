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

namespace Bss\FastOrder\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type as ProductType;

/**
 * Class GetProductWithSku
 *
 * @package Bss\FastOrder\Controller\Index
 */
class GetProductWithSku extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Bss\FastOrder\Model\Search\Save
     */
    protected $saveModel;

    /**
     * @var \Bss\FastOrder\Helper\Data
     */
    protected $helperBss;

    /**
     * @var \Bss\FastOrder\Model\Search\ProductList
     */
    protected $productList;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollection;

    /**
     * GetProductWithSku constructor.
     * @param Context $context
     * @param \Bss\FastOrder\Helper\Data $helperBss
     * @param \Bss\FastOrder\Model\Search\ProductList $productList
     * @param \Bss\FastOrder\Model\Search\Save $saveModel
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection
     */
    public function __construct(
        Context $context,
        \Bss\FastOrder\Helper\Data $helperBss,
        \Bss\FastOrder\Model\Search\ProductList $productList,
        \Bss\FastOrder\Model\Search\Save $saveModel,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection
    )
    {
        parent::__construct($context);
        $this->helperBss = $helperBss;
        $this->productList = $productList;
        $this->saveModel = $saveModel;
        $this->productCollection = $productCollection;
    }

    /**
     * @return bool|ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $productParams = $this->getRequest()->getParam('product');
        if (empty($productParams)) {
            $this->messageManager->addErrorMessage(
                __("Please select product(s).")
            );
            $resultJson->setData([]);
            return $resultJson;
        }

        $data = [];
        $skuErrors = [];
        $skuData = [];
        $qtyData = [];
        $this->getSkuData($productParams, $skuData, $qtyData, $skuErrors);

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
        $productCollection = $this->productCollection->create();

        $productCollection
            ->addFieldToSelect('*')
            ->addFieldToFilter('status', Status::STATUS_ENABLED)
            ->addAttributeToFilter('type_id', ['neq' => ProductType::TYPE_BUNDLE])
            ->addFieldToFilter('sku', ['in' => $skuData]);
        $productCollection = $this->saveModel->getResData($productCollection, true);

        foreach ($productCollection as $product) {
            $productSku = $product->getSku();
            $qty = 1;
            if (!empty($qtyData[$productSku])) {
                $qty = $qtyData[$productSku];
            }
            $product->setQty($qty);
        }

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
                'qty',
                'popup_html'
            ]);
        }

        if (!empty($skuErrors)) {
            $this->messageManager->addErrorMessage(
                __("SKU is not found or out of stock: %1", join(',', $skuErrors))
            );
        }

        $resultJson->setData($data);
        return $resultJson;
    }

    /**
     * @param array $productParams
     * @param array $skuData
     * @param array $qtyData
     * @param array $skuErrors
     */
    private function getSkuData($productParams, &$skuData, &$qtyData, &$skuErrors)
    {
        foreach ($productParams as $product) {
            if (empty($product['sku'])) {
                continue;
            }
            $sku = $product['sku'];
            if (!$this->saveModel->checkSkuExist($sku)) {
                $skuErrors[] = $sku;
                continue;
            }

            $skuData[] = $sku;

            if (empty($product['qty'])) {
                $qty = 1;
            } else {
                $qty = $product['qty'];
            }

            $qtyData[$sku] = $qty;
        }
    }
}
