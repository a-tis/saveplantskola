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
    'underscore',
    'mage/template',
    'Magento_Catalog/js/price-utils',
    'mage/translate',
    'jquery/ui',
    'mage/validation',
    'bss/fastorder_option',
    'Bss_FastOrder/js/index/option'
], function ($, _, mageTemplate, priceUtils, $t) {
    "use strict";

    window.productData = [];
    window.dataPopups = [];
    window.additionalAjaxRequests = [];
    window.requestedUrl = [];
    window.prePopulated = false;
    window.showPopupDulicate = true;

    // add selector for Fast Order cms page
    if (!$('body').hasClass('cms-fast-order')) {
        $('body').addClass('cms-fast-order');
    }

    $('.bss-search-input.allow-search').prop('disabled', false);

    $.widget('bss.fastorder', {
        options: {
            row: 1,
            searchUrl: '',
            csvUrl: '',
            fomatPrice: '',
            charMin: '',
            suggestCache: {},
            checkoutUrl:'',
            template:'',
            priceGrouped : 0,
            priceExcelTax : 0,
            getProductSimple:'',
            selectUrl:'',
            productNew :"",
            addProductPopularUrl : "",
            urlChildSku : "",
            urlSwatch : "",
            addMultipleProductUrl : "",
            cancelButtonSelector: 'button#bss-cancel-option',
            rowPrefixSelector: '#bss-fastorder-',
            integrateConfigurableGridView: false,
            integrateRequestForQuote: false

        },

        _create: function () {
            var opt = this.options,
                widgetContainer = this,
                rowFirst = this.element.html(),
                self = this,
                timer = 0,
                rowAdd,
                row;
            window.editProductCache = [];
            localStorage.removeItem('allImagesConfigurable');
            localStorage.removeItem('nextDataPopup');
            localStorage.removeItem('previousDataPopup');
            localStorage.setItem('popupShowed',0);
            $(document).on("click", '#bss-cancel-option', function () {
                var el = $('[data-sort-order="' + $(this).attr('row') + '"]').find('.bss-row-suggest');
                var widget = el.fastorder_option({});
                widget.fastorder_option('cancelOnPopup', el, $(this).attr('row'));
            });
            $(document).on("click", '#bss-select-option', function () {
                var el = $('[data-sort-order="' + $(this).attr('row') + '"]').find('.bss-row-suggest');
                var widget = el.fastorder_option({});
                widget.fastorder_option('selectOnPopup',
                    el,
                    $(this).attr('producttype'),
                    $(this).attr('row'),
                    $(this).attr('isEdit'),
                    $(this).attr('productId')
                );
            });
            $('#bss-fastorder-form .bss-addline').click(function () {
                row = opt.row;
                if (rowAdd > row) {
                    row = rowAdd;
                }
                rowAdd = self._addline(rowFirst, row);
            });

            $(document).on("change","input.bss-upload", function () {
                self._uploadCsv($(this), opt.csvUrl);
            });

            $(document).on("click", ".apply-multiple-product", function () {
                window.showPopupDulicate = false;
                window.dataPopups = [];
                localStorage.setItem('isAddingNewGrouped', false);
                var stackProduct = self._setStackProductSelected();
                if (stackProduct.length == 0) {
                    if ($('.bt-search-template2 .message').text() == '') {
                        $('.bt-search-template2 .message').text($t('Please select item!'));
                    }
                    return;
                }
                self._getStackProductSelected(stackProduct,self.options.getProductSimple);
                $('.bss-search-bar .bss-fastorder-autocomplete').empty().hide();
                $('.bss-search-bar .bss-search-input').val('');
            });

            $(document).on("click", ".bss-search-bar ul li", function () {
                var checkBox = $(this).find('.selectProduct');
                if (checkBox.prop('checked')) {
                    checkBox.prop('checked', false);
                    $(this).removeClass('bss-product-selected');
                } else {
                    checkBox.prop('checked', true);
                    $(this).addClass('bss-product-selected');
                }
            });

            $(document).on('click', '.selectProduct', function () {
                self._addDisabledClickHandler();
            });

            $(document).on("change", ".selectProduct", function () {
                var liElement = $(this).closest("li");
                if (this.checked) {
                    liElement.addClass('bss-product-selected');
                } else {
                    liElement.removeClass('bss-product-selected');
                }
            });

            $(document).on('click', '#bss-access-multiple', function () {
                if (!_.isEmpty($('#bss-multiple-sku').val())) {
                    var text = $("#bss-multiple-sku").val();
                    var lines = text.split(/\r|\r\n|\n/);
                    var data = [];
                    lines.forEach(function (element) {
                        if (element != "") {
                            data.push(element);
                        }
                    });
                    $.ajax({
                        url: opt.addMultipleProductUrl,
                        data: {
                            skuList: data
                        },
                        type: 'post',
                        dataType: 'json',
                        showLoader: true,
                        success: function (response) {
                            $('body').loader('hide');
                            window.dataPopups = [];
                            for (var key in response) {
                                if (window.productData[key]) {
                                    continue;
                                }
                                if (!_.isEmpty(response[key]['popup_html'])) {
                                    response[key]['additional_data'] = [];
                                    response[key]['additional_data']['popup_html'] = response[key]['popup_html'];
                                }
                                if (!_.isEmpty(response[key]['child_product_id'])) {
                                    window.productData[response[key]['child_product_id']] = response[key];
                                } else {
                                    window.productData[response[key]['entity_id']] = response[key];
                                }
                            }
                            window.showPopupDulicate = false;
                            self.addRow(Object.keys(response).length);
                            self.handleResponse(response);
                            self.scrollToMessage();
                            self._showPopup();
                            self._nextPopup();
                        },
                        error: function (er) {
                            self.hideLoader();
                        }
                    });
                }
            });

            $(document).on("keyup","input.bss-search-input", function () {
                var _this = this;
                if ($(_this).val().length >= opt.charMin) {
                    clearTimeout(timer);
                    timer = setTimeout(function () {
                        self._searchProduct(_this,opt.searchUrl);
                    }, 5);
                }
            });

            $(document).on('keyup','.input-text.qty',function(){
                self._checkStatusProceed();
                var currentQty = $(this).val();
                opt.priceExcelTax = 0;
                opt.priceGrouped = 0;
                var productType = $(this).closest('tr.bss-fastorder-row').find('.bss-fastorder-autocomplete .bss-product-type').val();
                var i = 1;

                if(productType != "grouped")
                {
                    $('.bss-fastorder-row-qty .qty').each(function () {
                        var productType = $(this).closest('tr.bss-fastorder-row').find('.bss-fastorder-autocomplete .bss-product-type').val();
                        if(productType != "grouped")
                            self._reloadTotalPrice(this,opt.fomatPrice);
                    });
                }
                else
                {
                    var i = 1;
                    $(this).closest('tr').find('.bss-fastorder-hidden.bss-addtocart-option').find('*').each(function(){
                        var name = $(this).attr('name');
                        if(name && name.startsWith('bss-fastorder-super_group['))
                        {
                            var value = $(this).closest('tr').find('.bss-fastorder-autocomplete .bss-product-qty').attr('option-group'+i);
                            $(this).attr('value',value*currentQty);
                            i++;
                        }
                        self._reloadPriceGrouped(this,name);

                    });
                    $(this).closest('.bss-fastorder-row-qty').find('.bss-product-price-number').attr('value',opt.priceGrouped);
                    $(this).closest('.bss-fastorder-row-qty').find('.bss-product-price-number').attr('data-excl-tax',opt.priceExcelTax);
                    var totalPriceFomat = "";
                    var displayPriceExclTax = "";
                    if (opt.priceGrouped) {
                        totalPriceFomat += widgetContainer._getFormattedPrice(opt.priceGrouped,opt.fomatPrice);
                        if(opt.priceExcelTax != 0)
                        {
                            totalPriceFomat += '<p>';
                            totalPriceFomat += $t('Excl. Tax: ');
                            totalPriceFomat += widgetContainer._getFormattedPrice(opt.priceExcelTax,opt.fomatPrice);
                            totalPriceFomat += '</p>';
                        }
                        $(this).closest('tr.bss-fastorder-row').find('.bss-product-baseprice p').html(displayPriceExclTax);

                    }
                    $(this).closest('tr.bss-fastorder-row').find('.bss-fastorder-row-price .price').html(totalPriceFomat);
                    $(this).closest('tr.bss-fastorder-row').find('.bss-product-baseprice .price').html(displayPriceExclTax);
                    $('#bss-fastorder-form tbody tr').removeClass('bss-row-error');
                    $('#bss-fastorder-form tbody td').removeClass('bss-hide-border');
                }
                self._showTotalRows();
            });

            $(document).on("blur","input.bss-search-input", function () {
                var _this = this,
                    input = $(_this).val();
                $(_this).attr('value', input);
                $(_this).closest('.bss-fastorder-row').find('.bss-fastorder-autocomplete').hide();
                self._showTotalRows();
                self._checkStatusProceed();
                $('.bss-fastorder-multiple-form tbody').scroll();
            });

            $(document).on('change','td.bss-fastorder-row-image.bss-fastorder-img',function(){
                var i = 1;
                $(this).closest('tr').find('.bss-fastorder-hidden.bss-addtocart-option').find('*').each(function(){
                    var name = $(this).attr('name');
                    var value = $(this).val();
                    if(name && name.startsWith('bss-fastorder-super_group['))
                    {
                        $(this).closest('tr').find('.bss-fastorder-autocomplete .bss-product-qty').attr('option-group'+i,value);
                        i++;
                    }
                });
            });

            $(document).on("mouseup", function (e) {
                var container = $('.bss-search-bar .bss-fastorder-autocomplete');
                if (!container.is(e.target) && container.has(e.target).length === 0) {
                    container.hide();
                }
            });

            $(document).on("click",".bss-fastorder-row-delete button", function () {
                self._resetRow(this,rowFirst);
                window.editProductCache = [];
                $('.bss-fastorder-row-qty .qty').each(function () {
                    self._reloadTotalPrice(this,opt.fomatPrice);
                });
            });

            $(window).unload(function(){
                self._removeKeySortOrder();

            });

            $('body').load(function(){
                self._removeKeySortOrder();
            });

            $(document).on("click",".bss-fastorder-row-edit button", function () {
                self._editRow(this);
            });

            $(document).on('click', '#bss-cancel-option', function () {
                self._showTotalRows();
            });

            $(document).on('click', '.next-previous.previous', function () {
                self._previousPopup();
            });

            $(document).on('click', '.next-previous.next', function () {
                self._nextPopup();
            })

            $(document).on("click",".bss-fastorder-row-image img", function () {
                self._showLightbox(this);
            });

            $(document).on('change','.bss-product-info',function(){
                var value = $(this).find('.bss-product-price-amount').attr('data-excl-tax');
                self._reloadTotalPrice(value,opt.fomatPrice);
                self._showTotalRows();
            });

            $(document).on("click",".bss-fastorder-lightbox", function () {
                $(this).fadeTo('slow', 0.3, function () {
                    $(this).remove();
                }).fadeTo('slow', 1);
            });

            $(document).on("click", "#bss-select-option", function () {
                self._showTotalRows();
            });

            $(document).on("change",'.bss-fastorder-row-qty .qty', function () {
                var qty = $(this).val();
                if (qty < 1){
                    $(this).attr('value', 1);
                    self._reloadTotalPrice(this,opt.fomatPrice);

                    self._showTotalRows();
                } else {
                    $(this).attr('value', qty);
                    self._reloadTotalPrice(this,opt.fomatPrice);
                    self._showTotalRows();
                }
            });

            $(document).on("change","#sorter", function () {
                var sortType = $('.action.sorter-action').attr('data-value');
                self._sortTable(this.value,sortType);
            });

            $(document).on('click','.bss-swatch-option.color ,.bss-swatch-option.text', function () {
                if ($(this).closest('.bss-product-option').find('.bss-swatch-attribute.size .bss-attribute-select').val() != "") {
                    var productIdChild = $(this).closest('.bss-product-option').find('.bss-product-child-id').val();
                    self._getImageConfigurableProduct(productIdChild, this);
                } else {
                    var firstIdColor = $(this).attr('bss-option-first-product');
                    $(this).closest('.bss-product-option').find('.bss-product-child-id').val(firstIdColor);
                    var productIdChild = $(this).closest('.bss-product-option').find('.bss-product-child-id').val();
                    self._getImageConfigurableProduct(productIdChild, this);
                }

            });

            $(document).on("click",".action.sorter-action", function (e) {
                e.preventDefault();
                if ($(this).hasClass("sort-desc")) {
                    $(this).removeClass("sort-desc").addClass("sort-asc");
                    $(this).attr("data-value","asc");
                    $(this).attr("title", "Set Ascending Direction");
                } else {
                    $(this).removeClass("sort-asc").addClass("sort-desc");
                    $(this).attr("data-value","desc");
                    $(this).attr("title", "Set Descending Direction");
                }
                var columnName = ($('#sorter').val());
                var sortType = $(this).attr("data-value");
                self._sortTable(columnName,sortType);
            });

            $(document).on("click",".bss-product-qty-up", function () {
                if ($(this).closest('.bss-fastorder-row-qty').find('.bss-product-id-calc').val() != "") {
                    var total = 0;
                    opt.priceExcelTax = 0;
                    opt.priceGrouped = 0;
                    var parrentQty = $(this).closest('.bss-fastorder-row-qty');
                    var currentQty = parrentQty.find('.qty');
                    var increaseQty = parseFloat(currentQty.val()) + 1;
                    var productType = $(this).closest('tr.bss-fastorder-row').find('.bss-fastorder-autocomplete .bss-product-type').val();
                    currentQty.attr('value', increaseQty);
                    if(productType != "grouped")
                    {
                        $('.bss-fastorder-row-qty .qty').each(function () {
                            var productType = $(this).closest('tr.bss-fastorder-row').find('.bss-fastorder-autocomplete .bss-product-type').val();
                            if(productType != "grouped")
                                self._reloadTotalPrice(this,opt.fomatPrice);
                        });
                    }
                    else
                    {
                        var i = 1;
                        $(this).closest('tr').find('.bss-fastorder-hidden.bss-addtocart-option').find('*').each(function(){
                            var name = $(this).attr('name');
                            if(name && name.startsWith('bss-fastorder-super_group['))
                            {
                                var value = $(this).closest('tr').find('.bss-fastorder-autocomplete .bss-product-qty').attr('option-group'+i);
                                $(this).attr('value',value*currentQty.val());
                                i++;
                            }
                            self._reloadPriceGrouped(this,name);

                        });
                        $(this).closest('.bss-fastorder-row-qty').find('.bss-product-price-number').attr('value',opt.priceGrouped);
                        $(this).closest('.bss-fastorder-row-qty').find('.bss-product-price-number').attr('data-excl-tax',opt.priceExcelTax);
                        var totalPriceFomat = "";
                        var displayPriceExclTax = "";
                        if (opt.priceGrouped) {
                            totalPriceFomat += widgetContainer._getFormattedPrice(opt.priceGrouped,opt.fomatPrice);
                            if(opt.priceExcelTax != 0)
                            {
                                totalPriceFomat += '<p>';
                                totalPriceFomat += $t('Excl. Tax: ');
                                totalPriceFomat += widgetContainer._getFormattedPrice(opt.priceExcelTax,opt.fomatPrice);
                                totalPriceFomat += '</p>';
                            }
                            $(this).closest('tr.bss-fastorder-row').find('.bss-product-baseprice p').html(displayPriceExclTax);

                        }
                        $(this).closest('tr.bss-fastorder-row').find('.bss-fastorder-row-price .price').html(totalPriceFomat);
                        $(this).closest('tr.bss-fastorder-row').find('.bss-product-baseprice .price').html(displayPriceExclTax);
                        $('#bss-fastorder-form tbody tr').removeClass('bss-row-error');
                        $('#bss-fastorder-form tbody td').removeClass('bss-hide-border');
                    }

                    self._showTotalRows();
                }
            });

            $(document).on("click",".bss-product-qty-down", function () {
                opt.priceExcelTax = 0;
                opt.priceGrouped = 0;
                var parrentQty = $(this).closest('.bss-fastorder-row-qty');
                var currentQty = parrentQty.find('.qty');
                var productType = $(this).closest('tr.bss-fastorder-row').find('.bss-fastorder-autocomplete .bss-product-type').val();
                if ($(this).closest('.bss-fastorder-row-qty').find('.bss-product-id-calc').val() != "" && parseFloat(currentQty.val()) > 1) {
                    var decreaseQty = (parseFloat(currentQty.val()) - 1).toFixed(2);
                    if (self._isInt(parseFloat(decreaseQty))) {
                        decreaseQty = parseInt(decreaseQty);
                    }
                    currentQty.attr('value', decreaseQty);
                    if(productType != "grouped")
                    {
                        $('.bss-fastorder-row-qty .qty').each(function () {
                            var productType = $(this).closest('tr.bss-fastorder-row').find('.bss-fastorder-autocomplete .bss-product-type').val();
                            if(productType != "grouped")
                                self._reloadTotalPrice(this,opt.fomatPrice);
                        });
                    }
                    else
                    {
                        var i = 1;
                        $(this).closest('tr').find('.bss-fastorder-hidden.bss-addtocart-option').find('*').each(function(){
                            var name = $(this).attr('name');
                            if(name && name.startsWith('bss-fastorder-super_group['))
                            {
                                var value = $(this).closest('tr').find('.bss-fastorder-autocomplete .bss-product-qty').attr('option-group'+i);
                                $(this).attr('value',value*currentQty.val());
                                i++;
                            }
                            self._reloadPriceGrouped(this,name);

                        });
                        $(this).closest('.bss-fastorder-row-qty').find('.bss-product-price-number').attr('value',opt.priceGrouped);
                        $(this).closest('.bss-fastorder-row-qty').find('.bss-product-price-number').attr('data-excl-tax',opt.priceExcelTax);
                        var totalPriceFomat = "";
                        var displayPriceExclTax = "";
                        if (opt.priceGrouped) {
                            totalPriceFomat += widgetContainer._getFormattedPrice(opt.priceGrouped,opt.fomatPrice);
                            if(opt.priceExcelTax != 0)
                            {
                                totalPriceFomat += '<p>';
                                totalPriceFomat += $t('Excl. Tax: ');
                                totalPriceFomat += widgetContainer._getFormattedPrice(opt.priceExcelTax,opt.fomatPrice);
                                totalPriceFomat += '</p>';
                            }
                            $(this).closest('tr.bss-fastorder-row').find('.bss-product-baseprice p').html(displayPriceExclTax);

                        }
                        $(this).closest('tr.bss-fastorder-row').find('.bss-fastorder-row-price .price').html(totalPriceFomat);
                        $(this).closest('tr.bss-fastorder-row').find('.bss-product-baseprice .price').html(displayPriceExclTax);
                        $('#bss-fastorder-form tbody tr').removeClass('bss-row-error');
                        $('#bss-fastorder-form tbody td').removeClass('bss-hide-border');
                    }
                    self._showTotalRows();
                }
            });

            $('#bss-fastorder-form').submit(function (e) {
                if (!self.validateForm('#bss-fastorder-form')) {
                    e.preventDefault();
                    return;
                }
                if (!opt.redirectToCart) {
                    e.preventDefault();
                    var form = $(this);
                    self._submitForm(form);
                }
                document.getElementById("checkProductExists").disabled = true;
                self._removeKeySortOrder();
            });
            $(document).on("click",'#checkProductExists', function () {
                document.getElementById("redirectCheckout").value = "redirectToCheckout";
                self._submitForm('#bss-fastorder-form');
                self._removeKeySortOrder();
            });

            $(document).on('click','#bss-multiple-sku',function(){
                $('.bss-fastorder-autocomplete').hide();
            });

            $(document).ready(function() {
                $('.bss-fastorder-multiple-form .action.tocart').css("display", "table-cell");
                document.getElementById("checkProductExists").disabled = true;
                self._getProductSeller();
                $( ".multiple-wishlist .show-input" ).click(function() {
                    $('.multiple-wishlist #bss-multiple-sku').css("display", "table-cell");
                    $('.multiple-wishlist #bss-multiple-sku').focus();
                    $('.multiple-wishlist .show-input').css("display", "none");
                });
            });
            $(document).on('click', '.closePopup', function () {
                self.closePopup();
            });

            $(document).on('click', '#bss-btn-upload', function () {
                $('[name="bss-upload"]').click();
            });
        },
        closePopup: function () {
            var self = this;
            $('#bss-content-option-product').empty().fadeOut(500);
            $('td.bss-fastorder-row-image.bss-fastorder-img').change();
            window.dataPopups = [];
            localStorage.removeItem('nextDataPopup');
            localStorage.removeItem('previousDataPopup');
            $('#multiPopups').attr('ismulti', "");
            $('#multiPopups').attr('istotal', "");
            $('#multiPopups').attr('currentsortorder', "");
            $('#multiPopups').attr('isNextMax', "");
            $('#multiPopups').attr('isPreviousMax', "");

            self.hideLoader();
        },
        _reloadPriceGrouped:function(el,name)
        {
            var widget = this;
            $(el).closest('tr').find('div.bss-fastorder-hidden').each(function(){
                if($(this).attr('name') == name)
                {
                    var qty = $('input[name = "'+name+'"]').attr('value');
                    var arrayPriceProduct = [];
                    var valueTotalGrouped =[];
                    var arrayPriceExclTax = [];
                    var ExclTax = 0;
                    var valueExcelTax =[];
                    if($(this).find('span.price-wrapper.price-including-tax').html())
                    {
                        if($(this).children().find('ul li').html())
                        {
                            $(this).find('ul li').each(function(){
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
                            var basePrice = $(this).find('.base-price-wrapper').attr('data-price-amount');
                            var exclTaxCurrent  = $(this).find('.base-excl-tax').attr('data-price-amount');
                            valueTotalGrouped[0] = basePrice;
                            valueExcelTax[0] = exclTaxCurrent;
                            if(valueTotalGrouped.sort(function(a, b){return a-b})[0])
                                widget.options.priceGrouped += Number(qty)*Number(valueTotalGrouped.sort(function(a, b){return a-b})[0]);

                            if(valueExcelTax.sort(function(a, b){return a-b})[0])
                                widget.options.priceExcelTax += Number(qty)*Number(valueExcelTax.sort(function(a, b){return a-b})[0]);

                        }
                        else
                        {
                            var priceBase = $(this).find('.price-wrapper.price-including-tax').attr('data-price-amount');
                            var priceExcl = $(this).find('.price-wrapper.price-excluding-tax').attr('data-price-amount');
                            widget.options.priceGrouped += priceBase*qty;
                            widget.options.priceExcelTax += qty*priceExcl;
                        }
                    }
                    else
                    {
                        if($(this).children().find('ul li').html())
                        {
                            $(this).find('ul li').each(function(){
                                var findNumber = /\d+/;
                                var textTierPrice = $(this).html();
                                arrayPriceProduct[Number(textTierPrice.match(findNumber))] = Number($(this).find('.price-wrapper').attr('data-price-amount'));
                                arrayPriceProduct.forEach(function(element,key){
                                    if(Number(key) <= qty)
                                        valueTotalGrouped[key] = arrayPriceProduct[key];
                                });

                            });
                            var basePrice = $(this).find('.base-price-wrapper').attr('data-price-amount');
                            valueTotalGrouped[0] = basePrice;
                            if(valueTotalGrouped.sort(function(a, b){return a-b})[0])
                                widget.options.priceGrouped += Number(qty)*Number(valueTotalGrouped.sort(function(a, b){return a-b})[0]);
                        }
                        else
                        {
                            var price = $(this).find('.base-price-wrapper , .price-wrapper').attr('data-price-amount');
                            widget.options.priceGrouped += price*qty;
                        }

                    }

                }

            });
        },
        _getProductSeller: function () {
            var self = this;
            $.ajax({
                url: self.options.addProductPopularUrl,
                dataType: 'json',
                showLoader: true,
                success: function (response) {
                    if (Object.keys(response).length > 0) {
                        window.prePopulated = true;
                    }
                    for (var key in response) {
                        if (!_.isEmpty(response[key]['popup_html'])) {
                            response[key]['additional'] = [];
                            response[key]['additional']['popup_html'] = response[key]['popup_html'];
                            window.productData[response[key]['entity_id']] = response[key];
                        }
                        if (!_.isEmpty(response[key]['child_product_id'])) {
                            window.productData[response[key]['child_product_id']] = response[key];
                        }
                    }

                    self.addRow(Object.keys(response).length);
                    self.handleResponse(response);
                    self.scrollToMessage();
                    self._showPopup();
                    self._nextPopup();
                },
                error: function (er) {
                    self.hideLoader();
                }
            });
            if (self.options.template == "template-2") {
                localStorage.setItem('popupShowed', 0);
            }
        },
        _addline: function (data, row) {
            var $widget = this;
            row = parseInt(row) + 1;
            var lineNew = '<tr class="bss-fastorder-row bss-row" data-sort-order="'+row+'" id="bss-fastorder-'+row+'">'+data+'</tr>';
            if ($('#bss-fastorder-form table.bss-fastorder-multiple-form tbody tr:last').hasClass('foot1')) {
                $('#bss-fastorder-form table.bss-fastorder-multiple-form tbody tr:last').before(lineNew);
            } else {
                $('#bss-fastorder-form table.bss-fastorder-multiple-form tbody').append(lineNew);
            }
            return row;
        },
        _addDisabledClickHandler: function (el, handler) {
            $("<div />").css({
                position: "absolute",
                top: el.position().top,
                left: el.position().left,
                width: el.width(),
                height: el.height()
            }).click(handler).appendTo("body");
        },
        _getImageConfigurableProduct: function (productId, el) {
            var $widget = this;
            var executeAjax = true;
            var allImagesConfigurable = JSON.parse(JSON.stringify(localStorage.getItem('allImagesConfigurable')));
            if (allImagesConfigurable == null ) {
                allImagesConfigurable = [];
            } else {
                allImagesConfigurable = JSON.parse(allImagesConfigurable);
                allImagesConfigurable.forEach(
                    function (element) {
                        if (element.productId == productId) {
                            $(el).closest('.bss-content-option-product').find('.photo.image').attr('src', element.image);
                            var dataRow = $(el).closest('.bss-content-option-product').find('.bss-row-select').val();
                            $('#bss-fastorder-'+dataRow+'')
                                .find('.bss-fastorder-row-image.bss-fastorder-img img')
                                .attr('src', element.image);
                            executeAjax = false;
                        }
                    }
                );
            }
            if (executeAjax) {
                var imageLoading = $('#baseUrlLoading').attr('data-image-loading');
                $(el).closest('.bss-content-option-product').find('.photo.image').attr('src', imageLoading);
                $.ajax(
                    {
                        url: $widget.options.urlSwatch,
                        data: {product_id: productId, isAjax: true},
                        type: 'get',
                        dataType: 'json',
                        showLoader: false,
                        success: function (res) {
                            $(el).closest('.bss-content-option-product').find('.photo.image').attr('src', res.small);
                            var dataRow = $(el).closest('.bss-content-option-product').find('.bss-row-select').val();
                            $('#bss-fastorder-'+dataRow+'').find('.bss-fastorder-row-image.bss-fastorder-img img').attr('src', res.small);

                            var data = {
                                'productId' : productId,
                                'image' : res.small
                            };
                            localStorage.setItem('imageConfigurable', JSON.stringify(data));
                            allImagesConfigurable.push(data);
                            localStorage.setItem('allImagesConfigurable', JSON.stringify(allImagesConfigurable));
                        },
                    }
                );
            }
        },
        _checkStatusProceed: function (){
            if(this._checkProductExists() == 1)
                document.getElementById("checkProductExists").disabled = false;
            else
                document.getElementById("checkProductExists").disabled = true;
        },
        _checkProductExists:function (){
            var result = 0;
            $('.bss-fastorder-multiple-form.table.data > tbody  > tr').each(function() {
                var text = $(this).find('.bss-product-name-select').html();
                if(text != "")
                    result = 1;
            });
            return result == 1;
        },
        _setStackProductSelected: function () {
            var stackProductSelect = [];
            $('.bss-search-bar .bss-fastorder-autocomplete ul li').each(
                function () {
                    if ($(this).hasClass('bss-product-selected')) {
                        var obj = {
                            sku : $(this).find('.bss-product-sku-select').val(),
                            qty : 1,
                            id : $(this).find('.bss-product-id').val(),
                        };
                        stackProductSelect.push(obj);
                    }
                }
            );
            return stackProductSelect;
        },
        _getStackProductSelected: function (stack, urlGet, showPopup = null) {
            // get products data from cache
            var productData = [];
            for (var key in stack) {
                var pid = stack[key]['id'];
                if (!_.isEmpty(window.productData[pid])) {
                    productData.push(window.productData[pid]);
                }
            }

            $('body').loader('show');
            if (showPopup != null) {
                localStorage.setItem('popupShowed', 1);
            } else {
                localStorage.setItem('popupShowed', 0);
            }
            var $widget = this;
            $widget.handleResponse(productData);

            if (_.isEmpty(window.additionalAjaxRequests)) {
                $widget.hideLoader();
                if (showPopup == null) {
                    $widget._showPopup();
                    $widget._nextPopup();
                }
            } else {
                Promise.all(window.additionalAjaxRequests).then(() => {
                    // all requests finished successfully
                    $widget.hideLoader();
                    if (showPopup == null) {
                        $widget._showPopup();
                        $widget._nextPopup();
                    }
                    window.additionalAjaxRequests = [];
                }).catch(() => {
                    $widget.hideLoader();
                    window.additionalAjaxRequests = [];
                    // all requests finished but one or more failed
                });
            }

            if ($widget.options.template == "template-1") {
                localStorage.setItem('popupShowed', 0);
            }
        },
        _addMultipleProduct: function (stack,urlGet,showPopup = null) {
            $('body').loader('show');
            if (showPopup != null) {
                localStorage.setItem('popupShowed',1);
            } else {
                localStorage.setItem('popupShowed',0);
            }
            var $widget = this;
            $.ajax(
                {
                    type: 'post',
                    url: urlGet,
                    data: {product:stack},
                    dataType: 'json',
                    showLoader: true,
                    global: false,
                    success: function (res) {
                        $widget.handleResponse(res);
                        if (showPopup == null) {
                            $widget._showPopup();
                            $widget._nextPopup();
                        }
                        // if ($widget.options.template == "template-1") {
                        //     localStorage.setItem('popupShowed',0);
                        // }
                        $widget.hideLoader();
                    },
                    error: function () {
                        $widget.hideLoader();
                    }
                }
            );
        },
        _getDataPopups: function () {
            if (!_.isEmpty(window.dataPopups)) {
                window.dataPopups.forEach(function (el,index) {
                    if($.isEmptyObject(el)) {
                        delete window.dataPopups[index];
                    }
                })
            }
            return window.dataPopups;
        },
        _showPopup: function () {
            self = this;
            var nextData = [];
            var previousData = [];
            var dataPopups = self._getDataPopups();
            $('#multiPopups').attr('istotal', self._returnLengthNotEmpty(dataPopups));
            if (self._returnLengthNotEmpty(dataPopups) > 1) {
                $('#multiPopups').attr('isMulti', 1);
            } else {
                $('#multiPopups').attr('isMulti', 0);
            }
            if (self._returnLengthNotEmpty(dataPopups) == 0) {
                return false;
            }
            dataPopups.forEach(function(el,index) {
                nextData.push(index);
            });
            localStorage.setItem('nextDataPopup', nextData);
            localStorage.setItem('previousDataPopup', previousData);
            localStorage.setItem('currentDataPopup', "");
        },
        _setCurrentPopup: function (typeFull = null) {
            var dataPopups = self._getDataPopups();
            var sort = $('#multiPopups').attr('currentSortOrder');
            var element = $('#bss-fastorder-'+dataPopups[sort].sortOrder).find('.bss-row-suggest');
            var widget = element.fastorder_option({});
            if (typeFull == 'next') {
                $('#multiPopups').attr('isNextMax',1);
            } else {
                $('#multiPopups').attr('isNextMax',0);
            }

            if (typeFull == 'prev') {
                $('#multiPopups').attr('isPreviousMax',1);
            } else {
                $('#multiPopups').attr('isPreviousMax',0);
            }
            widget.fastorder_option('showPopup', self.options.selectUrl, element);
        },
        _nextPopup: function () {
            if (self._returnLengthNotEmpty(self._getDataPopups()) == 0) {
                return false;
            }
            var $widget = this,
                nextData = localStorage.getItem('nextDataPopup'),
                nextData = nextData.split(',');
            var currentData = $('#multiPopups');
            if (currentData.attr('currentSortOrder') == "") {
                currentData.attr('currentSortOrder', nextData[0]);
                $widget._setCurrentPopup('prev');
                return;
            } else {
                for (var i = 0; i < nextData.length; i++) {
                    if (currentData.attr('currentSortOrder') == nextData[i]) {
                        var temp = i + 1;
                        if (temp <= nextData.length - 1) {
                            currentData.attr('currentSortOrder', nextData[temp]);
                            if (temp == nextData.length - 1) {
                                $widget._setCurrentPopup('next');
                            } else {
                                $widget._setCurrentPopup();
                            }
                            return;
                        }
                    }
                }
            }
            return nextData.length;
        },
        _previousPopup: function () {
            var $widget = this,
                nextData = localStorage.getItem('nextDataPopup'),
                nextData = nextData.split(',');
            var currentData = $('#multiPopups');
            for (var i = 0; i < nextData.length; i++) {
                if (currentData.attr('currentSortOrder') == nextData[i]) {
                    var temp = i - 1;
                    if (temp >= 0) {
                        currentData.attr('currentSortOrder', nextData[temp]);
                        if (temp == 0) {
                            $widget._setCurrentPopup('prev');
                        } else {
                            $widget._setCurrentPopup();
                        }
                        return;
                    }
                }
            }
            return nextData.length;
        },
        _returnLengthNotEmpty: function (array) {
            if (array == null) {
                return 0;
            }
            return array.reduce((acc,cv)=>(cv)?acc+1:acc,0);
        },
        _searchProduct: function (el,searchUrl,query = null) {
            localStorage.setItem('popupShowed',0);
            var input = $(el).val().trim(),
                $widget = this;
            if (input == '') {
                $(el).closest('.bss-fastorder-row').find('.bss-fastorder-autocomplete').empty();
                return false;
            }
            if (query) {
                input = query;
            }
            var sortOrder = $(el).closest('.bss-fastorder-row').attr('data-sort-order');
            $(el).addClass('bss-loading');
            var suggestCacheKey = 'bss-'+input;
            $widget._XhrKiller();
            if (suggestCacheKey in $widget.options.suggestCache) {
                $widget._getItemsLocalStorage(el, suggestCacheKey, sortOrder);
            } else {
                $widget.xhr = $.ajax({
                    type: 'GET',
                    url: searchUrl,
                    data: {
                        q: input
                    },
                    global: false,
                    dataType: 'json',
                    cache: true,
                    success: function (data) {
                        $widget._setItemsLocalStorage(el, suggestCacheKey, JSON.stringify(data), sortOrder);
                        for (var key in data) {
                            if (!_.isEmpty(window.productData[key]) && !_.isEmpty(window.productData[key]['additional_data'])) {
                                continue;
                            }
                            if (data.hasOwnProperty(key)) {
                                // $widget._storageProductTierPrice(data[key]);
                                window.productData[data[key]['entity_id']] = data[key];
                            }
                        }
                    },
                });
            }
        },

        /**
         * @param el
         * @private
         */
        _submitForm: function (el) {
            var opt = this.options;
            var redirectCheckout = document.getElementById("redirectCheckout");
            var actionForm = $(el).attr('action');
            var formData = new FormData($(el)[0]);
            $('#bss-fastorder-form tr').removeClass('bss-row-error');
            $('#bss-fastorder-form td').removeClass('bss-hide-border');
            var $widget = this;
            $.ajax({
                type: 'post',
                url: actionForm,
                data: formData,
                dataType: 'json',
                showLoader: true,
                processData: false,
                contentType: false,
                success: function (data) {
                    $widget.scrollToMessage();
                    if (data.status == true) {
                        $('.bss-fastorder-row-delete button').click();
                        window.editProductCache = [];
                        if (redirectCheckout.value == "redirectToCheckout") {
                            window.location.href = opt.checkoutUrl;
                        }
                    } else if (data.status == false && data.row >= 0) {
                        $('#bss-fastorder-form tbody #bss-fastorder-' + data.row).addClass('bss-row-error');
                        if ($('#bss-fastorder-form tbody #bss-fastorder-' + data.row).next().length > 0) {
                            $('#bss-fastorder-form tbody #bss-fastorder-' + data.row).next().find('td').addClass('bss-hide-border');
                        } else {
                            $('#bss-fastorder-form tfoot tr td').addClass('bss-hide-border');
                        }
                        redirectCheckout.value = "error";
                        $widget.scrollToErrorTable();
                    }
                    $widget._showTotalRows();
                    $widget.hideLoader();
                },
                error: function (e) {
                    console.trace('Can not add to cart: ' + e);
                }
            });
        },

        hideLoader: function() {
            $('body').loader('hide');
            $('.loading-mask').hide();
        },

        /**
         * Scroll to message section
         */
        scrollToMessage: function () {
            if ($('.page.messages').is(':visible')) {
                setTimeout(function() {
                    $([document.documentElement, document.body]).animate({
                        scrollTop: $('body').offset().top
                    }, 800);
                }, 500);
            }
        },

        _clearRow: function () {
            var $widget = this;
            var countRow = 0,rowAdd;
            var row = $('.bss-fastorder-multiple-form.table.data > tbody > tr');
            row.each(function () {
                if ($(this).find('.bss-product-id-calc').val() !== "") {
                    countRow++;
                }
            });
            if (countRow > 0) {
                row.each(function () {
                    if($(this).find('.bss-product-id-calc').val() === "") {
                        $(this).remove();
                    }
                });
            }
        },
        _removeKeySortOrder: function(){
            if(localStorage.getItem('allKeySortOrder') != null)
            {
                var keyExists = localStorage.getItem('allKeySortOrder');
                var data = keyExists.split('+');
                data.forEach(function(element){
                    localStorage.removeItem(element);
                });
                localStorage.removeItem('allKeySortOrder');
                localStorage.removeItem('sortOrderNew');
            }
        },
        _resetRow: function (el,data) {
            var sortOrder = $(el).closest('.bss-fastorder-row').attr('data-sort-order');
            $(el).closest('.bss-fastorder-row').html(data);
            $(el).hide();
            $('#bss-fastorder-form tr').removeClass('bss-row-error');
            $('#bss-fastorder-form td').removeClass('bss-hide-border');
            localStorage.removeItem(sortOrder);
            this._showTotalRows();
        },
        _editRow: function (el) {
            localStorage.setItem('isAddingNewGrouped', false);
            localStorage.setItem('popupShowed', 0);

            var $widget = this;
            var sortOrder = $(el).closest('.bss-fastorder-row').data('sort-order'),
                productId = $(el).closest('.bss-fastorder-row').find('input.bss-product-id').val();
            if (productId) {
                window.editProductCache[sortOrder] = $(el).closest('.bss-fastorder-row').html();
            }
            //$(el).closest('.bss-fastorder-row').find('.bss-fastorder-autocomplete li:first a').mousedown();
            //if ($widget.options.template == 'template-2') {
            var row = $(el).closest('.bss-fastorder-row').find('.bss-fastorder-autocomplete li:first a');
            var popup = row.fastorder_option({});
            popup.fastorder_option('showPopup', $widget.options.selectUrl, row);
            // }
            var productType = $(el).closest('tr.bss-fastorder-row').find('.bss-fastorder-autocomplete .bss-product-type').val();
            if(productType == "grouped")
            {
                $(el).closest('tr').find('td.bss-addtocart-info').find('*').each(function(){
                    var name = $(this).attr('name');
                    if(String(name).startsWith('bss-fastorder-super_group[') == true)
                    {
                        $(this).remove();
                    }
                });
            }

        },
        _showLightbox: function (el) {
            $('.bss-fastorder-lightbox').remove();
            var img = $(el).parent().html();
            var elLightbox = '<div class="bss-fastorder-lightbox">'+img+'</div>';
            $('form.bss-fastorder-form').fadeTo('slow', 0.3, function () {
                $(this).append(elLightbox);
            }).fadeTo('slow', 1);
        },
        _showTotalRows: function () {
            var $widget = this;
            var totalQty = 0;
            var totalProduct = 0;
            var totalPrice = 0;
            var totalPriceExclTax = 0;
            $('.bss-fastorder-row').each(function () {
                var id = $(this).find('.bss-fastorder-row-qty').find('.bss-product-id-calc').val();
                if (id !== "") {
                    totalProduct++;
                }
            });
            totalProduct=totalProduct-1;
            $('.bss-fastorder-row-qty .qty').each(function () {
                if (!_.isEmpty($(this).closest('.bss-fastorder-row-qty').find('.bss-product-id-calc').val())) {
                    totalQty += parseFloat($(this).val());
                    var productType = $(this).closest('tr.bss-fastorder-row').find('.bss-fastorder-autocomplete .bss-product-type').val();
                    if(productType != "grouped")
                    {
                        var current_qty = $(this).val();
                        var current_price = $(this).closest('.bss-fastorder-row-qty').find('.bss-product-price-number').attr('value');
                        var priceExclTax = $(this).closest('.bss-fastorder-row-qty').find('.bss-product-price-number').attr('data-excl-tax');
                        if (priceExclTax > 0) {
                            totalPriceExclTax += current_qty * parseFloat(priceExclTax);
                        }
                        current_price = (current_price === "") ? 0 : parseFloat(current_price);
                        totalPrice += current_qty * current_price;
                    }
                    else
                    {
                        totalPriceExclTax += parseFloat($(this).closest('td').find('.bss-product-price-number').attr('data-excl-tax'));
                        totalPrice += parseFloat($(this).closest('td').find('.bss-product-price-number').val());

                    }

                }
            });
            var opt = this.options;
            var totalPriceFomat = this._getFormattedPrice(totalPrice, opt.fomatPrice);
            if (parseFloat(totalPriceExclTax) > 0) {
                totalPriceExclTax = $t(' <p class="total-price-tax"> Excl. Tax: ') + this._getFormattedPrice(totalPriceExclTax, opt.formatPrice) + ('</p>');
                totalPriceFomat += totalPriceExclTax;
            }
            $('.bss-number-sub-total').html(totalPriceFomat);
            if ($widget._isInt(totalQty)) {
                $('.bss-number-total-qty').text(parseInt(totalQty));
            } else {
                $('.bss-number-total-qty').text(totalQty.toFixed(2));
            }
            $('.bss-number-product').text(totalProduct);
        },
        _getFormattedPrice: function (price,fomatPrice) {
            return priceUtils.formatPrice(price, fomatPrice);
        },
        _sortTable: function (columnName, sortType) {
            this._clearRow();
            var returnTypeA,returnTypeB,_item,_nextItem, isStringSort = false;
            if (sortType === 'desc') {
                returnTypeA = 1;
                returnTypeB = -1;
            } else {
                returnTypeA = -1;
                returnTypeB = 1;
            }

            var rows = $('.bss-fastorder-multiple-form.table.data tbody tr').get();
            rows.sort(function (item, nextItem) {
                switch (columnName) {
                    case 'sku':
                        _item = $(item).find('.input-text.bss-search-input').val();
                        _nextItem = $(nextItem).find('.input-text.bss-search-input').val();
                        isStringSort = true;
                        break;
                    case 'price':
                        if ($(item).find('.bss-product-price-group').val() !== "") {

                            _item = parseFloat($(item).find('.bss-product-price-group').val());
                        } else {
                            _item = parseFloat($(item).find('.bss-product-price-number').val());
                        }
                        if ($(nextItem).find('.bss-product-price-group').val() !== "") {
                            _nextItem = parseFloat($(nextItem).find('.bss-product-price-group').val());
                        } else {
                            _nextItem = parseFloat($(nextItem).find('.bss-product-price-number').val());
                        }
                        break;
                    case 'qty':
                        _item = parseFloat($(item).find('.bss-fastorder-row-qty').find('input.qty').val());
                        _nextItem = parseFloat($(nextItem).find('.bss-fastorder-row-qty').find('input.qty').val());
                        break;
                    default: // default column name
                        isStringSort = true;
                        _item = $(item).children('.bss-fastorder-row-name').text().toUpperCase();
                        _nextItem = $(nextItem).children('.bss-fastorder-row-name').text().toUpperCase();
                }
                var isRowAProduct = $(item).find('.bss-product-id-calc').val();
                var isRowBProduct = $(nextItem).find('.bss-product-id-calc').val();

                if (_.isEmpty(isRowAProduct) || _.isEmpty(isRowBProduct)) {
                    return 0;
                }

                if (isStringSort) {
                    return sortType === 'desc' ? _nextItem.localeCompare(_item) : _item.localeCompare(_nextItem);
                } else {
                    return sortType === 'desc' ? _nextItem - _item : _item - _nextItem;
                }
            });
            $.each(rows, function (index, row) {
                $('.bss-fastorder-multiple-form.table.data').children('tbody').append(row);
            });
        },
        _reloadTotalPrice: function (el,fomatPrice) {
            var totalPrice,
                priceCurrents,
                totalPriceExclTax,
                totalPriceFomat,
                totalPriceFomatExclTax,
                productCurId,
                displayPriceExclTax = "",
                qty = $(el).val(),
                price = $(el).closest('.bss-fastorder-row-qty').find('.bss-product-price-number').val(),
                priceExclTax = $(el).closest('.bss-fastorder-row-qty').find('.bss-product-price-number').attr('data-excl-tax'),
                priceOption = $(el).closest('.bss-fastorder-row-qty').find('.bss-product-price-custom-option').val(),
                priceOptionExclTax = $(el).closest('.bss-fastorder-row-qty').find('.bss-product-price-custom-option').attr('data-excl-tax'),
                productId = $(el).closest('tr.bss-fastorder-row').find('.bss-addtocart-info .bss-product-id').val(),
                productType = $(el).closest('tr.bss-fastorder-row').find('.bss-fastorder-autocomplete .bss-product-type').val(),
                downloadOption = $(el).closest('.bss-fastorder-row-qty').find('.bss-product-price-number-download').val(),
                decimal = parseInt($(el).closest('.bss-fastorder-row-qty').find('.qty').attr('data-decimal')),
                obj = {},
                row = $(el).closest('tr.bss-fastorder-row').attr('data-sort-order');
            if (_.isEmpty(price)) {
                return false;
            }
            if (_.isEmpty(productId)) {
                return false;
            }
            if (decimal !== 0) {
                qty = parseFloat(qty);
            } else {
                qty = parseFloat(qty);
            }
            obj = (!_.isEmpty(window.productData[productId])
                && !_.isEmpty(window.productData[productId]['additional_data'])) ? window.productData[productId]['additional_data']['price_data'] : [];

            productCurId = $(el).closest('tr.bss-fastorder-row').find('.bss-fastorder-row-qty .bss-product-id-calc').val();
            if (productId != productCurId && !_.isEmpty(obj)) {
                obj = obj['tier_price_child_'+productCurId];
            }
            if (qty > 0 && obj != null && !_.isEmpty(obj) && productType != 'grouped') {
                var qtyTotal = qty;
                $('.bss-fastorder-row .bss-fastorder-row-qty .bss-product-id-calc').each(function () {
                    var productIdClone = $(this).val(),
                        rowClone = $(this).closest('tr.bss-fastorder-row').attr('data-sort-order'),
                        qtyClone = 0;
                    if (row != rowClone) {
                        qtyClone = $(this).closest('tr.bss-fastorder-row').find('.bss-fastorder-row-qty .qty').val();
                    }
                    if (parseInt(productIdClone) == parseInt(productCurId)) {
                        qtyTotal += parseFloat(qtyClone);
                    }
                });
                for (var key in obj) {
                    if (typeof obj[key]['final_price'] != 'object') {
                        if (parseFloat(qtyTotal) >= parseFloat(key)) {
                            price = obj[key]['final_price'] + parseFloat(priceOption) + parseFloat(downloadOption);
                            if (obj[key]['base_price']) {
                                priceExclTax = obj[key]['base_price'] + parseFloat(priceOptionExclTax) + parseFloat(downloadOption);
                            }
                        }
                    } else {
                        for (var key2 in obj[key]['final_price']) {
                            if (parseFloat(qtyTotal) >= parseFloat(key2)) {
                                price = obj[key]['final_price'][key2] + parseFloat(priceOption) + parseFloat(downloadOption);
                                if (obj[key]['base_price']) {
                                    priceExclTax = obj[key]['base_price'] + parseFloat(priceOptionExclTax) + parseFloat(downloadOption);
                                }
                            }
                        }
                    }
                }
                $(el).closest('.bss-fastorder-row-qty').find('.bss-product-price-number').val(price);
                $(el).closest('.bss-fastorder-row-qty').find('.bss-product-price-number').attr('data-excl-tax', priceExclTax);
            }
            if (productId && qty > 0) {
                totalPrice = qty* parseFloat(price);
                totalPriceExclTax = qty * parseFloat(priceExclTax);
                totalPriceFomat = this._getFormattedPrice(totalPrice, fomatPrice);
                priceCurrents = totalPrice/qty;
                if (totalPriceExclTax) {
                    totalPriceFomatExclTax = this._getFormattedPrice(totalPriceExclTax, fomatPrice);
                    totalPriceFomat += '<p>';
                    totalPriceFomat += $t('Excl. Tax: ');
                    totalPriceFomat += totalPriceFomatExclTax;
                    totalPriceFomat += '</p>';
                    displayPriceExclTax += $t('Excl. Tax: ');
                    displayPriceExclTax += this._getFormattedPrice(priceExclTax, fomatPrice);
                    $(el).closest('tr.bss-fastorder-row').find('.bss-product-baseprice p').html(displayPriceExclTax);
                }
                priceCurrents = this._getFormattedPrice(priceCurrents, fomatPrice);

                if ($(el).closest('tr.bss-fastorder-row').find('.bss-product-hide-price').val() != '1') {
                    $(el).closest('tr.bss-fastorder-row').find('.bss-fastorder-row-price .price').html(totalPriceFomat);
                    $(el).closest('tr.bss-fastorder-row').find('.bss-product-baseprice .price').html(priceCurrents);
                } else {
                    $(el).parent().css({"position":"absolute","left":"-9999px","top":"-9999px"})
                    $(el).parent().append('<input type="hidden"value="0" name="qtys[]" >')
                    $(el).remove();
                    $(el).closest('tr.bss-fastorder-row').find('.bss-addtocart-info .bss-product-id').val('');
                    $(el).closest('.bss-fastorder-row-qty').find('.bss-product-price-number').val(0);
                    $(el).closest('.bss-fastorder-row-qty').find('.bss-product-price-number').attr('data-excl-tax', 0);
                }
                if ($(el).closest('tr.bss-fastorder-row').find('.bss-product-hide-price').val() == '0'
                    && $(el).closest('tr.bss-fastorder-row').find('.bss-product-hide-price-html').val() != ''){
                    var checkErrorMessage = ($(el).closest('tr.bss-fastorder-row').find(".bss-fastorder-row-name > span ").hasClass('hide-price-message'));
                    if (!checkErrorMessage){
                        $(el).closest('tr.bss-fastorder-row').find('.bss-fastorder-row-name').append('<span class="hide-price-message" style="color:#f00">' + $(el).closest('tr.bss-fastorder-row').find('.bss-product-hide-price-html').val() + '</span>')
                    }
                }
                $('#bss-fastorder-form tbody tr').removeClass('bss-row-error');
                $('#bss-fastorder-form tbody td').removeClass('bss-hide-border');
            }
            this._checkStatusProceed();
        },
        _XhrKiller: function () {
            var $widget = this;
            if ($widget.xhr !== undefined && $widget.xhr !== null) {
                $widget.xhr.abort();
                $widget.xhr = null;
            }
        },
        _getItemsLocalStorage: function (el, suggestCacheKey, sortOrder, dataMultiBox = null) {
            var $widget = this,
                data1 = $widget.options.suggestCache[suggestCacheKey],
                data2 = '';
            if (data1 && data1 != "null" && data1 != '[]') {
                data2 = JSON.parse(data1);
            }
            var html = mageTemplate('#bss-fastorder-search-complete',{data:data2});
            if ($widget.options.template == 'template-1') {
                $('#bss-fastorder-'+sortOrder+'').find('.bss-fastorder-autocomplete').show();
                $('#bss-fastorder-'+sortOrder+'').find('.bss-fastorder-autocomplete').html(html);
            } else {
                if (dataMultiBox == null) {
                    $('.bss-search-bar').find('.bss-fastorder-autocomplete').show();
                }
                $('.bss-search-bar').find('.bss-fastorder-autocomplete').html(html);
            }
            $(el).removeClass('bss-loading');
            if (data2) {
                if (data2.length == 1) {
                    $(el).closest('.bss-fastorder-row-ref').find(".bss-row-suggest").trigger('mousedown');
                }
            }
        },

        /**
         * @private
         */
        scrollToErrorTable: function() {
            setTimeout(function(){
                var container = $('.bss-fastorder-multiple-form tbody'),
                    scrollTo = $('.bss-row-error');
                $(container).animate({
                    scrollTop: scrollTo.offset().top - container.offset().top + container.scrollTop()
                });
            },1000)

        },
        _setItemsLocalStorage: function (el, suggestCacheKey, data, sortOrder) {
            var $widget = this;
            $widget.options.suggestCache[suggestCacheKey] = data;
            $widget._getItemsLocalStorage(el, suggestCacheKey, sortOrder);

        },
        _isInt: function (n) {
            return Number(n) === n && n % 1 === 0;
        },
        _isFloat: function (n) {
            return Number(n) === n && n % 1 !== 0;
        },
        _uploadCsv: function (el, csvUrl) {
            var file_data = el.prop("files")[0],
                data = new FormData(),
                $widget = this;
            // reset input file
            el.val('');

            if (!file_data) {
                return false;
            }
            $widget._XhrKiller();
            data.append("file", file_data);
            window.dataPopups = [];
            $widget.xhr = $.ajax({
                type: 'post',
                url: csvUrl,
                data: data,
                cache: false,
                contentType: false,
                processData: false,
                showLoader: true,
                success: function (response) {
                    $('body').loader('hide');
                    $widget.scrollToMessage();
                    for (var key in response) {
                        if (!_.isEmpty(response[key]['popup_html'])) {
                            response[key]['additional_data'] = [];
                            response[key]['additional_data']['popup_html'] = response[key]['popup_html'];
                            window.productData[response[key]['entity_id']] = response[key];
                        }
                        if (!_.isEmpty(response[key]['child_product_id'])) {
                            window.productData[response[key]['child_product_id']] = response[key];
                        }
                    }
                    window.showPopupDulicate = false;
                    $widget.addRow(Object.keys(response).length);
                    $widget.handleResponse(response);
                    $widget._showPopup();
                    $widget._nextPopup();
                },
                error: function (e) {
                    el.val('');
                    console.warn('Can not import csv' + e);
                }
            });
        },
        _dataBestSeller: function () {
            var widget = this;
            var arraySku = widget.options.listBestSeller;
            var data = [];
            if (arraySku.length > 0) {
                arraySku.forEach(function (element) {
                    var obj = {
                        sku: element,
                        qty: 1
                    };
                    data.push(obj);
                });
                widget._getStackProductSelected(data, widget.options.getProductSimple, 1);
            } else {
                return false;
            }
        },
        addRow: function (rowNumber) {
            var lineCurrent,
                lineUse,
                lineSurplus,
                lineNew,
                i;
            lineCurrent = $('#bss-fastorder-form .bss-row').size();
            lineUse = $('#bss-fastorder-form .bss-fastorder-row .bss-fastorder-autocomplete ul').size();
            lineSurplus = parseInt(lineCurrent) - parseInt(lineUse);
            if (rowNumber <= lineSurplus) {
                return;
            }
            lineNew = parseInt(rowNumber) - parseInt(lineSurplus);
            for (i = 0; i < lineNew; i++) {
                $('#bss-fastorder-form .bss-addline').click();
            }

        },
        /* Validation Form*/
        validateForm: function (form) {
            return $(form).validation() && $(form).validation('isValid');
        },

        /**
         * @param attributeData
         * @param sortOrder
         */
        selectConfigurableAttributes: function (attributeData, sortOrder) {
            var self = this;
            var rowSelector = $(self.options.rowPrefixSelector + sortOrder);
            var elProductInfo = rowSelector.find('.bss-fastorder-row-name .bss-product-option-select ul');
            var elAddToCart = rowSelector.find('.bss-addtocart-option');
            elProductInfo.empty();

            var attributeIds = [];
            for (var attributeId in attributeData) {
                if (!attributeData.hasOwnProperty(attributeId)) continue;

                // display selected option
                var selectInfo =
                    '<li>' +
                    '<span class="label">' + attributeData[attributeId].label +
                    '</span>&nbsp;:&nbsp;' + attributeData[attributeId].value +
                    '</li>';
                $(elProductInfo).append(selectInfo);

                // append to form input
                var selectedAttrInput =
                    '<input type="hidden" ' +
                    'class="bss-attribute-select" ' +
                    'name="bss-fastorder-super_attribute[' + sortOrder + '][' + attributeData[attributeId].id + ']" ' +
                    'value="' + attributeId + '">';
                elAddToCart.append(selectedAttrInput);

                attributeIds.push(attributeId)
            }
            // save selected option to storage
            localStorage.setItem(sortOrder, JSON.stringify(attributeIds));

            // fake popup content to make edit button know this is edit function
            window.editProductCache[sortOrder] = '';

            var isHidePriceValue = parseInt(rowSelector.find('.bss-fastorder-autocomplete .bss-row-suggest .bss-product-hide-price').val());
            // enable open popup
            if (isHidePriceValue !== 1) {
                rowSelector.find('.bss-fastorder-autocomplete .bss-row-suggest .bss-show-popup').val(1);
                rowSelector.find('.bss-fastorder-row-edit button').show();
            }

            if (rowSelector.is(':hidden')) {
                rowSelector.show();
            }
        },

        /**
         * @param res
         */
        handleResponse: function (res) {
            var displayPriceExclTax = "",
                displayPriceExclTaxTotal = "",
                lengthObj = 0,
                $widget = this;
            if (res) {
                if (typeof res == "object") {
                    lengthObj =  Object.keys(res).length;
                } else {
                    lengthObj =  res.length;
                }
                $widget.addRow(lengthObj);
                var i = 0;
                for (var key in res) {
                    i++;
                    var html,
                        productData;
                    productData = res[key];
                    if (res.hasOwnProperty(key)) {
                        html = mageTemplate('#bss-fastorder-search-complete', {data: [productData]});
                        $('#bss-fastorder-form .bss-row').each(
                            function () {
                                var sortOrder,
                                    self = $(this),
                                    qty = 0;
                                if (productData['qty'] && parseFloat(productData['qty']) > 0) {
                                    qty = productData['qty'];
                                }
                                if (self.find('.bss-row-suggest').length > 0) {
                                    return true;
                                }
                                sortOrder = self.attr('data-sort-order');
                                var rowSelector = $($widget.options.rowPrefixSelector + sortOrder);

                                rowSelector.find('.bss-fastorder-row-qty .bss-product-hide-price').val(productData['product_hide_price']);
                                rowSelector.find('.bss-fastorder-row-qty .bss-product-hide-price-html').val(productData['product_hide_html']);
                                rowSelector.find('.bss-fastorder-autocomplete').html(html);
                                if (rowSelector.find('.bss-fastorder-autocomplete .bss-product-type').val() == "grouped") {
                                    rowSelector.find('.bss-fastorder-autocomplete .bss-product-qty').attr('qty-group', qty);
                                }
                                rowSelector.find('.bss-fastorder-autocomplete .bss-product-qty').val(qty);
                                var valueExclTax = rowSelector.find('.bss-product-price-amount').attr('data-excl-tax');
                                rowSelector.find('.bss-fastorder-autocomplete .bss-row-suggest:first').mousedown();
                                rowSelector.find('.bss-fastorder-row-qty').find('.bss-product-price-number').attr('data-excl-tax', valueExclTax.replace(/[^0-9.]/g, ''));
                                if (i == res.length) {
                                    displayPriceExclTax += $t('Excl. Tax: ');
                                    displayPriceExclTax += $widget._getFormattedPrice(rowSelector.find('.bss-product-price-number').attr('data-excl-tax'), $widget.options.fomatPrice);
                                    rowSelector.find('.bss-product-baseprice p').html(displayPriceExclTax);
                                    var value = rowSelector.find('.bss-product-price-number').attr('data-excl-tax');
                                    var qty = rowSelector.find('.bss-fastorder-row-qty').find('.input-text.qty').val();
                                    var total = value * qty;
                                    displayPriceExclTaxTotal += $t('Excl. Tax: ');
                                    displayPriceExclTaxTotal += $widget._getFormattedPrice(total, $widget.options.fomatPrice);
                                    rowSelector.find('.bss-fastorder-row-price .price p').html(displayPriceExclTaxTotal);
                                }

                                if (!_.isEmpty(productData['child_product_id'])) {
                                    rowSelector.find('.bss-fastorder-row-qty .bss-product-id-calc').val(productData['child_product_id']);
                                    rowSelector.find('.bss-fastorder-row-qty .qty').change();
                                }
                                if (!_.isEmpty(productData['configurable_attributes'])) {
                                    $widget.selectConfigurableAttributes(productData['configurable_attributes'], sortOrder);
                                }

                                if (productData['popup'] == 1) {
                                    var newDataPopup = {
                                        sortOrder: sortOrder,
                                        productId: productData['entity_id']
                                    };
                                    var find = window.dataPopups.find(x =>
                                        (x.productId === productData['entity_id'] &&
                                            x.sortOrder === sortOrder
                                        )
                                    );
                                    if (_.isEmpty(find)) {
                                        window.dataPopups.push(newDataPopup);
                                    }
                                }

                                return false;
                            }
                        );
                    }
                    if (i == lengthObj) {
                        window.prePopulated = false;
                    }
                }
                if (res.length >= 1) {
                    $('#checkProductExists').prop('disabled', false);
                }
            }
        }
    });
    return $.bss.fastorder;
});
