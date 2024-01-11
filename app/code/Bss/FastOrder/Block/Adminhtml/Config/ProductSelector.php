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

namespace Bss\FastOrder\Block\Adminhtml\Config;

/**
 * Class ProductSelector
 * @package Bss\FastOrder\Block\Adminhtml\Config
 */
class ProductSelector extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $elementFactory;

    /**
     * ProductSelector constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->elementFactory = $elementFactory;
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $htmlId = $element->getId();
        $data = $element->getData();
        $data['after_element_html'] = $this->getLayout()
            ->createBlock('Bss\FastOrder\Block\Adminhtml\Layout')
            ->toHtml();
        $htmlItem = $this->elementFactory->create('text', ['data' => $data]);
        $htmlItem
            ->setId("{$htmlId}")
            ->setForm($element->getForm())
            ->addClass('required-entry')
            ->addClass('entities');
        $return = <<<HTML
                <div id="{$htmlId}-container" class="bss_chooser_container">{$htmlItem->getElementHtml()}</div>
HTML;
        $element->setData('after_element_html', $return);
        return $element->getElementHtml();
    }
}
