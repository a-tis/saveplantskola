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

namespace Bss\FastOrder\Block\Adminhtml;

/**
 * Class Layout
 * @package Bss\FastOrder\Block\Adminhtml
 */
class Layout extends \Magento\Widget\Block\Adminhtml\Widget\Instance\Edit\Tab\Main\Layout
{
    protected $_template = 'Bss_FastOrder::layout.phtml';
    
    /**
     * @return string
     */
    public function getProductsChooserUrl()
    {
        return $this->getUrl('fastorder/widget_instance/products', ['_current' => true]);
    }
}
