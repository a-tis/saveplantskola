<?php
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
namespace Bss\FastOrder\Controller\Adminhtml\Widget\Instance;

/**
 * Class Products
 * @package Bss\FastOrder\Controller\Adminhtml\Widget\Instance
 */
class Products extends \Magento\Widget\Controller\Adminhtml\Widget\Instance\Products
{
    /**
     * Products chooser Action (Ajax request)
     *
     * @return void
     */
    public function execute()
    {
        $selected = $this->getRequest()->getParam('selected', '');
        $productTypeId = $this->getRequest()->getParam('product_type_id', '');
        $chooser = $this->_view->getLayout()->createBlock(
            \Bss\FastOrder\Block\Adminhtml\Product\Widget\Chooser::class
        )->setName(
            $this->mathRandom->getUniqueHash('products_grid_')
        )->setUseMassaction(
            true
        )->setProductTypeId(
            $productTypeId
        )->setSelectedProducts(
            explode(',', $selected)
        );
        /* @var $serializer \Magento\Backend\Block\Widget\Grid\Serializer */
        $serializer = $this->_view->getLayout()->createBlock(
            \Bss\FastOrder\Block\Adminhtml\Widget\Grid\Serializer::class,
            '',
            [
                'data' => [
                    'grid_block' => $chooser,
                    'callback' => 'getSelectedProducts',
                    'input_element_name' => 'selected_products',
                    'reload_param_name' => 'selected_products',
                ]
            ]
        );
        $this->setBody($chooser->toHtml() . $serializer->toHtml());
    }
}
