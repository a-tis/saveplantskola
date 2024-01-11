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

namespace Bss\FastOrder\Controller\Integrate;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddToQuote extends \Magento\Checkout\Controller\Cart\Add
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
     * @var \Bss\FastOrder\Helper\Integrate
     */
    protected $integrateHelper;

    /**
     * AddToQuote constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param CustomerCart $cart
     * @param ProductRepositoryInterface $productRepository
     * @param \Bss\FastOrder\Helper\HelperAdd $helper
     * @param \Bss\FastOrder\Helper\Cart $cartHelper
     * @param \Bss\FastOrder\Helper\Integrate $integrateHelper
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
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
        \Bss\FastOrder\Helper\Cart $cartHelper,
        \Bss\FastOrder\Helper\Integrate $integrateHelper
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
        $this->integrateHelper = $integrateHelper;
    }

    /**
     * @return bool|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $quoteModel = $this->integrateHelper->getRequestForQuoteModel();
        $quoteHelper = $this->integrateHelper->getRequestForQuoteHelper();
        if (!$this->helper->getHelperBss()->getConfig('enabled')) {
            throw new LocalizedException(__("Module is disabled"));
        }
        $productList = $this->cartHelper->getProductAndParamList();

        $result = [];
        $success = false;
        $productNames = [];
        foreach ($productList as $productData) {
            $product = $productData['product'];

            try {
                if (!$quoteHelper->isActiveRequest4Quote($product)) {
                    $this->helper->getRegistry()->register('row_product', $productData['sortOrder']);
                    throw new LocalizedException(
                        __('You can not add products to quote: ' . $product->getName())
                    );
                }
                $params = $productData['params'];
                $productNames[] = '"' . $product->getName() . '"';
                $quoteModel->addProduct($product, $params);

                $success = true;
            } catch (LocalizedException $e) {
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
                    __('We can\'t add this item to your Quote right now.')
                );
                $this->helper->getLogger()->critical($e);
                $result['status'] = false;
                $result['row'] = $productData['sortOrder'];
                $resultRedirect->setUrl($this->_redirect->getRefererUrl());
                $result['redirect'] = $resultRedirect;
                break;
            }
        }

        $this->saveQuote($success, $productNames, $result);

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
    protected function saveQuote($success, $productNames, &$result)
    {
        if ($success) {
            $this->integrateHelper->getRequestForQuoteModel()->save();
            $result['status'] = true;
            $message = __(
                'You added %1 to your Quote.',
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
        $message = $e->getMessage();

        // modify Default qty validation messages
        if (stripos($message, "The fewest you may purchase is") !== false) {
            $message = str_replace(
                "The fewest you may purchase is",
                "The fewest you may quote",
                $message
            );
        }
        if (stripos($message, "The requested qty exceeds the maximum qty allowed in shopping cart") !== false) {
            $message = str_replace(
                "The requested qty exceeds the maximum qty allowed in shopping cart",
                "The requested qty exceeds the maximum qty allowed in quote cart",
                $message
            );
        }
        if (stripos($message, "You can buy this product only in quantities of") !== false) {
            $message = str_replace(
                "You can buy this product only in quantities of",
                "You can quote this product only in quantities of",
                $message
            );
        }

        if ($this->_checkoutSession->getUseNotice(true)) {
            $this->messageManager->addNotice(
                $this->helper->getEscaper()->escapeHtml($message)
            );
            $result['status'] = false;
            $result['row'] = $this->helper->getRegistry()->registry('row_product');
        } else {
            $messages = array_unique(explode("\n", $message));
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
