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

namespace Bss\FastOrder\Helper;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Message\ManagerInterface;

/**
 * Class Data
 * @package Bss\FastOrder\Helper
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Redirect to cart config path
     */
    const XML_PATH_REDIRECT_TO_CART = 'checkout/cart/redirect_to_cart';

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $jsonEncoder;

    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    protected $localeFormat;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    /**
     * @var \Magento\Customer\Model\SessionFactory
     */
    protected $customerSession;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var HelperSearchSave
     */
    protected $searchHelper;

    /**
     * @var PreOrder
     */
    protected $bssPreOrder;

    /**
     * @var \Magento\Tax\Helper\Data
     */
    protected $dataTax;

    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $session;
    /**
     * @var ManagerInterface
     */
    protected $messageManager;
    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param \Magento\Customer\Model\SessionFactory $customerSession
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonEncoder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param HelperSearchSave $searchHelper
     * @param PreOrder $bssPreOrder
     * @param \Magento\Tax\Helper\Data $dataTax
     * @param StockRegistryInterface $stockRegistry
     * @param \Magento\Checkout\Model\Session $session
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Customer\Model\SessionFactory $customerSession,
        \Magento\Framework\Serialize\Serializer\Json $jsonEncoder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        HelperSearchSave $searchHelper,
        PreOrder $bssPreOrder,
        \Magento\Tax\Helper\Data $dataTax,
        StockRegistryInterface $stockRegistry,
        \Magento\Checkout\Model\Session $session,
        ManagerInterface $messageManager,
        \Magento\Catalog\Helper\Data $taxHelper
    ) {

        parent::__construct($context);
        $this->localeFormat = $localeFormat;
        $this->imageHelper = $imageHelper;
        $this->priceCurrency = $priceCurrency;
        $this->jsonEncoder = $jsonEncoder;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->searchHelper = $searchHelper;
        $this->bssPreOrder = $bssPreOrder;
        $this->dataTax = $dataTax;
        $this->stockRegistry = $stockRegistry;
        $this->session = $session;
        $this->messageManager = $messageManager;
        $this->taxHelper = $taxHelper;
    }

    /**
     * @param string $config_path
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getConfig($config_path = '')
    {
        if ($this->scopeConfig->getValue(
                'fastorder/general/enabled',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
            && $this->checkCustomer()
        ) {
            return $this->scopeConfig->getValue(
                'fastorder/general/' . $config_path,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
        return false;
    }

    /**
     * @param string $config_path
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getPrepopulateConfig($config_path = '')
    {
        if ($this->scopeConfig->getValue(
                'fastorder/prepopulated_product/enabled',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
            && $this->checkCustomer()
        ) {
            return $this->scopeConfig->getValue(
                'fastorder/prepopulated_product/' . $config_path,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
        return false;
    }

    /**
     * @return \Magento\Framework\Serialize\Serializer\Json
     */
    public function getJson()
    {
        return $this->jsonEncoder;
    }

    /**
     * @return string
     */
    public function getFormatPrice()
    {
        $config = $this->localeFormat->getPriceFormat();
        return $this->jsonEncoder->serialize($config);
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function checkCustomer()
    {
        $customerConfig = $this->scopeConfig->getValue(
            'fastorder/general/active_customer_groups',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if ($customerConfig != '') {
            $customerConfigArr = explode(',', $customerConfig);
            $customerSession = $this->getSession();
            if ($customerSession->isLoggedIn()) {
                $customerId = $customerSession->getId();
                $customerGroupId = $this->searchHelper->getCustomerRepositoryInterface()->getById($customerId)->getGroupId();
                if (in_array($customerGroupId, $customerConfigArr)) {
                    return true;
                }
            } else {
                if (in_array(0, $customerConfigArr)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @return \Magento\Customer\Model\Session
     */
    public function getSession()
    {
        return $this->customerSession->create();
    }

    /**
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getUrlShortcut()
    {
        if ($this->getConfig('cms_url_key')) {
            return $this->getConfig('cms_url_key');
        }
        return false;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getJsonConfigPrice($product)
    {
        $dataObject = $this->searchHelper->getDataObject();
        if (!$product->hasOptions()) {
            $config = [
                'productId' => $product->getId(),
                'priceFormat' => $this->localeFormat->getPriceFormat()
            ];
            return $this->jsonEncoder->serialize($config);
        }

        $tierPrices = [];
        $tierPricesList = $product->getPriceInfo()->getPrice('tier_price')->getTierPriceList();
        foreach ($tierPricesList as $tierPrice) {
            $tierPrices[] = $this->priceCurrency->convert($tierPrice['price']->getValue());
        }
        $finalPriceModel = $product->getPriceInfo()->getPrice('final_price');

        $config = [
            'productId' => $product->getId(),
            'priceFormat' => $this->localeFormat->getPriceFormat(),
            'prices' => [
                'oldPrice' => [
                    'amount' => $this->priceCurrency->convert(
                        $product->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue()
                    ),
                    'adjustments' => []
                ],
                'basePrice' => [
                    'amount' => $finalPriceModel->getAmount()->getBaseAmount(),
                    'adjustments' => []
                ],
                'finalPrice' => [
                    'amount' => $finalPriceModel->getAmount()->getValue(),
                    'adjustments' => []
                ]
            ],
            'idSuffix' => '_clone',
            'tierPrices' => $tierPrices
        ];

        $this->_eventManager->dispatch('catalog_product_view_config', ['response_object' => $dataObject]);
        $additionalOptions = $dataObject->getAdditionalOptions();
        if (is_array($additionalOptions)) {
            foreach ($additionalOptions as $option => $value) {
                $config[$option] = $value;
            }
        }

        return $this->jsonEncoder->serialize($config);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return bool|string
     */
    public function getProductImage($product)
    {
        $productImage = $this->imageHelper->init(
            $product,
            'category_page_list'
        )->getUrl();
        if (!$productImage) {
            return false;
        }
        return $productImage;
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * @return bool|mixed
     */
    public function getRedirectToCart()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_REDIRECT_TO_CART,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return \Magento\Framework\Event\ManagerInterface
     */
    public function getEventManager()
    {
        return $this->_eventManager;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getUrlCheckout()
    {
        return $this->storeManager->getStore()->getBaseUrl() . "checkout";
    }

    /**
     * @return int
     */
    public function isPreOrder()
    {
        $isPreOrder = 0;

        if ($this->bssPreOrder->isEnable()) {
            $preOrder = $this->bssPreOrder->isPreOrder();
            $inStock = $this->bssPreOrder->isInStock();
            if ($preOrder == 1 || ($preOrder == 2 && $inStock == 0)) {
                $isPreOrder = 1;
            }
        }
        return $isPreOrder;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return array|bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getDataTierPrice(\Magento\Catalog\Model\Product $product)
    {
        if ($product->getBssHidePrice()) {
            return [];
        }
        $finalPriceModel = $product->getPriceInfo()->getPrice('final_price');
        $storeId = $this->getStoreId();
        if ($product->getTypeId() == 'configurable') {
            $productTypeInstance = $product->getTypeInstance();
            $productTypeInstance->setStoreFilter($storeId, $product);
            $usedProducts = $productTypeInstance->getUsedProducts($product);
            $tierPrices = $this->getChildProductTierPrice($usedProducts);
        } else {
            $tierPrices = [];
            if ($this->isDisplayBothPrices()) {
                $tierPrices[1]['final_price'] = $finalPriceModel->getAmount()->getValue();
                $tierPrices[1]['base_price'] = $finalPriceModel->getAmount()->getBaseAmount();
            } elseif ($this->isDisplayPriceIncludingTax()) {
                $tierPrices[1]['final_price'] = $this->taxHelper->getTaxPrice($product, $product->getFinalPrice(), true);
            } else {
                $tierPrices[1]['final_price'] = $product->getFinalPrice();
            }
            $tierPricesList = $product->getPriceInfo()->getPrice('tier_price')->getTierPriceList();
            if (!empty($tierPricesList)) {
                foreach ($tierPricesList as $tierPrice) {
                    $tierPriceQty = $tierPrice['price_qty'];
                    $tierPrices[$tierPriceQty]['final_price'] = $tierPrice['price']->getValue();
                    if ($this->isDisplayBothPrices()) {
                        $tierPrices[$tierPriceQty]['base_price'] = $tierPrice['website_price'];
                    }
                }
            }
        }
        return $tierPrices;
    }

    /**
     * @return bool
     */
    public function isDisplayBothPrices() {
        return $this->dataTax->displayBothPrices();
    }

    /**
     * @return bool
     */
    public function isDisplayPriceIncludingTax() {
        return $this->dataTax->displayPriceIncludingTax();
    }

    /**
     * @param null $usedProducts
     * @return array|bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getChildProductTierPrice($usedProducts = null)
    {
        if (empty($usedProducts)) {
            return false;
        }
        $childrenList = [];
        foreach ($usedProducts as $child) {
            $tierPrices = [];
            if ($this->isDisplayBothPrices()) {
                $tierPrices[1]['final_price'] = $child->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
                $tierPrices[1]['base_price'] = $child->getPriceInfo()->getPrice('final_price')
                    ->getAmount()->getBaseAmount();
            } elseif ($this->isDisplayPriceIncludingTax()) {
                $tierPrices[1]['final_price'] = $this->taxHelper->getTaxPrice($child, $child->getFinalPrice(), true);
            } else {
                $tierPrices[1]['final_price'] = $child->getFinalPrice();
            }
            $isSaleable = $child->isSaleable();
            if ($isSaleable) {
                $tierPricesList = $child->getPriceInfo()->getPrice('tier_price')->getTierPriceList();
                if (!empty($tierPricesList)) {
                    foreach ($tierPricesList as $tierPrice) {
                        //$tierPriceQty = $this->getTierPriceQty($tierPrice['price_qty'], $child->getId());
                        $tierPrices[$tierPrice['price_qty']]['final_price'] = $tierPrice['price']->getValue();
                        if ($this->isDisplayBothPrices()) {
                            $tierPrices[$tierPrice['price_qty']]['base_price'] = $tierPrice['website_price'];
                        }
                    }
                }
            }
            $childrenList['tier_price_child_' . $child->getId()] = $tierPrices;
        }
        return $childrenList;
    }

    /**
     * @param float $tierPriceQty
     * @param int $productId
     * @return float|int|mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getTierPriceQty($tierPriceQty, $productId)
    {
        $quote = $this->session->getQuote();
        foreach ($quote->getAllVisibleItems() as $item) {
            $productQuote = $this->searchHelper->getProductRepositoryInterface()->get($item->getSku());
            if ($productQuote->getId() == $productId) {
                $tierPriceQty = $tierPriceQty - $item->getQty();
                if ($tierPriceQty < 1) {
                    $tierPriceQty = 1;
                }
            }
        }
        return $tierPriceQty;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\CatalogInventory\Api\Data\StockItemInterface
     */
    public function getStockItem($product)
    {
        return $this->stockRegistry->getStockItem(
            $product->getId(),
            $product->getStore()->getWebsiteId()
        );
    }

    /**
     * @param array $params
     * @param \Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem
     * @param \Magento\Catalog\Model\Product $product
     */
    public function addDataParams(&$params, $stockItem, $product)
    {
        if ($stockItem->getQtyMaxAllowed()) {
            $params['maxAllowed'] = $stockItem->getQtyMaxAllowed();
        }
        if ($stockItem->getQtyIncrements() > 0 && $product->getTypeId() != 'grouped') {
            $params['qtyIncrements'] = (float)$stockItem->getQtyIncrements();
        }
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param string $productPriceHtml
     * @param string $productPrice
     */
    public function getPriceHtml($product, &$productPriceHtml, &$productPrice)
    {
        if ($product->getBssHidePrice()) {
            $productPriceHtml = $product->getBssHidePriceHtml();
        } else {
            $productPrice = $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
            $productPriceHtml = $this->searchHelper->getPriceCurrency()->format($productPrice, false);
        }
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param string $productPriceExcTaxHtml
     * @param string $productPriceExcTax
     */
    public function getTaxHtml($product, &$productPriceExcTaxHtml, &$productPriceExcTax)
    {
        if ($this->isDisplayBothPrices() && !$product->getBssHidePrice()) {
            $finalPriceModel = $product->getPriceInfo()->getPrice('final_price');
            $productPriceExcTaxHtml = $this->searchHelper->getPriceCurrency()->format(
                $finalPriceModel->getAmount()->getValue(\Magento\Tax\Pricing\Adjustment::ADJUSTMENT_CODE),
                false
            );
            $productPriceExcTax =
                $finalPriceModel->getAmount()->getValue(\Magento\Tax\Pricing\Adjustment::ADJUSTMENT_CODE);
        }
    }
    /**
     * GetMessage
     *
     * @return ManagerInterface
     */
    public function getMessage()
    {
        return $this->messageManager;
    }
}
