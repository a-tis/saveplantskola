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
    ], function ($) {
        "use strict";
    
        $.widget(
            'bss.fastorder_grouped', {
                options: {
                    priceHolderSelector: '#bss-content-option-product .price-box',
                    bssqtyElement: '',
                    sortOrder: ''
                },

                _create: function () {
                    this.element.find(this.options.bssqtyElement).on(
                        'change',function () {
                            $(this).attr('value', $(this).val());
                            $('#bss-validation-message-box').hide();
                            var qtyEl = parseFloat($(this).val());
                            var priceEl = parseFloat($(this).closest('tr').find('.price-wrapper').attr('data-price-amount')),
                            priceElExclTax = parseFloat($(this).closest('tr').find('.price-wrapper.price-excluding-tax').attr('data-price-amount'));
                            $(this).next().val(qtyEl*priceEl);
                            $(this).next().attr('data-excl-tax', qtyEl*priceElExclTax);
                        }
                    );
                },
            }
        );
    
        return $.bss.fastorder_grouped;
    }
);