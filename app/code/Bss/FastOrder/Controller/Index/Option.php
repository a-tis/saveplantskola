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

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class Option
 * @package Bss\FastOrder\Controller\Index
 */
class Option extends \Magento\Framework\App\Action\Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Bss\FastOrder\Helper\Data
     */
    protected $helperBss;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $catalogModelProduct;

    /**
     * Option constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Bss\FastOrder\Helper\Data $helperBss
     * @param \Magento\Catalog\Model\Product $catalogModelProduct
     * @param PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Bss\FastOrder\Helper\Data $helperBss,
        \Magento\Catalog\Model\Product $catalogModelProduct,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->helperBss = $helperBss;
        $this->storeManager = $storeManager;
        $this->resultPageFactory = $resultPageFactory;
        $this->catalogModelProduct = $catalogModelProduct;
    }

    /**
     * @return bool|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        if (!$this->helperBss->getConfig('enabled')) {
            return false;
        }
        $storeId = $this->storeManager->getStore()->getId();
        $productId = $this->getRequest()->getParam('productId');
        $sortOrder = $this->getRequest()->getParam('sortOrder');
        $isEdit = $this->getRequest()->getParam('isEdit') == 'true' ? 'true' : 'false';
        $product = $this->catalogModelProduct->setStoreId($storeId)->load($productId);
        $this->helperBss->getEventManager()->dispatch('bss_prepare_product_price', ['product' => $product]);
        $html = $this->getLayout()
                ->createBlock(
                    \Bss\FastOrder\Block\FastOrder::class,
                    '',
                    ['data' => ['sort_order' => $sortOrder, 'is_edit' => $isEdit]]
                )
                ->setTemplate('Bss_FastOrder::option.phtml')
                ->setProduct($product)
                ->toHtml();
        $result = [];
        $result['popup_option'] = $html;
        $result['type'] = $product->getTypeId();
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($result);

        return $resultJson;
    }

    /**
     * @return \Magento\Framework\View\LayoutInterface
     * @codingStandardsIgnoreStart
     */
    public function getLayout()
    {
        $resultPage = $this->resultPageFactory->create();
        return $resultPage->getLayout();
    }
    //@codingStandardsIgnoreEnd
}
