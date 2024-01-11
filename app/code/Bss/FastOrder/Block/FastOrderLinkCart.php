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
namespace Bss\FastOrder\Block;

/**
 * Class FastOrderLinkCart
 * @package Bss\FastOrder\Block\Order
 */
class FastOrderLinkCart extends \Magento\Framework\View\Element\Html\Link\Current
{
    /**
     * @var \Magento\Framework\App\DefaultPathInterface $defaultPath
     */
    protected $defaultPath;

    /**
     * @var \Bss\FastOrder\Helper\Data
     */
    protected $helperBss;

    /**
     * TopLink constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Bss\FastOrder\Helper\Data                       $helperBss
     * @param \Magento\Framework\App\DefaultPathInterface      $defaultPath
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Bss\FastOrder\Helper\Data $helperBss,
        \Magento\Framework\App\DefaultPathInterface $defaultPath,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $data);
        $this->helperBss = $helperBss;
    }

    /**
     * @return bool|string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _toHtml()
    {
        $value = explode(',', $this->helperBss->getConfig('active_fastorder_in'));
        if (!in_array('shopping-cart', $value)) {
            return false;
        }
        return $this->getLink();
    }

    /**
     * @return string
     */
    protected function getLink()
    {
        if (false != $this->getTemplate()) {
            return parent::_toHtml();
        }
        $link = rtrim($this->escapeHtml($this->getHref()), '/');
        $html = '<a class="minicart-wrapper" href="' . $link . '"';
        $html .= $this->getTitle()
                ? ' title="' . $this->escapeHtml(__($this->getTitle())) . '"'
                : '';
        $html .= '>';
        $html .= $this->escapeHtml(__($this->getLabel()));
        $html .= '</a>';

        return $html;
    }
}
