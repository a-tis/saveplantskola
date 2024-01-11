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
define(
    [
    "jquery",
    "jquery/ui",
    "bss/fastorder_grouped"
    ], function ($) {
        'use strict';

        return function (config) {
            $(document).ready(
                function () {
                    $('.bss-product-info-price .price-box').remove();
                    $('#bss-fastorder-super-product-table').fastorder_grouped(
                        {
                            "bssqtyElement":"input.bss-attribute-select",
                            "sortOrder":config.sortOrder
                        }
                    );
                }
            );
        }
    }
);
