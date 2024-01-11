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

use Magento\Framework\App\Helper\Context;

/**
 * Class Cart
 * @package Bss\FastOrder\Helper
 */
class Cart extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Bss\FastOrder\Helper\HelperAdd $helper
     */
    protected $helper;

    /**
     * @var \Bss\FastOrder\Filter\LocalizedToNormalized
     */
    protected $localizedToNormalized;

    /**
     * Cart constructor.
     * @param Context $context
     * @param \Bss\FastOrder\Filter\LocalizedToNormalized $localizedToNormalized
     * @param HelperAdd $helper
     */
    public function __construct(
        Context $context,
        \Bss\FastOrder\Filter\LocalizedToNormalized $localizedToNormalized,
        HelperAdd $helper
    ) {
    
        parent::__construct($context);
        $this->localizedToNormalized = $localizedToNormalized;
        $this->helper = $helper;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Locale_Exception
     */
    public function getProductAndParamList()
    {
        $request = $this->_request;
        $productIds = $request->getParam('productIds');
        $qtys = $request->getParam('qtys');
        $fastorderSuperAttribute = $request->getParam('bss-fastorder-super_attribute');
        $fastorderLinks = $request->getParam('bss_fastorder_links');
        $fastorderSuperGroup = $request->getParam('bss-fastorder-super_group');
        $fastorderCustomOption = $request->getParam('bss-fastorder-options');
        $result = [];
        $storeId = $this->helper->getHelperBss()->getStoreId();

        foreach ($productIds as $key => $productId) {
            if ($qtys[$key] <= 0 || !$productId) {
                continue;
            }
            $params = [];

            $product = $this->loadProduct($productId, $storeId);
            $params = $this->addOptionProduct(
                $params,
                $product,
                $fastorderSuperAttribute,
                $fastorderLinks,
                $fastorderSuperGroup,
                $key
            );
            // add custom option
            $params['options'] = $this->addCustomOption($fastorderCustomOption, $key, $product);
            $this->handleFilter($params, $qtys, $key);
            $params['product'] = $productId;
            $result[] = [
                'product' => $product,
                'params' => $params,
                'sortOrder' => $key
            ];
        }

        return $result;
    }

    /**
     * @param int $productId
     * @param int $storeId
     * @return \Magento\Catalog\Model\Product
     */
    protected function loadProduct($productId, $storeId)
    {
        return $this->helper->getProductFactory()->create()->setStoreId($storeId)->load($productId);
    }

    /**
     * @param  $params
     * @param  null $product
     * @param  null $fastorderSuperAttribute
     * @param  null $fastorderLinks
     * @param  null $fastorderSuperGroup
     * @param  null $key
     * @return bool
     */
    protected function addOptionProduct(
        $params,
        $product = null,
        $fastorderSuperAttribute = null,
        $fastorderLinks = null,
        $fastorderSuperGroup = null,
        $key = null
    ) {
        if ($product->getTypeId() == 'configurable' && !empty($fastorderSuperAttribute) && !empty($fastorderSuperAttribute[$key]) && isset($fastorderSuperAttribute[$key])) {
            $params['super_attribute'] = $fastorderSuperAttribute[$key];
        } elseif ($product->getTypeId() == 'downloadable' && !empty($fastorderLinks) && isset($fastorderLinks[$key])) {
            $params['links'] = $fastorderLinks[$key];
        } elseif ($product->getTypeId() == 'grouped' && !empty($fastorderSuperGroup) && isset($fastorderSuperGroup[$key])) {
            $params['super_group'] = $fastorderSuperGroup[$key];
        }
        if (!empty($params)) {
            return $params;
        }
        return false;
    }

    /**
     * @param  array|null $fastorderCustomOption
     * @param  int|null $key
     * @param  \Magento\Catalog\Model\Product $product
     * @return bool|mixed
     */
    protected function addCustomOption($fastorderCustomOption = null, $key = null, $product)
    {
        if (isset($fastorderCustomOption[$key])) {
            foreach ($fastorderCustomOption[$key] as $id => $value) {
                if (is_array($value) || is_int($value)) {
                    continue;
                }

                if ($this->isMultipleSelectCustomOption($id, $product)) {
                    // use explode function may return empty array with count is 1
                    $valueArr = preg_split('/,/', $value, null, PREG_SPLIT_NO_EMPTY);
                    if (!empty($valueArr)) {
                        $fastorderCustomOption[$key][$id] = $valueArr;
                    }
                } else {
                    $fastorderCustomOption[$key][$id] = $value;
                }
            }

            return $fastorderCustomOption[$key];
        }

        return false;
    }

    /**
     * @param  int $customOptionId
     * @param  \Magento\Catalog\Model\Product $product
     * @return bool
     */
    protected function isMultipleSelectCustomOption($customOptionId, $product)
    {
        foreach ($product->getOptions() as $o) {
            if ($o->getType() == 'multiple' && $o->getOptionId() == $customOptionId) {
                return true;
                break;
            }
        }
        return false;
    }

    /**
     * @param array $params
     * @param array $qtys
     * @param int $key
     * @throws \Zend_Locale_Exception
     */
    protected function handleFilter(&$params, $qtys, $key)
    {
        if (isset($qtys[$key])) {
            $filter = $this->localizedToNormalized;
            $params['qty'] = $filter->filter($qtys[$key]);
        }
    }
}
