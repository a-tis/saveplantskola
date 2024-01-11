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

namespace Bss\FastOrder\Controller\Index;

use Magento\Framework\Controller\ResultFactory;

/**
 * Class ProductAdditionalData
 * @package Bss\FastOrder\Controller\Index
 */
class ProductAdditionalData extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Bss\FastOrder\Helper\Data
     */
    private $helper;

    /**
     * @var \Bss\FastOrder\Controller\Index\Option
     */
    private $optionLayout;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * ProductAdditionalData constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Bss\FastOrder\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Bss\FastOrder\Helper\Data $helper,
        \Bss\FastOrder\Controller\Index\Option $optionLayout,
        \Magento\Framework\Registry $registry
    )
    {
        parent::__construct($context);
        $this->productRepository = $productRepository;
        $this->helper = $helper;
        $this->optionLayout = $optionLayout;
        $this->registry = $registry;
    }

    /**
     * Response rest of product data: tier price, validate, ...
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $productSku = $this->_request->getParam('sku', null);
        $hasPopup = $this->_request->getParam('has_popup', false);

        try {
            $product = $this->productRepository->get($productSku);
            $isPreOrder = $this->helper->isPreOrder();

            $validators['required-number'] = true;
            $stockItem = $this->helper->getStockItem($product);
            $validatorQty = [];
            $validatorQty['minAllowed'] = max((float)$stockItem->getQtyMinAllowed(), 1);
            $this->helper->addDataParams($validatorQty, $stockItem, $product);
            $validators['validate-item-quantity'] = $validatorQty;
            $tierPrices = $this->helper->getDataTierPrice($product);

            $data = [
                'data_validate' => $this->helper->getJson()->serialize($validators),
                'is_qty_decimal' => (int)$stockItem->getIsQtyDecimal(),
                'pre_order' => $isPreOrder,
                'price_data' => $tierPrices,
            ];

            if ($hasPopup) {
                $this->addPopupHtmlToResult($data, $product);
            }

        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $data = [];
        } catch (\Exception $e) {
            $data = [];
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($data);

        return $resultJson;
    }

    /**
     * @param array $data
     * @param \Magento\Catalog\Model\Product $product
     */
    private function addPopupHtmlToResult(&$data, $product)
    {
        $this->registry->unregister('current_product');
        $this->registry->register('current_product', $product);
        $this->helper->getEventManager()->dispatch('bss_prepare_product_price', ['product' => $product]);
        $layout = $this->optionLayout->getLayout();
        $layout->getUpdate()->addHandle('default');
        $popupHtml = $layout->createBlock(
            \Bss\FastOrder\Block\FastOrder::class,
            'fastorder.popup.data',
            ['data' => ['is_edit' => 'false', 'sort_order' => 0]]
        )
            ->setProduct($product)
            ->setTemplate('Bss_FastOrder::option.phtml')
            ->toHtml();

        $data['popup_html'] = $popupHtml;
    }
}
