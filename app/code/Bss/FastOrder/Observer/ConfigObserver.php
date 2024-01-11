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

namespace Bss\FastOrder\Observer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Bss\FastOrder\Helper\Data;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class ConfigObserver
 * Filter product id
 */
class ConfigObserver implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $helperBss;
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var WriterInterface
     */
    protected $configWriter;
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * ConfigObserver constructor.
     * @param Data $helperBss
     * @param ProductRepositoryInterface $productRepository
     * @param RequestInterface $request
     * @param WriterInterface $configWriter
     */
    public function __construct(
        Data $helperBss,
        ProductRepositoryInterface $productRepository,
        RequestInterface $request,
        WriterInterface $configWriter
    ) {
        $this->helperBss = $helperBss;
        $this->productRepository = $productRepository;
        $this->request = $request;
        $this->configWriter = $configWriter;
    }

    /**
     * @param Observer $observer
     * @return ConfigObserver
     */
    public function execute(Observer $observer)
    {
        try {
            if ($this->helperBss->getPrepopulateConfig('enabled')) {
                if ($this->helperBss->getPrepopulateConfig('action') == "manual") {
                    $errorMessages = 'Can not add pre-populated products.';
                    $productListId = [];
                    try {
                        $productListId = $this->applyFilter();
                    } catch (NoSuchEntityException $e) {
                        $this->helperBss->getMessage()
                            ->addErrorMessage(__($errorMessages));
                    } catch (LocalizedException $e) {
                        $this->helperBss->getMessage()
                            ->addErrorMessage(__($errorMessages));
                    }
                    $stringId = implode(',', $productListId);
                    $this->configWriter->save('fastorder/prepopulated_product/product_selector', $stringId);
                    return $this;
                }
            }

        } catch (NoSuchEntityException $e) {
            $this->helperBss->getMessage()
                ->addErrorMessage(__('Something went wrong. Please try again later'));
        } catch (LocalizedException $e) {
            $this->helperBss->getMessage()
                ->addErrorMessage(__('Something went wrong. Please try again later'));
        }
        return $this;
    }

    /**
     * @return array|bool|mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function applyFilter(){
        $prePopulatedIds = $this->helperBss->getPrepopulateConfig('product_selector');
        try {
            $prePopulatedIds = explode(',', $prePopulatedIds);
        }catch (\Exception $e){
            $this->helperBss->getMessage()
                ->addErrorMessage(__('pre-populated products can not be null.'));
        }
        foreach ($prePopulatedIds as $key => $checkProductId){
            if (!is_numeric($checkProductId)) {
                $errorMessages = 'Product id "%1" is not valid.';
                $this->helperBss->getMessage()
                    ->addErrorMessage(__($errorMessages, $checkProductId));
                unset($prePopulatedIds[$key]);
                continue;
            }
            $product = $this->productRepository->getById($checkProductId);
            $visibility = $product->getVisibility();
            $status = $product->getStatus();
            $stock = $product->getExtensionAttributes()->getStockItem()->getIsInStock();
            $productType = $product->getTypeId();
            if( $visibility == 1 || $status == 2 || $stock == false || $productType == 'bundle'){
                unset($prePopulatedIds[$key]);
                $errorMessages = 'Can not add id "%1" to pre-populated products.';
                $this->helperBss->getMessage()
                    ->addErrorMessage(__($errorMessages, $checkProductId));
            }
        }
        return $prePopulatedIds;
    }
}