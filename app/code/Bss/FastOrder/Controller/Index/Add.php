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

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Framework\Controller\ResultFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Add extends \Magento\Checkout\Controller\Cart\Add
{
    /**
     * Helper
     *
     * @var \Bss\FastOrder\Helper\HelperAdd $helper
     */
    protected $helper;

    /**
     * @var \Bss\FastOrder\Helper\Cart
     */
    protected $cartHelper;

    /**
     * Add constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param CustomerCart $cart
     * @param ProductRepositoryInterface $productRepository
     * @param \Bss\FastOrder\Helper\HelperAdd $helper
     * @param \Bss\FastOrder\Helper\Cart $cartHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        CustomerCart $cart,
        ProductRepositoryInterface $productRepository,
        \Bss\FastOrder\Helper\HelperAdd $helper,
        \Bss\FastOrder\Helper\Cart $cartHelper
    ) {
    
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart,
            $productRepository
        );

        $this->helper = $helper;
        $this->cartHelper = $cartHelper;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Locale_Exception
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if (!$this->helper->getHelperBss()->getConfig('enabled')) {
            throw new \Magento\Framework\Exception\LocalizedException(__("Module is disabled"));
        }
        $productList = $this->cartHelper->getProductAndParamList();

        $result = [];
        $success = false;
        $productNames = [];
        foreach ($productList as $productData) {
            try {
                $product = $productData['product'];
                $params = $productData['params'];
                $productNames[] = '"' . $product->getName() . '"';
                $this->cart->addProduct($product, $params);
                $success = true;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $success = false;
                $this->helper->getRegistry()->unregister('row_product');
                $this->helper->getRegistry()->register('row_product', $productData['sortOrder']);

                $this->handleException($result, $e);
                $resultRedirect->setUrl($this->_redirect->getRefererUrl());
                $result['redirect'] = $resultRedirect;
                break;
            } catch (\Exception $e) {
                $success = false;
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('We can\'t add this item to your shopping cart right now.')
                );
                $this->helper->getLogger()->critical($e);
                $result['status'] = false;
                $result['row'] = $productData['sortOrder'];
                $resultRedirect->setUrl($this->_redirect->getRefererUrl());
                $result['redirect'] = $resultRedirect;
                break;
            }
        }

        $this->saveCart($success, $productNames, $result);
        if ($this->getRequest()->isAjax()) {
            unset($result['redirect']);
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $resultJson->setData($result);

            return $resultJson;
        } else {
            return $result['redirect'];
        }
    }

    /**
     * @param boolean $success
     * @param array $productNames
     * @param array $result
     */
    protected function saveCart($success, $productNames, &$result)
    {
        if ($success) {
            $this->cart->save();
            $result['status'] = true;
            $message = __(
                'You added %1 to your shopping cart.',
                join(', ', $productNames)
            );
            $this->messageManager->addSuccessMessage($message);
            if ($this->helper->getHelperBss()->getRedirectToCart()) {
                $cartUrl = $this->helper->getHelperCart()->getCartUrl();
                $result['redirect'] = $this->goBack($cartUrl);
            }
        } else {
            $result['status'] = false;
            if ($this->helper->getHelperBss()->getRedirectToCart()) {
                $result['redirect'] = $this->goBack($this->_redirect->getRefererUrl());
            }
        }
    }

    /**
     * Handle Exception
     *
     * @param array $result
     * @param mixed $e
     */
    protected function handleException(&$result, $e)
    {
        if ($this->_checkoutSession->getUseNotice(true)) {
            $this->messageManager->addNotice(
                $this->helper->getEscaper()->escapeHtml($e->getMessage())
            );
            $result['status'] = false;
            $result['row'] = $this->helper->getRegistry()->registry('row_product');
        } else {
            $messages = array_unique(explode("\n", $e->getMessage()));
            foreach ($messages as $message) {
                $this->messageManager->addError(
                    $this->helper->getEscaper()->escapeHtml($message)
                );
            }
            $result['status'] = false;
            $result['row'] = $this->helper->getRegistry()->registry('row_product');
        }
    }
}
