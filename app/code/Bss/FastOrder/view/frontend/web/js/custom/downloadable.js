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
    "bss/fastorder_downloadable"
    ], function ($) {
        'use strict';

        $.widget(
            'bss.customDownloadable', {
                _create: function () {
                    $('#bss-fastorder-downloadable-links-list').fastorder_downloadable(
                        {
                            "bsslinkElement":"input:checkbox[value]",
                            "bssallElements":"#bss-fastorder-bss_fastorder_links_all",
                            "bssconfig":this.options.bssconfig,
                            "sortOrder":this.options.sortOrder,
                            "defaultPrice": this.options.defaultPrice
                        }
                    );
                }
            }
        );

        return $.bss.customDownloadable;
    }
);
