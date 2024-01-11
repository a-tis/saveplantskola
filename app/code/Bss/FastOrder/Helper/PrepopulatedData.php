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

namespace Bss\FastOrder\Helper;

use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

/**
 * Class PrepopulatedData
 * @package Bss\FastOrder\Helper
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PrepopulatedData extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\Module\Manager
     */
    private $helperBss;

    /**
     * @var ConfigurableProduct
     */
    private $configurableProductHelper;

    /**
     * @var \Bss\FastOrder\Helper\HelperSearchSave
     */
    protected $helperSave;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Report\Bestsellers\CollectionFactory
     */
    protected $resourceCollection;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * Prepopulated constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param Data $helperBss
     * @param ConfigurableProduct $configurableProductHelper
     * @param HelperSearchSave $helperSave
     * @param \Magento\Sales\Model\ResourceModel\Report\Bestsellers\CollectionFactory $resourceCollection
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        Data $helperBss,
        ConfigurableProduct $configurableProductHelper,
        HelperSearchSave $helperSave,
        \Magento\Sales\Model\ResourceModel\Report\Bestsellers\CollectionFactory $resourceCollection,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
    ) {
    
        parent::__construct($context);
        $this->helperBss = $helperBss;
        $this->configurableProductHelper = $configurableProductHelper;
        $this->helperSave = $helperSave;
        $this->resourceCollection = $resourceCollection;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    /**
     * @return false|string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getPrepopulated()
    {
//        $responseData = [];
//        $responseData['childProduct'] = "";
//        $responseData['otherProduct'] = "";
        if (!$this->helperBss->getPrepopulateConfig('enabled')) {
            return [];
        }
        if ($this->helperBss->getPrepopulateConfig('action') == "automation") {
            return $this->getProductAuto();
        } else {
            return $this->getProductManual();
        }
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getProductManual()
    {
        $prepopulatedIds = $this->helperBss->getPrepopulateConfig('product_selector');
        $prepopulatedIds = explode(',', $prepopulatedIds);
//        $productConfigurable = [];
//        $productOther = [];
        $responseData = [];
        foreach ($prepopulatedIds as $pid) {
            if (!is_numeric($pid)) {
                continue;
            }

            try {
                $product = $this->helperSave->getProductRepositoryInterface()->getById($pid);
//                $parentId = $this->configurableProductHelper->getParentProductId($product->getId());
//                if ($parentId) {
//                    $productConfigurable[$parentId][] = $product->getSku();
//                } else {
                $responseData[] = $product->getSku();
//                }
            } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
                $this->_logger->warning('Invalid product Id: '.$pid);
            }
        }

//        $responseData['childProduct'] = $productConfigurable;
//        $responseData['otherProduct'] = $productOther;

        return $responseData;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getProductAuto()
    {
        $toDate = date("Y-m-d");
        $date = date_create(date('Y-m-d'));
        date_sub($date, date_interval_create_from_date_string('90 days'));
        $fromDate = date_format($date, "Y-m-d");
        $responseData = [];
//        $responseData['otherProduct'] = [];
        if (!$this->helperBss->getSession()->isLoggedIn()) {
            $data = $this->getItemsCustomerNotLogged($fromDate, $toDate);
        } else {
            $data = $this->getItemsCustomerLogged($fromDate, $toDate);
        }
        foreach ($data as $item) {
            $responseData[] = $item->getSku();
        }
//        $responseData['childProduct'] = null;

        return $responseData;
    }

    /**
     * @param mixed $fromDate
     * @param mixed $toDate
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getItemsCustomerNotLogged($fromDate, $toDate)
    {
        $numberItem = $this->getMaxResShow();
        $storeId = $this->helperBss->getStoreId();
        $items = $this->resourceCollection->create()->setModel(
            \Magento\Catalog\Model\Product::class
        )->addStoreFilter($storeId)
            ->setPeriod('year')
            ->setDateRange($fromDate, $toDate);
        $ids = [];
        foreach ($items as $item) {
            $productStock = $this->helperBss->getStockItem($item);
            if ($productStock) {
                $idConfigurable = $this->configurableProductHelper->getParentProductId($item->getProductId());
                if (empty($idConfigurable)) {
                    $ids[] = $item->getProductId();
                } else {
                    $ids[] = $idConfigurable;
                }
            }
        }
        $ids = array_unique($ids);
        $ids = array_values($ids);
        $ids = array_slice($ids, 0, $numberItem);
        $responseData = $this->getCollectionFilterId($ids);
        return $responseData;
    }

    /**
     * @param mixed $fromDate
     * @param mixed $toDate
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getItemsCustomerLogged($fromDate, $toDate)
    {
        $numberItem = $this->getMaxResShow();
        $finalList = [];
        $customerId = $this->helperBss->getSession()->getCustomerId();
        $orderCollection = $this->orderCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter(
                'status',
                ['in' => \Magento\Sales\Model\Order::STATE_COMPLETE]
            )
            ->addAttributeToFilter('created_at', ['from' => $fromDate])
            ->setOrder('created_at', 'desc');
        if ($orderCollection->getSize() == 0) {
            return $this->getItemsCustomerNotLogged($fromDate, $toDate);
        }

        $list = $this->getOrderedProductData($orderCollection);
        $this->sortList($list, 'sku');

        $itemCount = count($list);
        for ($i = 0; $i < $itemCount - 1; $i++) {
            if ($list[$i]['sku'] == $list[$i + 1]['sku']) {
                $list[$i]['qty'] += $list[$i + 1]['qty'];
            }
        }

        $this->sortList($list, 'qty');

        if (!empty($list)) {
            foreach ($list as $item) {
                $idConfigurable = $this->configurableProductHelper->getParentProductId($item['id']);
                if (empty($idConfigurable)) {
                    $finalList[] = $item['id'];
                } else {
                    $finalList[] = $idConfigurable;
                }
            }
        }
        $finalList = array_unique($finalList);
        $finalList = array_slice($finalList, 0, $numberItem);
        $responseData = $this->getCollectionFilterId($finalList);

        return $responseData;
    }

    /**
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection
     * @return array
     */
    protected function getOrderedProductData($orderCollection)
    {
        $result = [];

        foreach ($orderCollection as $order) {
            $items = $order->getAllItems();
            foreach ($items as $item) {
                if ($item->getProductType() !=  ProductType::TYPE_BUNDLE &&
                    $item->getProductType() != Configurable::TYPE_CODE &&
                    $item->getProductType() != Grouped::TYPE_CODE) {
                    $result[] = [
                        'sku' => $item->getSku(),
                        'type' => $item->getProductType(),
                        'qty' => $item->getQtyOrdered(),
                        'id' => $item->getProductId()
                    ];
                }
            }
        }
        return $result;
    }

    /**
     * @return bool|int|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getMaxResShow()
    {
        $maxRes = ($this->helperBss->getPrepopulateConfig('max_results_show') > 0) ?
            $this->helperBss->getPrepopulateConfig('max_results_show') : 5;
        return $maxRes;
    }

    /**
     * Return collection product filter by list id
     *
     * @param array $arrayIds
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function getCollectionFilterId($arrayIds)
    {
        $collection = $this->helperSave->getProductCollectionFactory()->create();
        $collection
            ->addAttributeToSelect('*')
            ->addIdFilter($arrayIds)
            ->addFieldToFilter('status', Status::STATUS_ENABLED)
            ->addAttributeToFilter('type_id', ['neq' => ProductType::TYPE_BUNDLE])
            ->setVisibility($this->helperSave->getProductVisibility()->getVisibleInSiteIds());
        return $collection;
    }


    /**
     * Return sorted array
     *
     * @param array $array
     * @param string $orderBy
     * @return mixed
     */
    protected function sortList(&$array, $orderBy)
    {
        $sortArray = [];
        foreach ($array as $element) {
            foreach ($element as $key => $value) {
                if (!isset($sortArray[$key])) {
                    $sortArray[$key] = [];
                }
                $sortArray[$key][] = $value;
            }
        }

        if (isset($sortArray[$orderBy])) {
            array_multisort($sortArray[$orderBy], SORT_DESC, $array);
        }
    }
}
