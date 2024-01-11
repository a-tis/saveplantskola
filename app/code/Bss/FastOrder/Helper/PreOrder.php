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

namespace Bss\FastOrder\Helper;

class PreOrder extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @return bool
     */
    public function isEnable()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isPreOrder()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isInStock()
    {
        return false;
    }

    /**
     * @return array
     */
    public function getAllData()
    {
        return [];
    }
}
