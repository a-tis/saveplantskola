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
 * Class AddMultipleProduct
 *
 * @package Bss\FastOrder\Controller\Index
 */
class AddPrepopulatedData extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Bss\FastOrder\Helper\ConfigurableProduct
     */
    protected $configurableProductHelper;

    /**
     * @var \Bss\FastOrder\Model\Search\Save
     */
    protected $saveModel;

    /**
     * @var \Bss\FastOrder\Helper\PrepopulatedData
     */
    protected $prepopulatedDataHelper;

    /**
     * AddPrepopulatedData constructor.
     * @param Context $context
     * @param \Bss\FastOrder\Helper\PrepopulatedData $prepopulatedDataHelper
     * @param \Bss\FastOrder\Helper\ConfigurableProduct $configurableProductHelper
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Bss\FastOrder\Model\Search\Save $saveModel
     */
    public function __construct(
        Context $context,
        \Bss\FastOrder\Helper\PrepopulatedData $prepopulatedDataHelper,
        \Bss\FastOrder\Helper\ConfigurableProduct $configurableProductHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Bss\FastOrder\Model\Search\Save $saveModel
    ) {
        parent::__construct($context);
        $this->configurableProductHelper = $configurableProductHelper;
        $this->productRepository = $productRepository;
        $this->prepopulatedDataHelper = $prepopulatedDataHelper;
        $this->saveModel = $saveModel;
    }

    /**
     * @return ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $skuList = $this->prepopulatedDataHelper->getPrepopulated();
        $data = $this->getProductManual($skuList);
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($data);
        return $resultJson;
    }

    /**
     * @param $skuData
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getProductManual($skuData)
    {
        $data = [];
        $itemAdded = 0;
        $productCollection = $this->saveModel->getStandardProductCollection($skuData, true);
        foreach ($productCollection as $product) {
            if ($parentProductId = $this->configurableProductHelper->getParentProductId($product->getId())) {
                // handle for child of configurable product
                $parentProduct = $this->productRepository->getById($parentProductId);
                $childData = $this->configurableProductHelper->getChildProductData(
                    $parentProduct,
                    $product
                );
                if (!empty($childData)) {
                    $product->setData($childData->getData());
                }
            }

            $qty = 1;

            $product->setQty($qty);

            $itemAdded++;
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
                'popup_html',
                'configurable_attributes',
                'child_product_id'
            ]);
        }
        return $data;
    }
}
