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

namespace Bss\FastOrder\Helper;

/**
 * Class ConfigurableProduct
 * @package Bss\FastOrder\Helper
 */
class ConfigurableProduct extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Bss\FastOrder\Helper\Data
     */
    protected $helperBss;

    /**
     * @var \Bss\FastOrder\Helper\HelperSearchSave
     */
    protected $helperSave;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $json;

    /**
     * @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable
     */
    protected $configurableProductType;

    /**
     * @var \Bss\FastOrder\Model\Search\Save
     */
    protected $saveModel;

    /**
     * ConfigurableProduct constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param Data $helperBss
     * @param HelperSearchSave $helperSave
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableProductType
     * @param \Bss\FastOrder\Model\Search\Save $saveModel
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        Data $helperBss,
        HelperSearchSave $helperSave,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableProductType,
        \Bss\FastOrder\Model\Search\Save $saveModel
    ) {
    
        parent::__construct($context);
        $this->helperBss = $helperBss;
        $this->helperSave = $helperSave;
        $this->json = $json;
        $this->configurableProductType = $configurableProductType;
        $this->saveModel = $saveModel;
    }

    /**
     * Get Child Product data as a Configurable product
     * @param int $parentProductId
     * @param array $productListParams [sku => 'ABC', qty => 2]
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMultiChildProductData($parentProductId, $productListParams)
    {
        // get parent product
        $parentProduct = $this->getProductRepositoryInterface()->getById($parentProductId);
        $productSkuList = array_column($productListParams, 'id');
        if (empty($productSkuList)) {
            return [];
        }
        $result = [];
        $productData = $this->getProductData($parentProduct);

        foreach ($productListParams as $productParam) {
            $this->helperBss->getEventManager()->dispatch('bss_prepare_product_price', ['product' => $parentProduct]);
            $childProduct = $this->getProductRepositoryInterface()->getById($productParam['id']);
            $qty = $productParam['qty'];
            // get configurable attributes of each child product
            $attributeData = $this->getAttributeData($childProduct);
            // add Qty & Attribute Data of Child Product to Parent product data
            $childProductData = $productData;
            $childProductData['qty'] = $qty;
            $childProductData['configurable_attributes'] = $attributeData;
            $productThumbnail = $this->getProductThumbnail($childProduct);
            $childProductData['product_thumbnail'] = $productThumbnail;

            $productPrice = '';
            $productPriceHtml = '';
            $this->helperBss->getPriceHtml($childProduct, $productPriceHtml, $productPrice);

            $productPriceExcTaxHtml = '';
            $productPriceExcTax = '';
            $this->helperBss->getTaxHtml($childProduct, $productPriceExcTaxHtml, $productPriceExcTax);

            $childProductData['product_price'] = $productPriceHtml;
            $childProductData['product_price_amount'] = $productPrice;
            $childProductData['product_price_amount'] = $productPrice;
            $childProductData['product_price_exc_tax_html'] = $productPriceExcTaxHtml;
            $childProductData['product_price_exc_tax'] = $productPriceExcTax;
            $childProductData['child_product_id'] = $childProduct->getId();

            $childProductData['product_hide_price'] = $parentProduct->getBssHidePrice() ? 1 : 0;
            $childProductData['product_hide_html'] = $parentProduct->getBssHidePriceHtml();
            $showPopup = $parentProduct->getBssHidePrice() ? 0 : 1;
            $childProductData['popup'] = $showPopup;

            $result[] = $childProductData;
        }

        return $result;
    }

    /**
     * @param $parentProduct
     * @param $childProduct
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getChildProductData($parentProduct, $childProduct)
    {
        // get configurable attributes of each child product
        $attributeData = $this->getAttributeData($childProduct);
        // add Qty & Attribute Data of Child Product to Parent product data
        $childProduct->setData('configurable_attributes', $attributeData);
        $productThumbnail = $this->getProductThumbnail($childProduct);
        $childProduct->setData('product_thumbnail', $productThumbnail);
        $childProductId = $childProduct->getId();
        $productPrice = '';
        $productPriceHtml = '';
        $this->helperBss->getPriceHtml($childProduct, $productPriceHtml, $productPrice);

        $productPriceExcTaxHtml = '';
        $productPriceExcTax = '';
        $this->helperBss->getTaxHtml($childProduct, $productPriceExcTaxHtml, $productPriceExcTax);

        $this->helperBss->getEventManager()->dispatch('bss_prepare_product_price', ['product' => $parentProduct]);
        $childProduct->setData('product_hide_price', $parentProduct->getBssHidePrice() ? 1 : 0);
        $childProduct->setData('product_hide_html', $parentProduct->getBssHidePriceHtml());
        $showPopup = 0;
        $childProduct->setData('popup', $showPopup);

        $childProduct->setData('name', $parentProduct->getName());
        $childProduct->setData('entity_id', $parentProduct->getId());
        $childProduct->setData('sku', $parentProduct->getSku());
        $childProduct->setData('type_id', $parentProduct->getTypeId());
        $childProduct->setData('product_url', $parentProduct->getProductUrl());

        $childProduct->setData('product_price', $productPriceHtml);
        $childProduct->setData('product_price_amount', $productPrice);
        $childProduct->setData('product_price_exc_tax_html', $productPriceExcTaxHtml);
        $childProduct->setData('product_price_exc_tax', $productPriceExcTax);
        $childProduct->setData('child_product_id', $childProductId);
        $this->saveModel->addPopupHtmlToResult($parentProduct);
        $childProduct->setData('popup_html', $parentProduct->getData('popup_html'));
        return $childProduct;
    }

    /**
     * @return \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected function getProductRepositoryInterface()
    {
        return $this->helperSave->getProductRepositoryInterface();
    }

    /**
     * @param \Magento\Catalog\Model\Product $childProduct
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getAttributeData($childProduct)
    {
        $attributes = $childProduct->getAttributes();
        $attributeData = [];

        foreach ($attributes as $attribute) {
            /** @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute */
            if ($attribute->getIsUserDefined() && $attribute->getFrontendInput() == 'select') {
                $attributeValue = $attribute->getFrontend()->getValue($childProduct);
                $optionId = $attribute->getSource()->getOptionId($attributeValue);

                if (!$optionId) continue; // on Magento 2.3.3, show unexpected options without option ID

                $attributeData[$optionId] = [
                    'value' => $attributeValue,
                    'label' => $attribute->getDefaultFrontendLabel(),
                    'id' => $attribute->getAttributeId()
                ];
            }
        }

        return $attributeData;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getProductData($product)
    {
        $productSkuHigh = '';
        $productId = $product->getId();
        $productName = $product->getName();
        $productSku = $product->getSku();
        $isPreOrder = $this->helperBss->isPreOrder();
        $tierPrices = $this->helperBss->getDataTierPrice($product);
        $productUrl = $product->getUrlModel()->getUrl($product);
        $validators = [];
        $validators['required-number'] = true;
        $stockItem = $this->helperBss->getStockItem($product);
        $params = [];
        $params['minAllowed'] = max((float)$stockItem->getQtyMinAllowed(), 1);
        $this->helperBss->addDataParams($params, $stockItem, $product);
        $validators['validate-item-quantity'] = $params;

        $data = [
            'popup' => 1,
            'name' => $productName,
            'sku' => $productSku,
            'product_id' => $productId,
            'entity_id' => $productId,
            'product_url' => $productUrl,
            'type_id' => $product->getTypeId(),
            'tier_price_' . $productId => $tierPrices,
            'product_sku_highlight' => $productSkuHigh,
            'data_validate' => $this->json->serialize($validators),
            'is_qty_decimal' => (int)$stockItem->getIsQtyDecimal(),
            'pre_order' => $isPreOrder
        ];

        return $data;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    protected function getProductThumbnail($product)
    {
        return $this->helperSave->getImageHelper()->init($product, 'category_page_grid')->getUrl();
    }

    /**
     * @param $childProductId
     * @return int|null
     */
    public function getParentProductId($childProductId)
    {
        $parentIds = $this->configurableProductType->getParentIdsByChild($childProductId);
        if ($parentIds) {
            return $parentIds[0];
        }

        return null;
    }
}
