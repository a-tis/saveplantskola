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
            'bss.fastorder_downloadable', {
                options: {
                    priceHolderSelector: '#bss-content-option-product .price-box',
                    sortOrder: '',
                    defaultPrice: ''
                },

                _create: function () {
                    var self = this;
                    this._restoreData(this.element.find(self.options.bssallElements), this.element.find(this.options.bsslinkElement + ':not(:checked)').length);

                    this.element.find(this.options.bsslinkElement).on(
                        'change', $.proxy(
                            function () {
                                var selectAll = this.element.find(this.options.bssallElements);
                                if (this.element.find(this.options.bsslinkElement + ':not(:checked)').length > 0 && selectAll.is(":checked")) {
                                    selectAll.prop('checked', false);
                                    $('[for="bss-fastorder-bss_fastorder_links_all"] span').text(selectAll.attr('data-notchecked'));
                                }
                                if (this.element.find(this.options.bsslinkElement + ':not(:checked)').length == 0 && !selectAll.is(":checked")) {
                                    selectAll.prop('checked', true);
                                    $('[for="bss-fastorder-bss_fastorder_links_all"] span').text(selectAll.attr('data-checked'));

                                }
                                this._reloadPrice();
                                $('#bss-links-advice-container').hide();
                            }, this
                        )
                    );

                    this.element.find(this.options.bssallElements).on(
                        'change', $.proxy(
                            function () {
                                var selectAll = this.element.find(this.options.bssallElements);
                                if (selectAll.is(":checked")) {
                                    $('#bss-links-advice-container').hide();
                                    $('[for="bss-fastorder-bss_fastorder_links_all"] span').text($(selectAll).attr('data-checked'));
                                    self.element.find(self.options.bsslinkElement + ':not(:checked)').each(
                                        function () {
                                            $(this).prop('checked', true);
                                        }
                                    );
                                } else {
                                    $('[for="bss-fastorder-bss_fastorder_links_all"] span').text($(selectAll).attr('data-notchecked'));
                                    self.element.find(self.options.bsslinkElement + ':checked').each(
                                        function () {
                                            $(this).prop('checked', false);
                                        }
                                    );
                                }
                                self._reloadPrice();
                            }, this
                        )
                    );
                },

                _restoreData: function (selectAll, optionLength) {
                    if (optionLength > 0 && selectAll.is(":checked")) {
                        selectAll.prop('checked', false);
                        $('[for="bss-fastorder-bss_fastorder_links_all"] span').text(selectAll.attr('data-notchecked'));
                    }
                    if (optionLength == 0 && !selectAll.is(":checked")) {
                        selectAll.prop('checked', true);
                        $('[for="bss-fastorder-bss_fastorder_links_all"] span').text(selectAll.attr('data-checked'));

                    }
                    this._reloadPrice();
                },

                /**
                 * Reload product price with selected link price included
                 *
                 * @private
                 */
                _reloadPrice: function () {
                    var finalPrice = 0;
                    var basePrice = 0;
                    var refreshPrice = 0;
                    $('#bss-fastorder-form-option .bss-attribute-select').val('');
                    this.element.find(this.options.bsslinkElement + ':checked').each(
                        $.proxy(
                            function (index, element) {
                                finalPrice += this.options.bssconfig.links[$(element).val()].finalPrice;
                                basePrice += this.options.bssconfig.links[$(element).val()].basePrice;
                                $(element).next().val($(element).val());
                            }, this
                        )
                    );
                    var sortOrder = this.element.closest('#bss-fastorder-form-option').find('#bss-select-option').attr('row');
                    $('#bss-fastorder-' + sortOrder + ' .bss-product-price-number').val(parseFloat(this.options.defaultPrice));
                    $('#bss-fastorder-' + sortOrder + ' .bss-product-price-number-download').val(finalPrice);
                    $(this.options.priceHolderSelector).trigger(
                        'updatePrice', {
                            'prices': {
                                'finalPrice': {'amount': finalPrice},
                                'basePrice': {'amount': basePrice}
                            }
                        }
                    );
                }
            }
        );

        return $.bss.fastorder_downloadable;
    }
);