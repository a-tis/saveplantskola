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
 * @category  BSS
 * @package   Bss_FastOrder
 * @author    Extension Team
 * @copyright Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license   http://bsscommerce.com/Bss-Commerce-License.txt
 */

namespace Bss\FastOrder\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class Search
 *
 * @package Bss\FastOrder\Controller\Index
 */
class Search extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Bss\FastOrder\Helper\Data
     */
    protected $helperBss;

    /**
     * @var \Bss\FastOrder\Model\Search\ProductList
     */
    protected $productList;

    /**
     * Search constructor.
     *
     * @param Context                                 $context
     * @param \Bss\FastOrder\Helper\Data              $helperBss
     * @param \Bss\FastOrder\Model\Search\ProductList $productList
     */
    public function __construct(
        Context $context,
        \Bss\FastOrder\Helper\Data $helperBss,
        \Bss\FastOrder\Model\Search\ProductList $productList
    ) {
        parent::__construct($context);
        $this->helperBss = $helperBss;
        $this->productList = $productList;
    }

    /**
     * @return bool|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        if (!$this->helperBss->getConfig('enabled')) {
            return false;
        }
        $queryText = $this->getRequest()->getParam('q');
        $responseData = [];

        if ($queryText) {
            $responseData = $this->productList->getSearchResult($queryText);
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($responseData);

        return $resultJson;
    }
}
