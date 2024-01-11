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
    'Magento_Catalog/js/price-box',
    'Magento_Catalog/product/view/validation'
    ], function ($) {
        'use strict';

        $.widget(
            'bss.customOption', {
                _create: function () {
                    var priceBoxesFastOrder = $('[data-role-fastorder=priceBox]');
                    priceBoxesFastOrder.priceBox(
                        {
                            'priceConfig': this.options.priceConfig,
                        }
                    );
                    priceBoxesFastOrder.trigger('reloadPrice');
                    $('#bss-fastorder-form-option').validation({});
                }
            }
        );

        return $.bss.customOption;
    }
);
