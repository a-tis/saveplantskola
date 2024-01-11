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
    'Bss_FastOrder/js/price-options'
    ], function ($) {
        'use strict';

        $.widget(
            'bss.productViewOption', {
                _create: function () {
                    $('#bss-fastorder-form-option').priceOptions(
                        {
                            optionConfig: this.options.optionConfig,
                            controlContainer: ".field",
                            priceHolderSelector: "[data-role-fastorder=priceBox]"
                        }
                    );
                }
            }
        );

        return $.bss.productViewOption;
    }
);
