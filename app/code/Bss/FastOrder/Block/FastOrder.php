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

namespace Bss\FastOrder\Block;

use Magento\Catalog\Model\Product\Type;
use Magento\Framework\View\Element\Template;

/**
 * Class FastOrder
 * @package Bss\FastOrder\Block
 */
class FastOrder extends Template
{
    /**
     * @var \Bss\FastOrder\Helper\ConfigurableProduct
     */
    protected $configurableProductHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Bss\FastOrder\Helper\Integrate
     */
    protected $integrateHelper;

    /**
     * @var \Bss\FastOrder\Helper\Data
     */
    protected $helper;

    /**
     * FastOrder constructor.
     * @param Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Bss\FastOrder\Helper\Integrate $integrateHelper
     * @param \Bss\FastOrder\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Bss\FastOrder\Helper\Integrate $integrateHelper,
        \Bss\FastOrder\Helper\Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->_isScopePrivate = true;
        $this->integrateHelper = $integrateHelper;
        $this->helper = $helper;
    }

    /**
     * Get Form Action
     *
     * @return string
     */
    public function getFormAction()
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {
            return $this->getUrl('fastorder/index/add', ['_secure' => true]);
        } else {
            return $this->getUrl('fastorder/index/add');
        }
    }

    /**
     * Get Url Import Csv
     *
     * @return string
     */
    public function getUrlCsv()
    {
        $fileName = 'import_fastorder.csv';
        $url = $this->getViewFileUrl('Bss_FastOrder::csv/bss/fastorder/' . $fileName);
        return $url;
    }

    /**
     * @return string
     */
    public function getCheckoutUrl()
    {
        return $this->getUrl('checkout');
    }

    /**
     * @return \Magento\Checkout\Model\Session
     */
    public function getSession()
    {
        return $this->checkoutSession;
    }

    /**
     * @return bool
     */
    public function isDisabled()
    {
        return !$this->getSession()->getQuote()->validateMinimumAmount();
    }

    /**
     * Get option template
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getOptionTemplate()
    {
        $product = $this->getProduct();
        $productType = $product->getTypeId();
        $sortOrder = $this->getSortOrder();
        $isEditPopup = $this->getIsEdit();
        $template = '';

        switch ($productType) {
            case 'configurable':
                $block = $this->integrateHelper->getConfigurableGridViewModuleBlock($isEditPopup);
                $template = $block->setProduct($product)->toHtml();
                break;
            case 'downloadable':
                $template = $this->getLayout()->createBlock(
                    \Magento\Downloadable\Block\Catalog\Product\Links::class,
                    '',
                    [
                        'data' => [
                            'sort_order' => $sortOrder
                        ]
                    ]
                )
                    ->setTemplate('Bss_FastOrder::downloadable.phtml')->setProduct($product)->toHtml();
                break;
            case 'grouped':
                $template = $this->getLayout()->createBlock(
                    \Magento\GroupedProduct\Block\Product\View\Type\Grouped::class,
                    '',
                    [
                        'data' => [
                            'sort_order' => $sortOrder
                        ]
                    ]
                )
                    ->setTemplate('Bss_FastOrder::grouped.phtml')
                    ->setProduct($product)->toHtml();
                break;
        }

        return $template;
    }

    /**
     * @return bool
     */
    public function isConfigurableGridViewModuleEnabled()
    {
        return $this->integrateHelper->isConfigurableGridViewModuleEnabled();
    }

    /**
     * @return bool
     */
    public function isRequestForQuoteModuleActive()
    {
        return $this->integrateHelper->isRequestForQuoteModuleActive();
    }

    /**
     * @return string
     */
    public function getAddToQuoteButtonText()
    {
        return $this->integrateHelper->getRequestForQuoteButtonText();
    }

    /**
     * @return string
     */
    public function getAddToQuoteButtonStyle()
    {
        return $this->integrateHelper->getRequestForQuoteButtonStyle();
    }

    /**
     * @return string
     */
    public function getHidePriceHelper()
    {
        return $this->integrateHelper->getHidePriceHelper();
    }

    /**
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isModuleEnabled()
    {
        return $this->helper->getConfig('enabled');
    }

    /**
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getFormTemplate()
    {
        return $this->helper->getConfig('list_template');
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getLineNumber()
    {
        return (int)$this->helper->getConfig('number_of_line');
    }

    /**
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMainColor()
    {
        return $this->helper->getConfig('main_color');
    }
    /**
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAutocompleteMinChar()
    {
        return (int)$this->helper->getConfig('automplete_min_char');
    }

    /**
     * @return string
     */
    public function getFormatPrice()
    {
        return $this->helper->getFormatPrice();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getUrlCheckout()
    {
        return $this->helper->getUrlCheckout();
    }

    /**
     * @return string
     */
    public function getJsonConfigPrice()
    {
        return $this->helper->getJsonConfigPrice($this->getProduct());
    }

    /**
     * @return string
     */
    public function getProductImage()
    {
        return $this->helper->getProductImage($this->getProduct());
    }

    /**
     * @return bool
     */
    public function isDisplayBothPrices()
    {
        return $this->helper->isDisplayBothPrices();
    }
}
