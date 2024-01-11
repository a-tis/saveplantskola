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
        'jquery',
        'bss/fastorder_option'
    ], function ($) {
        'use strict';

        return function (config) {
            var template = $('#baseUrlLoading').attr('data-template');
            $(document).on("mousedown", "#bss-fastorder-form .bss-row-suggest", function (e) {
                var el = $(this);
                var widget = $(this).fastorder_option({});

                widget.fastorder_option('selectProduct', this);
                if (el.find('.bss-show-popup').val() == 1 &&
                    _.isEmpty(el.find('.bss-child-product-id').val()) &&
                    template == 'template-1' &&
                    window.prePopulated === false && window.showPopupDulicate === true
                ) {
                    var productId = $(this)
                        .closest('.bss-fastorder-row.bss-row')
                        .find('.bss-fastorder-row-ref .bss-product-id')
                        .val();
                    var sortOrder = $(this).closest('.bss-fastorder-row.bss-row').attr('data-sort-order');
                    var newDataPopup = {
                        sortOrder: sortOrder,
                        productId: productId
                    };

                    var find = window.dataPopups.find(x =>
                        (x.productId === productId &&
                            x.sortOrder === sortOrder
                        )
                    );
                    if (_.isEmpty(find)) {
                        window.dataPopups.push(newDataPopup);
                    }

                    widget.fastorder_option('showPopup', config.selectUrl, el);
                }
            });
        }
    });
