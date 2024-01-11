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

namespace Bss\FastOrder\Helper;

/**
 * Class Integrate
 * @package Bss\FastOrder\Helper
 */
class Integrate extends \Magento\Framework\App\Helper\AbstractHelper
{
    //@codingStandardsIgnoreStart
    /**
     * @var \Magento\Framework\Module\Manager
     */
    private $objectManager;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    private $layout;

    /**
     * Integrate constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\View\LayoutInterface $layout
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\View\LayoutInterface $layout
    ) {
        parent::__construct($context);
        $this->objectManager = $objectManager;
        $this->layout = $layout;
    }

    /**
     * @return bool
     */
    public function isConfigurableGridViewModuleEnabled()
    {
        if ($this->_moduleManager->isEnabled('Bss_ConfiguableGridView')) {
            $helper = $this->objectManager->create(\Bss\ConfiguableGridView\Helper\Data::class);
            if ($helper->isEnabled()) {
                return true;
            }
        }

        return false;
    }


    /**
     * @param $isEditPopup
     * @return \Magento\Framework\View\Element\BlockInterface
     */
    public function getConfigurableGridViewModuleBlock($isEditPopup)
    {
        $block = $this->layout->createBlock('Bss\FastOrder\Block\Product\Renderer\Configurable');
        if ($this->isConfigurableGridViewModuleEnabled() && $isEditPopup != 'true') {
            $block = $this->layout->createBlock('Bss\ConfiguableGridView\Block\Product\View\Configurable');
        }

        return $block;
    }

    /**
     * @return bool
     */
    public function isRequestForQuoteModuleEnabled()
    {
        if ($this->_moduleManager->isEnabled('Bss_QuoteExtension')) {
            $helper = $this->objectManager->create(\Bss\QuoteExtension\Helper\Data::class);
            return $helper->isEnable();
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isRequestForQuoteModuleActive()
    {
        if ($this->isRequestForQuoteModuleEnabled()) {
            $configShowHelper = $this->objectManager->create(\Bss\QuoteExtension\Helper\Admin\ConfigShow::class);
            return $configShowHelper->isEnableOtherPage();
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getRequestForQuoteButtonText()
    {
        $helperShow = $this->objectManager->create(\Bss\QuoteExtension\Helper\Admin\ConfigShow::class);
        return $helperShow->getOtherPageText() ? $helperShow->getOtherPageText() : __('Add to Quote');
    }

    /**
     * @return mixed
     */
    public function getRequestForQuoteButtonStyle()
    {
        $helperShow = $this->objectManager->create(\Bss\QuoteExtension\Helper\Admin\ConfigShow::class);
        return $helperShow->getOtherPageCustomStyle();
    }

    /**
     * @return mixed
     */
    public function getRequestForQuoteModel()
    {
        return $this->objectManager->create(\Bss\QuoteExtension\Model\QuoteExtension::class);
    }

    /**
     * @return mixed
     */
    public function getRequestForQuoteHelper()
    {
        return $this->objectManager->create(\Bss\QuoteExtension\Helper\Data::class);
    }
}
//@codingStandardsIgnoreEnd
