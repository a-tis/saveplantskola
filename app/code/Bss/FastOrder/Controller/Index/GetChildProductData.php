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

namespace Bss\FastOrder\Controller\Index;

use Magento\Framework\Controller\ResultFactory;

/**
 * Class GetChildProductData
 * @package Bss\FastOrder\Controller\Index
 */
class GetChildProductData extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Bss\FastOrder\Helper\ConfigurableProduct
     */
    protected $configurableProductHelper;

    /**
     * GetChildProductData constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Bss\FastOrder\Helper\ConfigurableProduct $configurableProductHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Bss\FastOrder\Helper\ConfigurableProduct $configurableProductHelper
    ) {
    
        parent::__construct($context);
        $this->configurableProductHelper = $configurableProductHelper;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $parentProductId = $this->_request->getParam('parentProductId');
        $childProductListParams = $this->_request->getParam('productList');
        try {
            $data = $this->configurableProductHelper->getMultiChildProductData($parentProductId, $childProductListParams);
        } catch (\Exception $e) {
            $data = [];
        }

        $result = [
            'data' => $data
        ];
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($result);

        return $resultJson;
    }
}
