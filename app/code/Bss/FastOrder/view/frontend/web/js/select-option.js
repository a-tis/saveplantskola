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
    'mage/translate',
    'underscore',
    'mage/url',
    'jquery/ui'
], function ($, $t, _, urlBuilder) {
    'use strict';

    $.widget('bss.fastorder_option', {
        options: {
            resetButtonSelector: '.bss-fastorder-row-delete button',
            cancelButtonSelector: 'button.bss-cancel-option',
            selectButtonSelector: 'button.bss-select-option',
            formSubmitSelector: 'form#bss-fastorder-form-option',
            optionsSelector: '#bss-fastorder-form-option .product-custom-option',
            data : [],
        },
        _create: function () {
            this._bind();
            var self = this;
        },
        _bind: function () {
            var self = this;
            this.createElements();
        },
        createElements: function () {
            if (!($('#bss-content-option-product').length)) {
                $(document.body).append('<div class="bss-content-option-product" id="bss-content-option-product"></div>');
                $('#bss-content-option-product').hide();
            }
            this.options.optionsPopup = $('#bss-content-option-product');
        },
        showPopup: function (selectUrl, el) {
            window.showPopupDulicate = true;
            if (localStorage.getItem('popupShowed') == 1) {
                return false;
            }
            if (el.find('.bss-show-popup').val() != 1) {
                return false;
            }
            if (JSON.parse(localStorage.getItem('isAddingNewGrouped')) === true) {
                return false;
            }
            $('body').loader('show');
            localStorage.setItem('sortOrderNew',0);
            var self = this,
                productId = $(el).find('.bss-product-id').val(),
                sortOrder = $(el).closest('.bss-fastorder-row').attr('data-sort-order'),
                editProductCache = window.editProductCache,
                productType;
            localStorage.setItem('sortOrderNew', sortOrder);
            var isEdit = editProductCache[sortOrder] !== undefined ? true : false;

            if (!_.isEmpty(window.productData[productId]) && !isEdit) {
                var productData = window.productData[productId];
                productType = productData['type_id'];
                if (!_.isEmpty(window.additionalAjaxRequests)) {
                    Promise.all(window.additionalAjaxRequests).then(() => {
                        setTimeout(function(){
                            // all requests finished successfully
                            self.hideLoader();
                            self._handleShowPopup(el, productData['additional_data']['popup_html'], productType, productId, sortOrder, isEdit);
                            window.additionalAjaxRequests = [];
                        }, 2000);
                    }).catch(() => {
                        self.hideLoader();
                        // all requests finished but one or more failed
                    });
                } else {
                    self._handleShowPopup(el, productData['additional_data']['popup_html'], productType, productId, sortOrder, isEdit);
                }
            } else {
                // hot-fix for loader, because popup is overlapping the loader
                $('#bss-content-option-product').css('z-index', 99);
                $.ajax({
                    url: selectUrl,
                    data: { productId: productId, sortOrder: sortOrder, isEdit: isEdit },
                    type: 'get',
                    dataType: 'json',
                    showLoader: true,
                    cache: false,
                    success: function (res) {
                        $('#bss-content-option-product').css('z-index', 9999);
                        if (res.popup_option) {
                            productType = res.type;
                            self._handleShowPopup(el, res.popup_option, productType, productId, sortOrder, isEdit);
                        }
                    },
                    error: function (response) {
                        $('#bss-content-option-product').css('z-index', 9999);
                        console.trace('Can not load option: ' + response);
                    }
                });
            }
        },
        _handleShowPopup: function(el, popupContent, productType, productId, sortOrder, isEdit) {
            var self = this;
            self.options.optionsPopup.html(popupContent).trigger('contentUpdated');
            self.options.optionsPopup.find('#bss-cancel-option').attr('row', sortOrder);
            self.options.optionsPopup.find('#bss-select-option').attr('row', sortOrder);
            self.options.optionsPopup.find('#bss-select-option').attr('isEdit', isEdit);
            if (productType == 'downloadable') {
                self.options.optionsPopup.find("input[name*='bss_fastorder_links']").attr("name", "bss_fastorder_links[" + sortOrder + "][]");
            }
            self.options.optionsPopup.fadeIn(500);
            $('body').loader('show');

            // update exactly row position of product
            $('.bss-row-select').val(sortOrder);

            // update sort order for custom option fields
            self.options.optionsPopup.find('[name*=bss-fastorder-options]').each(function () {
                var inputName = $(this).attr('name');
                inputName = inputName.split('bss-fastorder-options[');
                var inputNameSuffix = inputName[1].charAt(0) != ']' ? inputName[1] : sortOrder + inputName[1];
                inputName = 'bss-fastorder-options[' + inputNameSuffix;
                $(this).attr('name', inputName)
            });

            // update sort order for Configurable Product inputs
            self.options.optionsPopup.find('[name*=bss-fastorder-super_group]').each(function () {
                var inputName = $(this).attr('name');
                inputName = inputName.split('bss-fastorder-super_group[');
                var inputNameSuffix = inputName[1].charAt(0) != ']' ? inputName[1].substr(inputName[1].indexOf(']')) : inputName[1];
                inputName = 'bss-fastorder-super_group[' + sortOrder + inputNameSuffix;
                $(this).attr('name', inputName)
            });

            self.changePopupStyle(isEdit);

            // Edit button click
            self.restorePopupData(productType, sortOrder);

            if ($('#multiPopups').attr('ismulti') == 1) {
                self.pagePopupNumber('show');
                $('.next-previous-button').show();
            } else {
                $('.next-previous-button').hide();
            }

            if ($('#multiPopups').attr('isNextMax') == 1) {
                $('.next-previous.next').hide();
            } else {
                $('.next-previous.next').show();
            }

            if ($('#multiPopups').attr('isPreviousMax') == 1) {
                $('.next-previous.previous').hide();
            } else {
                $('.next-previous.previous').show();
            }

            $('body').trigger('popupIsShow');
        },
        selectProduct: function (el) {
            var productSku = $(el).find('.bss-product-sku-select').val();
            var childProductId = $(el).find('.bss-child-product-id').val();
            if (typeof childProductId !== "undefined" && childProductId != '') {
                var productId = childProductId;
            } else {
                var productId = $(el).find('.bss-product-id').val();
            }

            $(el).closest('.bss-fastorder-row.bss-row').find('.bss-fastorder-row-name .bss-product-custom-option-select ul').empty();
            var elProductName,
                productUrl = $(el).find('.bss-product-url').val(),
                productImage = $(el).find('.bss-product-image').html(),
                productName = $(el).find('.bss-product-name .product.name').text(),
                productPrice = $(el).find('.bss-product-price').html(),
                productHidePrice = $(el).find('.bss-product-hide-price').val(),
                productPriceAmount = $(el).find('.bss-product-price-amount').val(),
                productType = $(el).find('.bss-product-type').val(),
                productShowPopup = $(el).find('.bss-show-popup').val(),
                productPriceAmountExclTax = 0,
                rowEl = $(el).closest('tr.bss-fastorder-row'),
                liSelect = $(el).parent(),
                qty = $(el).find('.bss-product-qty').val();
            if ($(el).find('.bss-product-price-amount').attr('data-excl-tax')) {
                productPriceAmountExclTax = $(el).find('.bss-product-price-amount').attr('data-excl-tax');
            }
            $('#bss-fastorder-form tr').removeClass('bss-row-error');
            $('#bss-fastorder-form td').removeClass('bss-hide-border');
            $(rowEl).find('.bss-addtocart-info .bss-addtocart-option').empty();
            $(rowEl).find('.bss-fastorder-row-name .bss-product-option-select ul').empty();
            $(rowEl).find('.bss-fastorder-row-name .bss-product-baseprice ul').empty();
            $(rowEl).find('.bss-fastorder-row-edit button').hide();
            if (productShowPopup == "1") {
                $(rowEl).find('.bss-fastorder-row-edit button').show();
            }
            $(rowEl).find('.bss-fastorder-row-qty input.qty').removeAttr('readonly');
            $(rowEl).find('.bss-fastorder-row-delete button').show();
            $(rowEl).find('.bss-fastorder-img').html(productImage);
            if (qty && qty > 0) {
                $(rowEl).find('.bss-fastorder-row-qty input.qty').val(qty);
            }
            elProductName = productName;
            if (productUrl != '') {
                elProductName = '<a href="' + productUrl + '" alt="' + productName + '" class="product name" target="_blank">' + productName + '</a>';
            }
            productId = $(el).find('.bss-product-id').val();
            $(rowEl).find('.bss-fastorder-row-qty .bss-product-id-calc').val(productId);
            $(rowEl).find('.bss-fastorder-row-name .bss-product-name-select').html(elProductName);
            $(rowEl).find('.bss-fastorder-row-qty .bss-product-price-number').val(productPriceAmount).attr('data-excl-tax', productPriceAmountExclTax);
            $(rowEl).find('.bss-fastorder-row-qty .bss-product-price-custom-option').val(0).attr('data-excl-tax', 0);
            var sortOrder = $(rowEl).attr('data-sort-order');
            if (productType !== 'grouped') {
                $(rowEl).find('.bss-fastorder-row-name .bss-product-baseprice ul').append('<li>' + productPrice + '</li>');
            }
            $(rowEl).find('.bss-fastorder-row-ref .bss-search-input').val(productSku);
            $(el).closest('.bss-height-tr').find('.bss-fastorder-autocomplete').hide();
            $(el).closest('.bss-height-tr').find('.bss-fastorder-autocomplete li').not(liSelect).remove();
            $(el).closest('.bss-fastorder-row').find('.bss-addtocart-info .bss-product-id').val(productId);

            if (productHidePrice == '1') {
                return false;
            }

            if (!_.isEmpty(window.productData[productId]) && _.isEmpty(window.productData[productId]['additional_data'])) {
                // update rest of data of product here via ajax
                var newRequestData = JSON.stringify({
                    url: "fastorder/index/productAdditionalData",
                    sku: productSku,
                    has_popup: window.productData[productId]['popup']
                });
                // whether new request has called
                if (window.requestedUrl.includes(sortOrder) === false) {
                    var getAdditionalDataRequest = $.ajax({
                        url: urlBuilder.build("fastorder/index/productAdditionalData"),
                        data: {sku: productSku, has_popup: window.productData[productId]['popup']},
                        type: 'get',
                        dataType: 'json',
                        showLoader: false,
                        cache: false
                    });
                    window.requestedUrl[sortOrder] = newRequestData;

                    getAdditionalDataRequest
                        .done(function (data) {
                            if (data) {
                                // save response data for temporarily
                                if (_.isEmpty(data['popup_html']) &&
                                    !_.isEmpty(window.productData[productId]) &&
                                    !_.isEmpty(window.productData[productId]['additional_data']) &&
                                    !_.isEmpty(window.productData[productId]['additional_data']['popup_html'])
                                ) {
                                    data['popup_html'] = window.productData[productId]['additional_data']['popup_html'];
                                }
                                window.productData[productId]['additional_data'] = data;

                                var validators = data.data_validate;
                                var validatorsDecode = $.parseJSON(validators);
                                validatorsDecode = validatorsDecode['validate-item-quantity'];
                                $(rowEl).find('.bss-fastorder-row-qty .qty').attr('data-validate', validators);
                                if (typeof validatorsDecode.qtyIncrements !== 'undefined') {
                                    $(rowEl).find('.bss-fastorder-row-qty .bss-product-qty-increment').text('is available to buy in increments of ' + validatorsDecode['qtyIncrements']);
                                }
                                var decimal = data.is_qty_decimal;
                                var stockStatus = parseInt(data.pre_order);
                                if (stockStatus) {
                                    $(rowEl).find('.bss-fastorder-row-name .bss-product-stock-status').html($t('Pre-Order'));
                                }
                                $(rowEl).find('.bss-fastorder-row-qty .qty').attr('data-decimal', decimal);
                                $(rowEl).find('.bss-fastorder-row-name .bss-product-stock-status').empty();

                                _afterSelectProduct();
                            }
                        })
                        .fail(function (response) {
                            console.warn('Can not load product additional data');
                        });
                    if (window.additionalAjaxRequests.reduce((acc,cv)=>(cv)?acc+1:acc,0) == 0) {
                        window.additionalAjaxRequests.push(getAdditionalDataRequest);
                    }
                }
            } else {
                _afterSelectProduct();
            }

            function _afterSelectProduct() {
                $(rowEl).find('.bss-fastorder-row-qty .qty').change();
                $(rowEl).find('.bss-product-qty-up').click();
                $(rowEl).find('.bss-product-qty-down').click();
            }
        },

        closePopup: function (type = null) {
            var self = this;
            if ($('#multiPopups').attr('ismulti') == 1 && $('#multiPopups').attr('isnextmax') == 0 || $('#multiPopups').attr('isnextmax') == 'hasChange' && $('.next-previous.next').is(":visible")) {
                if ($('#multiPopups').attr('isnextmax') == 0) {
                    $('.next-previous.next').click();
                }

                if ($('#multiPopups').attr('ispreviousmax') == 'hasChange') {
                    $('#multiPopups').attr('ispreviousmax',1);
                    $('.next-previous.next').click();
                }

                if ($('#multiPopups').attr('isnextmax') == 'hasChange' ) {
                    $('#multiPopups').attr('isnextmax',1);
                    $('.next-previous.previous').click();
                }

                if ($('#multiPopups').attr('isnextmax') == 0 && $('#multiPopups').attr('ispreviousmax') == 0 && type == "isCancel") {
                    var oldData = (localStorage.getItem('nextDataPopup')).split(',');
                    $('#multiPopups').attr('currentSortOrder', oldData[0]);
                    if (type == "isCancel") {
                        $('.next-previous.next').click();
                    }
                }
            } else {
                this.options.optionsPopup.empty().fadeOut(500);
                $('td.bss-fastorder-row-image.bss-fastorder-img').change();
                localStorage.removeItem('nextDataPopup');
                localStorage.removeItem('previousDataPopup');
                $('#multiPopups').attr('ismulti',"");
                $('#multiPopups').attr('istotal',"");
                $('#multiPopups').attr('currentsortorder',"");
                $('#multiPopups').attr('isNextMax',"");
                $('#multiPopups').attr('isPreviousMax',"");

                if (_.isEmpty(window.childProductAjaxRequests)) {
                    self.hideLoader();
                } else {
                    // case: after add product in Configurable Grid View Table Popup
                    Promise.all(window.childProductAjaxRequests).then(() => {
                        // all requests finished successfully
                        window.childProductAjaxRequests = [];
                        self.hideLoader();
                    }).catch(() => {
                        // all requests finished but one or more failed
                        window.childProductAjaxRequests = [];
                        self.hideLoader();
                    });
                }
            }
        },
        hideLoader: function() {
            $('body').loader('hide');
            $('.loading-mask').hide();
        },
        _returnLengthNotEmpty: function (array) {
            return array.reduce((acc,cv)=>(cv)?acc+1:acc,0);

        },
        selectOption: function (sortOrder) {
            var lineItem = $('#bss-fastorder-' + sortOrder);
            var self = this,
                disabledSelect = false,
                selectedLinks = '',
                elAddtocart = lineItem.find('.bss-addtocart-option'),
                elAddtocartOption = lineItem.find('.bss-addtocart-custom-option'),
                priceInfo,
                linksInfo,
                i = 0,
                groupedPrice = 0,
                groupedPriceExclTax = 0,
                elProductinfo = lineItem.find('.bss-fastorder-row-name .bss-product-option-select ul'),
                elPricetinfo = lineItem.find('.bss-fastorder-row-name .bss-product-baseprice ul'),
                elCustomOption = lineItem.find('.bss-fastorder-row-name .bss-product-custom-option-select ul');

            lineItem.find('.bss-fastorder-row-qty .qty').removeAttr('readonly');
            elProductinfo.empty();
            elPricetinfo.empty();
            elAddtocart.empty();
            elCustomOption.empty();
            elAddtocartOption.empty();

            // reset custom options total price value
            lineItem.find('.bss-fastorder-row-qty .bss-product-price-custom-option').val(0).attr('data-excl-tax', 0);

            // move id child product configurable to form
            if ($('#bss-fastorder-form-option .bss-swatch-attribute').length > 0) {
                var priceNew = $('#bss-content-option-product .bss-product-info-price .price-wrapper').attr('data-price-amount'),
                    priceNewExclTax = 0,
                    childId = $('#bss-fastorder-form-option .bss-product-child-id').val();
                if ($('#bss-content-option-product .bss-product-info-price .base-price').length) {
                    priceNewExclTax = $('#bss-content-option-product .bss-product-info-price .price-wrapper').attr('data-excl-tax');
                }
                lineItem.find('.bss-fastorder-row-qty .bss-product-price-number').val(priceNew);
                lineItem.find('.bss-fastorder-row-qty .bss-product-price-number').attr('data-excl-tax', priceNewExclTax);
                lineItem.find('.bss-fastorder-row-qty .bss-product-id-calc').val(childId);
                lineItem.find('.bss-fastorder-row-name .bss-product-stock-status').empty();
                self._updatePreOrder(childId, sortOrder);
            }

            var typePopup = '.bss-attribute-select',
                productType = $('#bss-fastorder-'+sortOrder+' .bss-product-type').val();
            if (productType == 'downloadable') typePopup = '.bss-product-option';

            self.options.optionsPopup.find('#bss-fastorder-form-option ' + typePopup).each(function () {
                if ($('#bss-fastorder-form-option .bss-swatch-attribute').length > 0) {
                    // configurable product option
                    disabledSelect = self._selectConfigurable(this,disabledSelect,elAddtocart,elProductinfo);
                } else if ($('#bss-fastorder-form-option').find('.control').length > 0 && productType == 'downloadable') {
                    // downloadable product links
                    selectedLinks = self._selectDownloads($(this).find('#bss-fastorder-downloadable-links-list .field.choice'),elAddtocart,selectedLinks);
                } else if ($('#bss-fastorder-form-option .table-wrapper.grouped').length > 0) {
                    //grouped product child qty
                    var priceExcelTax = 0;
                    var priceGrouped = 0;
                    var checkPriceChange = 0;
                    var checkPriceExclTaxChange = 0;
                    if($(this).closest('tr').find('.price-wrapper.price-including-tax').attr('data-price-amount') != null)
                    {
                        $('#bss-fastorder-super-product-table tbody').each(function(){
                            var arrayPriceProduct = [];
                            var arrayPriceExclTax = [];

                            if($(this).children().hasClass('row-tier-price'))
                            {
                                checkPriceChange = priceGrouped;
                                checkPriceExclTaxChange = priceExcelTax;
                                var qty = $(this).find('.input-text.qty.bss-attribute-select').val();
                                var valueTotalGrouped =[];
                                var valueExcelTax =[];
                                $(this).find("ul li").each(function(){
                                    var findNumber = /\d+/;
                                    var textTierPrice = $(this).html();
                                    arrayPriceProduct[Number(textTierPrice.match(findNumber))] = Number($(this).find('.price-wrapper.price-including-tax').attr('data-price-amount'));
                                    arrayPriceExclTax[Number(textTierPrice.match(findNumber))] = Number($(this).find('.price-wrapper.price-excluding-tax').attr('data-price-amount'));

                                    arrayPriceProduct.forEach(function(element,key){
                                        if(Number(key) <= qty)
                                            valueTotalGrouped[key] = arrayPriceProduct[key];
                                    });
                                    arrayPriceExclTax.forEach(function(element,key){
                                        if(Number(key) <= qty)
                                            valueExcelTax[key] = arrayPriceExclTax[key];
                                    });
                                });
                                if(valueTotalGrouped.sort(function(a, b){return a-b})[0])
                                    priceGrouped += Number(qty)*Number(valueTotalGrouped.sort(function(a, b){return a-b})[0]);

                                if(valueExcelTax.sort(function(a, b){return a-b})[0])
                                    priceExcelTax += Number(qty)*Number(valueExcelTax.sort(function(a, b){return a-b})[0]);

                                if(priceGrouped == checkPriceChange && priceExcelTax == checkPriceExclTaxChange)
                                {
                                    var qty = $(this).find('.input-text.qty.bss-attribute-select').val();
                                    priceGrouped += Number(qty)*Number($(this).find('.price-wrapper.price-including-tax').attr('data-price-amount'));
                                    priceExcelTax += Number(qty)*Number($(this).find('.price-wrapper.price-excluding-tax').attr('data-price-amount'));
                                }
                            }
                            else
                            {
                                var qty = $(this).find('.input-text.qty.bss-attribute-select').val();
                                priceGrouped += Number(qty)*Number($(this).find('.price-wrapper.price-including-tax').attr('data-price-amount'));
                                priceExcelTax += Number(qty)*Number($(this).find('.price-wrapper.price-excluding-tax').attr('data-price-amount'));
                            }

                        });
                        if ($(this).val() != '' ) {
                            $(this).clone().appendTo(elAddtocart);
                        }
                        groupedPrice = parseFloat(priceGrouped);
                        groupedPriceExclTax =  parseFloat(priceExcelTax);
                    }
                    else if ($(this).closest('tbody').find('.row-tier-price') != null)
                    {
                        $('#bss-fastorder-super-product-table tbody').each(function(){
                            var arrayPriceProduct = [];

                            if($(this).children().hasClass('row-tier-price'))
                            {
                                checkPriceChange = priceGrouped;
                                checkPriceExclTaxChange = priceExcelTax;
                                var qty = $(this).find('.input-text.qty.bss-attribute-select').val();
                                var valueTotalGrouped =[];
                                $(this).find("ul li").each(function(){
                                    var findNumber = /\d+/;
                                    var textTierPrice = $(this).html();
                                    arrayPriceProduct[Number(textTierPrice.match(findNumber))] = Number($(this).find('.price-wrapper').attr('data-price-amount'));

                                    arrayPriceProduct.forEach(function(element,key){
                                        if(Number(key) <= qty)
                                            valueTotalGrouped[key] = arrayPriceProduct[key];
                                    });

                                });
                                if(valueTotalGrouped.sort(function(a, b){return a-b})[0])
                                    priceGrouped += Number(qty)*Number(valueTotalGrouped.sort(function(a, b){return a-b})[0]);
                                if(priceGrouped == checkPriceChange)
                                {
                                    var qty = $(this).find('.input-text.qty.bss-attribute-select').val();
                                    priceGrouped += Number(qty)*Number($(this).find('.price-wrapper').attr('data-price-amount'));
                                    priceExcelTax = priceGrouped;
                                }
                            }
                            else
                            {
                                var qty = $(this).find('.input-text.qty.bss-attribute-select').val();
                                priceGrouped += Number(qty)*Number($(this).find('.price-wrapper').attr('data-price-amount'));
                                priceExcelTax = priceGrouped;
                            }

                        });
                        if ($(this).val() != '' ) {
                            $(this).clone().appendTo(elAddtocart);
                        }
                        groupedPrice = parseFloat(priceGrouped);
                        groupedPriceExclTax =  0;
                    }
                    else
                    {
                        if ($(this).val() != '' ) {
                            $(this).clone().appendTo(elAddtocart);
                        }
                        if ($(this).next().val() != '') {
                            priceGrouped = $(this).next().val();
                            priceExcelTax = $(this).next().attr('data-excl-tax');
                        }
                        groupedPrice = parseFloat(groupedPrice)+parseFloat(priceGrouped);
                        groupedPriceExclTax =  parseFloat(groupedPrice)+parseFloat(priceExcelTax);

                    }
                }
            });

            if ($('#bss-fastorder-form-option .field.downloads').length > 0) {
                if (selectedLinks == '') {
                    disabledSelect = true;
                    $('#bss-links-advice-container').show();
                } else {
                    var linksLabel = $('#bss-fastorder-form-option .bss-required-label').html();
                    linksInfo = '<li><span class="label">' + linksLabel + '</span></li>' + selectedLinks;
                    $(elProductinfo).append(linksInfo);
                }
            } else if ($('#bss-fastorder-form-option .table-wrapper.grouped').length > 0) {
                lineItem.find('.bss-fastorder-row-qty .bss-product-price-number').val(groupedPrice);
                lineItem.find('.bss-fastorder-row-qty .bss-product-price-number').attr('data-excl-tax', groupedPriceExclTax);
                lineItem.find('.bss-fastorder-row-qty .bss-product-price-group').val(self._getPriceGroupThisPopup());
                if (groupedPrice <= 0) {
                    disabledSelect = true;
                    $('.bss-validation-message-box').show();
                }
            }

            $(self.options.optionsSelector).each(function () {
                self._onOptionChanged(this, sortOrder, elAddtocartOption);
            });
            if (disabledSelect == false) {
                priceInfo = $('#bss-content-option-product .bss-product-info-price .price-container').html();
                $(elProductinfo).find('li .price').parent().remove();
                if (priceInfo) {
                    $(elPricetinfo).append('<li>'+priceInfo+'</li>');
                }
                lineItem.find('.bss-fastorder-row-edit button').show();
                lineItem.find('.bss-fastorder-row-qty .qty').change();
                self.closePopup();
            }

        },
        _selectConfigurable: function (el, disabledSelect,elAddtocart,elProductinfo) {
            var selectInfo;
            if ($(el).val() == '') {
                disabledSelect = true;
                if ($(el).parent().find('.bss-mage-error').length == 0) {
                    $(el).parent().append('<div generated="true" class="bss-mage-error">This is a required field.</div>');
                }
            } else {
                var selectLabel = $(el).parent().find('.bss-swatch-attribute-label').text();
                var selectValue = $(el).parent().find('.bss-swatch-attribute-selected-option').text();
                if (selectValue == '') {
                    selectValue = $(el).parent().find('.bss-swatch-select option:selected').text();
                }
                selectInfo = '<li><span class="label">' + selectLabel + '</span>&nbsp;:&nbsp;' + selectValue + '</li>';
                $(el).parent().find('.bss-mage-error').remove();
                $(el).clone().appendTo(elAddtocart);
                $(elProductinfo).append(selectInfo);
            }
            return disabledSelect;
        },
        _selectDownloads: function (el,elAddtocart,selectedLinks) {
            if ($(el).find('span').html() != '') {
                var selectedLinks = '',
                    selectedLinksOnlyOneOption = '',
                    onlyOneOption = true;
                $(el).each(function(index){
                    var inputChecked = $(this).find('input:checked');
                    if (inputChecked.length > 0 && inputChecked.attr('id') != 'bss-fastorder-bss_fastorder_links_all') {
                        onlyOneOption = false;
                        selectedLinks += '<li>' + $(this).find('label.label span').html() + '</li>';
                    } else {
                        selectedLinksOnlyOneOption += '<li>' + $(this).find('label.label span').html() + '</li>';
                    }
                });
                $(el).clone().appendTo(elAddtocart);
                elAddtocart.find('.downloads-all').remove();
                if (onlyOneOption === true && el.find("input[name*='bss_fastorder_links']").length == 0) {
                    selectedLinks = selectedLinksOnlyOneOption;
                }
            }
            return selectedLinks;
        },
        _onOptionChanged: function (el, sortOrder, elAddtocartOption) {
            var element = $(el),
                label = '',
                option = '',
                id = '',
                idSelect = '',
                price = 0,
                priceExclTax = 0,
                optionType = element.prop('type'),
                elPrice = $('#bss-fastorder-'+sortOrder+'').find('.bss-fastorder-row-qty .bss-product-price-number'),
                elPriceOption = $('#bss-fastorder-'+sortOrder+'').find('.bss-fastorder-row-qty .bss-product-price-custom-option'),
                elOptionInfo = $('#bss-fastorder-'+sortOrder+'').find('.bss-fastorder-row-name .bss-product-custom-option-select ul');
            switch (optionType) {
                case 'text':
                    if (element.val() != '') {
                        label = element.closest('.bss-options-info').find('.label:first').html();
                        if (element.closest('.field').find('.price-container .price-excluding-tax').length == 0) {
                            price = element.closest('.field').find('.price-container .price-wrapper').attr('data-price-amount');
                        } else {
                            price = element.closest('.field').find('.price-container .price-including-tax').attr('data-price-amount');
                            priceExclTax = element.closest('.field').find('.price-container .price-excluding-tax').attr('data-price-amount');
                        }
                        if (price > 0) {
                            elPrice.val(parseFloat(price) + parseFloat(elPrice.val()));
                            elPrice.attr('data-excl-tax', parseFloat(priceExclTax) + parseFloat(elPrice.attr('data-excl-tax')));
                            elPriceOption.val(parseFloat(price) + parseFloat(elPriceOption.val()));
                            elPriceOption.attr('data-excl-tax', parseFloat(priceExclTax) + parseFloat(elPriceOption.attr('data-excl-tax')));
                        }
                        element.closest('.control').find('.bss-customoption-select').val(element.val());
                        element.closest('.control').find('.bss-customoption-select').clone().appendTo(elAddtocartOption);
                        option = element.val();
                        elOptionInfo.append('<li><span class="label">'+label+'</span></li><li>'+option+'</li>');
                    }
                    break;
                case 'textarea':
                    if (element.val() != '') {
                        label = element.closest('.bss-options-info').find('.label:first').html();
                        if (element.closest('.textarea').find('.price-container .price-excluding-tax').length == 0) {
                            price = element.closest('.textarea').find('.price-container .price-wrapper').attr('data-price-amount');
                        } else {
                            price = element.closest('.textarea').find('.price-container .price-including-tax').attr('data-price-amount');
                            priceExclTax = element.closest('.textarea').find('.price-container .price-excluding-tax').attr('data-price-amount');
                        }
                        if (price > 0) {
                            elPrice.val(parseFloat(price) + parseFloat(elPrice.val()));
                            elPrice.attr('data-excl-tax', parseFloat(priceExclTax) + parseFloat(elPrice.attr('data-excl-tax')));
                            elPriceOption.val(parseFloat(price) + parseFloat(elPriceOption.val()));
                            elPriceOption.attr('data-excl-tax', parseFloat(priceExclTax) + parseFloat(elPriceOption.attr('data-excl-tax')));
                        }
                        element.closest('.control').find('.bss-customoption-select').val(element.val());
                        element.closest('.control').find('.bss-customoption-select').appendTo(elAddtocartOption);
                        option = element.val();
                        elOptionInfo.append('<li><span class="label">'+label+'</span></li><li>'+option+'</li>');
                    }
                    break;

                case 'radio':
                    if (element.is(':checked')) {
                        if (element.closest('li').find('.price-container .price-including-tax').length == 0) {
                            price = element.attr('price');
                        } else {
                            price = element.closest('li').find('.price-container .price-including-tax').attr('data-price-amount');
                            priceExclTax = element.closest('li').find('.price-container .price-excluding-tax').attr('data-price-amount');
                        }
                        if (price > 0) {
                            elPrice.val(parseFloat(price) + parseFloat(elPrice.val()));
                            elPrice.attr('data-excl-tax', parseFloat(priceExclTax) + parseFloat(elPrice.attr('data-excl-tax')));
                            elPriceOption.val(parseFloat(price) + parseFloat(elPriceOption.val()));
                            elPriceOption.attr('data-excl-tax', parseFloat(priceExclTax) + parseFloat(elPriceOption.attr('data-excl-tax')));
                        }
                        element.next().clone().appendTo(elAddtocartOption);
                        label = element.closest('.bss-options-info').find('.label:first').html();
                        option = element.closest('.field').find('.label:first').html();
                        if (element.val()) {
                            elOptionInfo.append('<li><span class="label">'+label+'</span></li><li>'+option+'</li>');
                        }
                    }
                    break;
                case 'select-one':
                    if (element.closest('.bss-options-info').find('.label:first').html() == undefined) {
                        if (element.attr('name').indexOf('month') != -1 ) {
                            element.closest('.control').find('.bss-customoption-select-month').val(element.val());
                            element.closest('.control').find('.bss-customoption-select-month').clone().appendTo(elAddtocartOption);
                        } else if (element.attr('name').indexOf('day_part') != -1 ) {
                            element.closest('.control').find('.bss-customoption-select-day_part').val(element.val());
                            element.closest('.control').find('.bss-customoption-select-day_part').clone().appendTo(elAddtocartOption);
                            if (element.closest('.control').find('.bss-customoption-select-day_part').hasClass('bss-customoption-select-last')) {
                                var month = element.closest('.control').find('.bss-customoption-select-month').val();
                                var day = element.closest('.control').find('.bss-customoption-select-day').val();
                                var year = element.closest('.control').find('.bss-customoption-select-year').val();
                                var hour = element.closest('.control').find('.bss-customoption-select-hour').val();
                                var minute = element.closest('.control').find('.bss-customoption-select-minute').val();

                                if (!_.isEmpty(month) && !_.isEmpty(day) && !_.isEmpty(year) && !_.isEmpty(hour) && !_.isEmpty(minute)) {
                                    if (element.closest('.field').find('.price-container .price-excluding-tax').length == 0) {
                                        price = element.closest('.field').find('.price-container .price-wrapper').attr('data-price-amount');
                                    } else {
                                        price = element.closest('.field').find('.price-container .price-including-tax').attr('data-price-amount');
                                        priceExclTax = element.closest('.field').find('.price-container .price-excluding-tax').attr('data-price-amount');
                                    }
                                    if (price > 0) {
                                        elPrice.val(parseFloat(price) + parseFloat(elPrice.val()));
                                        elPrice.attr('data-excl-tax', parseFloat(priceExclTax) + parseFloat(elPrice.attr('data-excl-tax')));
                                        elPriceOption.val(parseFloat(price) + parseFloat(elPriceOption.val()));
                                        elPriceOption.attr('data-excl-tax', parseFloat(priceExclTax) + parseFloat(elPriceOption.attr('data-excl-tax')));
                                    }

                                    label = element.closest('.bss-options-info').find('legend.legend').html();

                                    var day_part = element.find(":selected").text();
                                    month = this._pad(month,2);
                                    day = this._pad(day,2);
                                    hour = this._pad(hour,2);
                                    minute = this._pad(minute,2);
                                    if (element.val()) {
                                        elOptionInfo.append('<li><span class="label">'+label+'</span></li><li>'+month+'/' + day +'/'+year+' '+hour+':' + minute +' '+day_part+'</li>');
                                    }
                                }
                            }
                        } else if (element.attr('name').indexOf('day') != -1 ) {
                            element.closest('.control').find('.bss-customoption-select-day').val(element.val());
                            element.closest('.control').find('.bss-customoption-select-day').clone().appendTo(elAddtocartOption);
                        } else if (element.attr('name').indexOf('year') != -1 ) {
                            element.closest('.control').find('.bss-customoption-select-year').val(element.val());
                            element.closest('.control').find('.bss-customoption-select-year').clone().appendTo(elAddtocartOption);
                            var month = element.closest('.control').find('.bss-customoption-select-month').val();
                            var day = element.closest('.control').find('.bss-customoption-select-day').val();
                            var year = element.closest('.control').find('.bss-customoption-select-year').val();
                            if (!_.isEmpty(month) && !_.isEmpty(day) && !_.isEmpty(year)) {
                                if (element.closest('.control').find('.bss-customoption-select-year').hasClass('bss-customoption-select-last')) {
                                    if (element.closest('.field').find('.price-container .price-excluding-tax').length == 0) {
                                        price = element.closest('.field').find('.price-container .price-wrapper').attr('data-price-amount');
                                    } else {
                                        price = element.closest('.field').find('.price-container .price-including-tax').attr('data-price-amount');
                                        priceExclTax = element.closest('.field').find('.price-container .price-excluding-tax').attr('data-price-amount');
                                    }
                                    if (price > 0) {
                                        elPrice.val(parseFloat(price) + parseFloat(elPrice.val()));
                                        elPrice.attr('data-excl-tax', parseFloat(priceExclTax) + parseFloat(elPrice.attr('data-excl-tax')));
                                        elPriceOption.val(parseFloat(price) + parseFloat(elPriceOption.val()));
                                        elPriceOption.attr('data-excl-tax', parseFloat(priceExclTax) + parseFloat(elPriceOption.attr('data-excl-tax')));
                                    }

                                    label = element.closest('.bss-options-info').find('legend.legend').html();

                                    var month = this._pad(element.closest('.control').find('.bss-customoption-select-month').val(),2);
                                    var day = this._pad(element.closest('.control').find('.bss-customoption-select-day').val(),2);
                                    var year = element.find(":selected").text();
                                    if (element.val()) {
                                        elOptionInfo.append('<li><span class="label">'+label+'</span></li><li>'+month+'/' + day +'/'+year+'</li>');
                                    }
                                }
                            }
                        } else if (element.attr('name').indexOf('hour') != -1 ) {
                            element.closest('.control').find('.bss-customoption-select-hour').val(element.val());
                            element.closest('.control').find('.bss-customoption-select-hour').clone().appendTo(elAddtocartOption);
                        } else if (element.attr('name').indexOf('minute') != -1 ) {
                            element.closest('.control').find('.bss-customoption-select-minute').val(element.val());
                            element.closest('.control').find('.bss-customoption-select-minute').clone().appendTo(elAddtocartOption);
                            var hour = element.closest('.control').find('.bss-customoption-select-hour').val();
                            var minute = element.closest('.control').find('.bss-customoption-select-minute').val();
                            var isTimeOption = element.closest('.control').find('.bss-customoption-select-month').val();
                            if (!_.isEmpty(hour) && !_.isEmpty(minute) && isTimeOption === undefined) {
                                if (element.closest('.control').find('.bss-customoption-select-day_part').hasClass('bss-customoption-select-last')) {
                                    if (element.closest('.field').find('.price-container .price-excluding-tax').length == 0) {
                                        price = element.closest('.field').find('.price-container .price-wrapper').attr('data-price-amount');
                                    } else {
                                        price = element.closest('.field').find('.price-container .price-including-tax').attr('data-price-amount');
                                        priceExclTax = element.closest('.field').find('.price-container .price-excluding-tax').attr('data-price-amount');
                                    }
                                    if (price > 0) {
                                        elPrice.val(parseFloat(price) + parseFloat(elPrice.val()));
                                        elPrice.attr('data-excl-tax', parseFloat(priceExclTax) + parseFloat(elPrice.attr('data-excl-tax')));
                                        elPriceOption.val(parseFloat(price) + parseFloat(elPriceOption.val()));
                                        elPriceOption.attr('data-excl-tax', parseFloat(priceExclTax) + parseFloat(elPriceOption.attr('data-excl-tax')));
                                    }
                                    label = element.closest('.bss-options-info').find('legend.legend').html();
                                    var hour = this._pad(element.closest('.control').find('.bss-customoption-select-hour').val(),2);
                                    var minute = element.find(":selected").text();
                                    if (element.val()) {
                                        elOptionInfo.append('<li><span class="label">'+label+'</span></li><li>'+hour+':' + minute +'</li>');
                                    }
                                }
                            }
                        }
                    } else {
                        if (element.attr('data-incl-tax')) {
                            price = element.attr('data-incl-tax');
                            priceExclTax = element.find(":selected").attr('price');
                        } else {
                            price = element.find(":selected").attr('price');
                        }
                        label = element.closest('.bss-options-info').find('.label:first').html();
                        option = element.find(":selected").text();
                        if (price > 0) {
                            elPrice.val(parseFloat(price) + parseFloat(elPrice.val()));
                            elPrice.attr('data-excl-tax', parseFloat(priceExclTax) + parseFloat(elPrice.attr('data-excl-tax')));
                            elPriceOption.val(parseFloat(price) + parseFloat(elPriceOption.val()));
                            elPriceOption.attr('data-excl-tax', parseFloat(priceExclTax) + parseFloat(elPriceOption.attr('data-excl-tax')));
                        }
                        element.closest('.control').find('.bss-customoption-select').val(element.val());
                        element.closest('.control').find('.bss-customoption-select').clone().appendTo(elAddtocartOption);
                        if (element.val()) {
                            elOptionInfo.append('<li><span class="label">'+label+'</span></li><li>'+option+'</li>');
                        }
                    }
                    break;

                case 'select-multiple':
                    label = element.closest('.bss-options-info').find('.label:first').html();
                    element.find(":selected").each(function (i, selected) {
                        if ($(selected).attr('data-incl-tax')) {
                            price += parseFloat($(selected).attr('data-incl-tax'));
                            priceExclTax += parseFloat($(selected).attr('price'));
                        } else {
                            price += parseFloat($(selected).attr('price'));
                        }

                        id += $(selected).val() + ',';
                        option += '<li>'+$(selected).text()+'</li>';
                    });
                    if (price > 0) {
                        elPrice.val(parseFloat(price) + parseFloat(elPrice.val()));
                        elPrice.attr('data-excl-tax', parseFloat(priceExclTax) + parseFloat(elPrice.attr('data-excl-tax')));
                        elPriceOption.val(parseFloat(price) + parseFloat(elPriceOption.val()));
                        elPriceOption.attr('data-excl-tax', parseFloat(priceExclTax) + parseFloat(elPriceOption.attr('data-excl-tax')));
                    }
                    element.closest('.control').find('.bss-customoption-select').val(id);
                    element.closest('.control').find('.bss-customoption-select').clone().appendTo(elAddtocartOption);
                    if (element.val()) {
                        elOptionInfo.append('<li><span class="label">' + label + '</span></li><li>' + option + '</li>');
                    }
                    break;

                case 'checkbox':
                    if (element.is(':checked')) {
                        idSelect = element.closest('.bss-options-info').find('.label:first').attr('for');
                        if (elOptionInfo.find('.'+idSelect).length == 0) {
                            label = element.closest('.bss-options-info').find('.label:first').html();
                        }
                        if ($(element).attr('data-incl-tax')) {
                            price = parseFloat($(element).attr('data-incl-tax'));
                            priceExclTax = parseFloat($(element).attr('price'));
                        } else {
                            price = parseFloat($(element).attr('price'));
                        }
                        element.next().clone().appendTo(elAddtocartOption);
                        option = '<li>'+element.closest('.field').find('.label:first').html()+'</li>';
                    }
                    if (price > 0) {
                        elPrice.val(parseFloat(price) + parseFloat(elPrice.val()));
                        elPrice.attr('data-excl-tax', parseFloat(priceExclTax) + parseFloat(elPrice.attr('data-excl-tax')));
                        elPriceOption.val(parseFloat(price) + parseFloat(elPriceOption.val()));
                        elPriceOption.attr('data-excl-tax', parseFloat(priceExclTax) + parseFloat(elPriceOption.attr('data-excl-tax')));
                    }
                    elOptionInfo.append('<li><span class="label '+idSelect+'">'+label+'</span></li><li>'+option+'</li>');
                    break;

                case 'file':
                    if (element.val() != '') {
                        label = element.closest('.bss-options-info').find('.label:first').html();
                        if (element.closest('.field').find('.price-container .price-excluding-tax').length == 0) {
                            price = element.closest('.field').find('.price-container .price-wrapper').attr('data-price-amount');
                        } else {
                            price = element.closest('.field').find('.price-container .price-including-tax').attr('data-price-amount');
                            priceExclTax = element.closest('.field').find('.price-container .price-excluding-tax').attr('data-price-amount');
                        }
                        if (price > 0) {
                            elPrice.val(parseFloat(price) + parseFloat(elPrice.val()));
                            elPrice.attr('data-excl-tax', parseFloat(priceExclTax) + parseFloat(elPrice.attr('data-excl-tax')));
                            elPriceOption.val(parseFloat(price) + parseFloat(elPriceOption.val()));
                            elPriceOption.attr('data-excl-tax', parseFloat(priceExclTax) + parseFloat(elPriceOption.attr('data-excl-tax')));
                        }
                        element.closest('.control').find('.bss-customoption-select').val(element.val());
                        element.closest('.control').find('.bss-customoption-select').clone().appendTo(elAddtocartOption);
                        element.closest('.control').find('.bss-customoption-file').clone().appendTo(elAddtocartOption);
                        option = element.val();
                        elOptionInfo.append('<li><span class="label">'+label+'</span></li><li>'+option+'</li>');
                    }
                    break;
            }
        },
        _updatePreOrder: function (childId, sortOrder) {
            if ($('.bss-fastorder-swatch').length) {
                var dataPreOrder = $('.bss-fastorder-swatch').data('preorder');
                var obj = _.find(dataPreOrder, function (obj) {
                    return obj.productId == childId;
                });
                if (obj && obj.preorder) {
                    $('#bss-fastorder-'+sortOrder+'').find('.bss-fastorder-row-name .bss-product-stock-status').html($t('Pre-Order'));
                }
            }
        },
        _getPriceGroupThisPopup: function () {
            var listProductGroup = $('#bss-fastorder-super-product-table').find('tbody');
            var totalPrice = 0;
            var totalPriceTax = 0;
            listProductGroup.each( function () {
                totalPrice += parseFloat($(this).find('td .price-wrapper.price-including-tax').attr('data-price-amount'));
            });
            return totalPrice.toFixed(2);
        },
        _pad: function (number, length) {
            var str = '' + number;
            while (str.length < length) {
                str = '0' + str;
            }
            return str;
        },
        pagePopupNumber: function (type = null) {
            if (type == "show") {
                $('#pagePopup').show();
                var currentIndex = $('#multiPopups').attr('currentSortOrder');
                var dataPopups = localStorage.getItem('nextDataPopup');
                dataPopups = dataPopups.split(',');
                currentIndex = dataPopups.indexOf(currentIndex);
                currentIndex++;
                $('#currentNumber').text(currentIndex);
                $('#totalNumber').text($('#multiPopups').attr('istotal'));
            }
        },
        changePopupStyle: function (isEdit) {
            if ($('#bss_configurablegridview').length && isEdit === false) {
                this.options.optionsPopup.addClass('bss-configurable-grid-view-popup');
            } else {
                this.options.optionsPopup.removeClass('bss-configurable-grid-view-popup');
            }
            if ($('.grouped').length && isEdit === false) {
                this.options.optionsPopup.addClass('bss-grouped-popup');
            } else {
                this.options.optionsPopup.removeClass('bss-grouped-popup');
            }

        },
        restorePopupData: function (productType, sortOrder) {
            var self = this;
            if (productType == "grouped") {
                if (self.options.data != null && localStorage.getItem(sortOrder) != null) {
                    var dataEdit = JSON.parse(localStorage.getItem(sortOrder));
                    dataEdit.forEach(function (element, key) {
                        var subValue = element.split("+");
                        $('[name = "' + String(subValue[0]) + '"]').val(subValue[1]);
                    });
                }
            }
            else if (productType == "configurable") {
                if (self.options.data != null && localStorage.getItem(sortOrder) != null) {
                    var dataEdit = JSON.parse(localStorage.getItem(sortOrder));
                    dataEdit.forEach(function (element, key) {
                        $('.bss-swatch-option').each(function () {
                            if ($(this).attr('bss-option-id') == element) {
                                $(this).addClass('selected');
                            }
                        });
                    });
                }
            }
            else // Custom option
            {
                if (self.options.data != null && localStorage.getItem(sortOrder) != null) {

                    var dataEdit = JSON.parse(localStorage.getItem(sortOrder));
                    dataEdit.forEach(function (element, key) {
                        var subValue = element.split("+");
                        if (($('[name = "' + String(subValue[0]) + '"]').attr('type') == "radio")) {
                            $('[name = "' + String(subValue[0]) + '"]').each(function () {
                                if ($(this).val() == subValue[1]) {
                                    $(this).prop("checked", true).trigger("change");
                                }
                            });
                        }
                        else if ($('[name = "' + String(subValue[0]) + '"]').attr('type') == "checkbox" || $('[name = "' + String(subValue[0]) + '"]').attr('multiple') == "multiple") {
                            $('[name = "' + String(subValue[0]) + '"]').each(function () {
                                var value = subValue[1].split(',');
                                var checkbox = this;
                                if ($('[name = "' + String(subValue[0]) + '"]').attr('multiple') == "multiple") {
                                    $(checkbox).val(value);
                                }
                                else {
                                    value.forEach(function (element, key) {
                                        if ($(checkbox).val() == Number(element)) {
                                            $(checkbox).prop("checked", true).trigger("change");
                                        }
                                    });
                                }
                            });
                        }
                        else {
                            $('[name = "' + String(subValue[0]) + '"]').val(subValue[1]).trigger("change");
                        }

                    });
                }
            }
        },
        cancelOnPopup: function (el, sortOrder) {
            var flagClearMultiPopup = false;
            var self = this;
            var indexDelete = parseInt($('#multiPopups').attr('currentsortorder'));
            var nextData = [],
                oldData;
            $(el).closest('tr').find('*').each(function () {
                if ($(this).attr('colspan') == 2) {
                    $(this).remove();
                }
            });
            if (editProductCache[sortOrder]) {
                var productRow = $('#bss-fastorder-' + sortOrder);
                var productSku = $(el).find('.bss-product-sku-select').val();
                productRow.html(editProductCache[sortOrder]);
                productRow.find('.bss-search-input').val(productSku);

            } else {
                $('tr#bss-fastorder-' + sortOrder).find(self.options.resetButtonSelector).click();
                self.options.data = [];
                localStorage.removeItem(sortOrder);
            }

            if ($('#multiPopups').attr('ismulti') == 1) {
                var currentTotal = $('#multiPopups').attr('istotal'),
                    firstItem = false,
                    lastItem = false;
                if (!_.isEmpty(window.dataPopups)) {
                    var i = 0,
                        nextItem = false,
                        backFirstItem = false;
                    window.dataPopups.forEach(function (el, index) {
                        i++;
                        if ($.isEmptyObject(el) || index == indexDelete) {
                            delete window.dataPopups[index];
                        } else {
                            if (backFirstItem === false) {
                                backFirstItem = index;
                            }
                        }
                        if (index == indexDelete && i == 1) {
                            firstItem = true;
                        }
                        if (index == indexDelete && i == currentTotal - 1) {
                            lastItem = true;
                        }
                        if (index > indexDelete && nextItem === false) {
                            nextItem = index;
                        }
                    })
                }
                if (nextItem === false) {
                    nextItem = backFirstItem;
                }
                if (nextItem === false) {
                    nextItem = "";
                }
                window.dataPopups.forEach(function (el, index) {
                    nextData.push(index);
                });
                localStorage.setItem('nextDataPopup', nextData);
                currentTotal = self._returnLengthNotEmpty(window.dataPopups);
                $('#multiPopups').attr('istotal', currentTotal);

                $('#multiPopups').attr('currentSortOrder', nextItem);
                if (firstItem == true && lastItem == true) {
                    $('#multiPopups').attr('isNextMax', 1);
                    $('#multiPopups').attr('isPreviousMax', 1)
                } else {
                    if (firstItem == true) {
                        $('#multiPopups').attr('isNextMax', 0);
                        $('#multiPopups').attr('isPreviousMax', 1)
                    }
                    if (lastItem == true) {
                        $('#multiPopups').attr('isNextMax', 1);
                        $('#multiPopups').attr('isPreviousMax', 0);
                    }
                }
                if (currentTotal > 0) {
                    self.showPopup(selectUrl, $('[data-sort-order="' + window.dataPopups[$('#multiPopups').attr('currentSortOrder')].sortOrder + '"]').find('.bss-row-suggest'));
                } else {
                    this.options.optionsPopup.empty().fadeOut(500);
                    self.hideLoader();
                    localStorage.removeItem('nextDataPopup');
                    localStorage.removeItem('previousDataPopup');
                    $('#multiPopups').attr('ismulti', "");
                    $('#multiPopups').attr('istotal', "");
                    $('#multiPopups').attr('currentsortorder', "");
                    $('#multiPopups').attr('isNextMax', "");
                    $('#multiPopups').attr('isPreviousMax', "");
                    window.dataPopups=[];
                }

            } else {
                self.closePopup('isCancel');
                if (flagClearMultiPopup == true) {
                    var currentIndex = parseInt($('#multiPopups').attr('currentsortorder'));
                    var selectUrl = $('#multiPopups').attr('selectUrl');
                    self.options.optionsPopup.empty().fadeOut(500);
                    $('td.bss-fastorder-row-image.bss-fastorder-img').change();
                    localStorage.removeItem('nextDataPopup');
                    localStorage.removeItem('previousDataPopup');
                    $('#multiPopups').attr('ismulti', "");
                    $('#multiPopups').attr('istotal', "");
                    $('#multiPopups').attr('currentsortorder', "");
                    $('#multiPopups').attr('isNextMax', "");
                    $('#multiPopups').attr('isPreviousMax', "");
                    self.showPopup(selectUrl, $('[data-sort-order="' + window.dataPopups[currentIndex].sortOrder + '"]').find('.bss-row-suggest'));
                    window.dataPopups=[];
                }
            }
        },
        selectOnPopup: function (el, productType, sortOrder, isEdit, productId) {
            $("#bss-fastorder-form-option").submit(function(e){
                e.preventDefault();
            });
            var k = sortOrder,
                self = this;
            el = $('#bss-fastorder-' + sortOrder);
            $('#bss-fastorder-super-product-table tbody').each(function () {
                if ($(this).children().hasClass('row-tier-price')) {
                    if (!$(el).closest('tr').find('.bss-addtocart-info.bss-fastorder-hidden').find('.bss-tier-price-group' + k + '').html()) {
                        var data = $(this).find('.row-tier-price').html();
                        var priceWrapper = $(this).find('.price-wrapper').attr('data-price-amount');
                        var ExclPriceCurrent = $(this).find('.price-wrapper.price-excluding-tax').attr('data-price-amount');
                        var name = $(this).find('.input-text.qty.bss-attribute-select').attr('name');
                        el.find('.bss-addtocart-info.bss-fastorder-hidden').append("<div class = 'bss-fastorder-hidden bss-tier-price-group" + k + "' name = " + name + "></div>");
                        el.find('.bss-addtocart-info.bss-fastorder-hidden').find('.bss-tier-price-group' + k + '').html(data);
                        el.find('.bss-addtocart-info.bss-fastorder-hidden').find('.bss-tier-price-group' + k + '').append('<div class = "bss-fastorder-hidden base-price-wrapper" data-price-amount = ' + priceWrapper + ' ></div>');
                        el.find('.bss-addtocart-info.bss-fastorder-hidden').find('.bss-tier-price-group' + k + '').append('<div class = "bss-fastorder-hidden base-excl-tax" data-price-amount = ' + ExclPriceCurrent + ' ></div>');

                    }
                    k++;

                }
                else {
                    if (!el.find('.bss-addtocart-info.bss-fastorder-hidden').find('.bss-tier-price-group' + k + '').html()) {
                        var data = $(this).find('.price-box.price-final_price').html();
                        var name = $(this).find('.input-text.qty.bss-attribute-select').attr('name');
                        el.find('.bss-addtocart-info.bss-fastorder-hidden').append("<div class = 'bss-fastorder-hidden bss-tier-price-group" + k + "' name = " + name + "></div>");
                        el.find('.bss-addtocart-info.bss-fastorder-hidden').find('.bss-tier-price-group' + k + '').html(data);
                    }
                    k++;
                }
            });
            var i = 0;
            if (productType == "configurable") {
                var i = 0;
                $('.bss-swatch-attribute').each(function () {
                    self.options.data[i++] = $(this).attr('bss-option-selected');
                });
                if ($('.bss-product-option').html() != "") {
                    $('.bss-product-option').find('*').each(function () {
                        if (typeof $(this).attr('name') !== 'undefined') {
                            var nameOption = String($(this).attr('name'));
                            if (nameOption.startsWith('bss-options') == true || nameOption.startsWith('options[') == true || nameOption.startsWith('bss_fastorder_links[') == true) {
                                if ($('[name = "' + nameOption + '"]').attr('type') == "radio") {
                                    self.options.data[i++] = nameOption + "+" + $('[name = "' + nameOption + '"]:checked').val();
                                }
                                else if ($('[name = "' + nameOption + '"]').attr('type') == "checkbox") {
                                    var checkArray = [];
                                    var j = 0;
                                    $('[name = "' + nameOption + '"]').each(function () {
                                        if ($(this).attr('checked')) {
                                            checkArray[j++] = $(this).val();
                                        }
                                    });
                                    var nameCheckbox = "";
                                    checkArray.forEach(function (element, key) {
                                        nameCheckbox += element + ",";
                                    });
                                    self.options.data[i++] = nameOption + "+" + nameCheckbox;
                                }
                                else {
                                    self.options.data[i++] = nameOption + "+" + $('[name = "' + nameOption + '"]').val();
                                }
                            }
                        }

                    });
                }
                localStorage.setItem(sortOrder, JSON.stringify(self.options.data));

                if (isEdit === false || isEdit == 'false') {
                    var triggerData = {
                        popupNode: $('#bss-content-option-product'),
                        productId: productId,
                        sortOrder: sortOrder
                    };
                    $('body').trigger('selectOptionClicked', triggerData);
                }
            }
            else {
                $('.bss-product-option').find('input, textarea, select, radio, checkbox').each(function () {
                    if ($(this).attr('type') != 'hidden') {
                        var typeOption = $(this).attr('type');
                        var nameOption = String($(this).attr('name'));
                        if (nameOption.startsWith('bss-options') == true || nameOption.startsWith('options[') == true || nameOption.startsWith('bss_fastorder_links[') == true) {
                            if (typeOption == "radio") {
                                if ($(this).is(":checked")) {
                                    self.options.data[i++] = nameOption + "+" + $(this).val();
                                }
                            } else if (typeOption == "checkbox") {
                                if ($(this).attr('check') != 'true'  && $(this).is(":checked")) {
                                    var nameCheckbox = "";
                                    $('.bss-product-option [name = "' + nameOption + '"]').each(function () {
                                        $(this).attr('check', 'true');
                                        if ($(this).attr('checked')) {
                                            if (nameCheckbox != "") {
                                                nameCheckbox += ",";
                                            }
                                            nameCheckbox += $(this).val();
                                        }
                                    });
                                    self.options.data[i++] = nameOption + "+" + nameCheckbox;
                                }
                            } else {
                                self.options.data[i++] = nameOption + "+" + $('[name = "' + nameOption + '"]').val();
                            }
                        }
                    }

                });
                localStorage.setItem(sortOrder, JSON.stringify(self.options.data));
            }
            if ($(self.options.formSubmitSelector).length > 0) {
                var isValid = $(self.options.formSubmitSelector).valid();
                if (isValid) {
                    self.selectOption(sortOrder);
                    if (productType == "grouped") {
                        var i = 0;
                        var j = 1;
                        el.find('td.bss-addtocart-info.bss-fastorder-hidden').find('div.bss-fastorder-hidden.bss-addtocart-option').find('*').each(function () {
                            var nameOption = String($(this).attr('name'));
                            self.options.data[i++] = nameOption + "+" + $('[name = "' + nameOption + '"]').val();
                            el.children('.bss-product-qty').attr('option-group' + j, $('[name = "' + nameOption + '"]').val());
                            j++;
                        });
                        localStorage.setItem(sortOrder, JSON.stringify(self.options.data));
                    }

                }
            }
            var key = localStorage.getItem('allKeySortOrder') ? localStorage.getItem('allKeySortOrder') : 0;
            key += "+" + sortOrder;
            localStorage.setItem('allKeySortOrder', key);
        }
    });
    return $.bss.fastorder_option;
});