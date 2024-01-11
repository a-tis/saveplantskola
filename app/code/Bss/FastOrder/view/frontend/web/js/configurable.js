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
    "bss/fastorder_swatch"
    ], function ($) {
        'use strict';

        $.widget(
            'bss.Configurable', {
                _init: function () {
                    var fastorderRow = $('.bss-row-select').val();
                    var productId = this.options.productId;
                    $('.bss-swatch-opt-' + productId).FastOrderSwatch(
                        {
                            jsonConfig: this.options.jsonConfig,
                            jsonSwatchConfig: this.options.jsonSwatchConfig,
                            mediaCallback: this.options.mediaCallback,
                            fastorderRow: fastorderRow,
                            formatPrice: this.options.formatPrice,
                        }
                    );
                    if(localStorage.getItem('sortOrderNew') != null && localStorage.getItem(localStorage.getItem('sortOrderNew')) != null) {
                        var dataNew = JSON.parse(localStorage.getItem(localStorage.getItem('sortOrderNew')));
                        dataNew.forEach(
                            function (element,key) {
                                $('.bss-swatch-option').each(
                                    function () {
                                        if($(this).attr('bss-option-id') == element) {
                                            $(this).click();
                                            $(this).closest('.bss-swatch-attribute').attr('bss-option-selected',element);
                                            $(this).closest('.bss-swatch-attribute').children('.bss-attribute-select').val(element);
                                            $(this).closest('.bss-swatch-attribute').children('.bss-swatch-attribute-selected-option').html($(this).attr('bss-option-label'));
                                        }
                                        else if(element.startsWith('bss-options') == true || element.startsWith('options[') == true || element.startsWith('bss_fastorder_links[') == true)
                                        {
                                            var subValue = element.split("+");
                                            if(($('[name = "'+String(subValue[0])+'"]').attr('type') == "radio")){
                                                $('[name = "'+String(subValue[0])+'"]').each(function(){
                                                    if($(this).val() == subValue[1])
                                                    {
                                                        $(this).attr('checked','checked');
                                                    }
                                                });
                                            }
                                            else if($('[name = "'+String(subValue[0])+'"]').attr('type') == "checkbox" || $('[name = "'+String(subValue[0])+'"]').attr('multiple') == "multiple")
                                            {
                                                $('[name = "'+String(subValue[0])+'"]').each(function(){
                                                    var value = subValue[1].split(',');
                                                    var checkbox = this;
                                                    if($('[name = "'+String(subValue[0])+'"]').attr('multiple') == "multiple")
                                                    {
                                                        $(checkbox).val(value);
                                                    }
                                                    else
                                                    {
                                                        value.forEach(function(element,key){
                                                            if($(checkbox).val() == Number(element))
                                                            {
                                                                $(checkbox).attr('checked','checked');
                                                            }
                                                        }); 
                                                    }                                                             
                                                });
                                            }  
                                            else
                                            {
                                                $('[name = "'+String(subValue[0])+'"]').val(subValue[1]);
                                            } 
                                        }
                                    }
                                );
                        
                        
                            }
                        );
                    }
                }
            }
        );

        return $.bss.Configurable;
    }
);
