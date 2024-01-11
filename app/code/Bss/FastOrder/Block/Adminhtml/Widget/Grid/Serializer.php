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

namespace Bss\FastOrder\Block\Adminhtml\Widget\Grid;

/**
 * Class Serializer
 * @package Bss\FastOrder\Block\Adminhtml\Widget\Grid
 */
class Serializer extends \Magento\Backend\Block\Widget\Grid\Serializer
{
    /**
     * Set serializer template
     *
     * @return void
     */
    public function _construct()
    {
        \Magento\Framework\View\Element\Template::_construct();
        $this->setTemplate('Bss_FastOrder::widget/grid/serializer.phtml');
    }
}
