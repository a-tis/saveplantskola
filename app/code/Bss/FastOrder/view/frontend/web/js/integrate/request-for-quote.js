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
define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'jquery/ui',
    'bss/fastorder'
], function ($, customerData) {
    'use strict';

    $.widget('bss.integrate_RequestForQuote', {
        options: {
            formSelector: "#bss-fastorder-form",
            addToQuoteButtonSelector: ".btn-bss-add-to-quote",
            miniquoteSelector: '[data-block="miniquote"]',
            rowPrefixSelector: '#bss-fastorder-',
        },
        _create: function () {
            var self = this;
            // active module BSS Request for Quote
            $(document).on("click", self.options.addToQuoteButtonSelector, function (e) {
                e.preventDefault();
                self.handleAddToQuote();
            })
        },

        handleAddToQuote: function() {
            var form = $(this.options.formSelector);
            var self = this;
            var formAction = form.attr('action').replace("index/add", "integrate/addtoquote");
            var formData = new FormData(form[0]);
            $('#bss-fastorder-form tr').removeClass('bss-row-error');
            $('#bss-fastorder-form td').removeClass('bss-hide-border');
            var fastOrderWidget = $(self.options.rowPrefixSelector + '0').fastorder({});

            $.ajax({
                type: 'post',
                url: formAction,
                data: formData,
                dataType: 'json',
                showLoader: true,
                processData: false,
                contentType: false,
                success: function (data) {
                    fastOrderWidget.fastorder('scrollToMessage');
                    if (data.status == true) {
                        $('.bss-fastorder-row-delete button').click();
                        var sections = ['quote'];
                        customerData.invalidate(sections);
                        customerData.reload(sections, false);
                    } else if (data.status == false && data.row >= 0) {
                        $('#bss-fastorder-form tbody #bss-fastorder-' + data.row).addClass('bss-row-error');
                        if ($('#bss-fastorder-form tbody #bss-fastorder-' + data.row).next().length > 0) {
                            $('#bss-fastorder-form tbody #bss-fastorder-' + data.row).next().find('td').addClass('bss-hide-border');
                        } else {
                            $('#bss-fastorder-form tfoot tr td').addClass('bss-hide-border');
                        }
                    }
                },
                error: function () {
                    console.warn('Can not add to quote');
                }
            });
        }
    });

    return $.bss.integrate_RequestForQuote;
});
