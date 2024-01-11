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
namespace Bss\FastOrder\Model\Config\Source;

/**
 * Class FastOrderLinkPosition
 *
 * @package Bss\FastOrder\Model\Config\Source
 */
class FastOrderLinkPosition implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'shopping-cart', 'label' => __('Near mini cart')],
            ['value' => 'top-menu', 'label' => __('In top menu')],
            ['value' => 'footer', 'label' => __('In footer')],
        ];
    }
}
