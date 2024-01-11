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
namespace Bss\FastOrder\Block\Product\Renderer;

use Magento\Swatches\Block\Product\Renderer\Configurable as SwatchesConfigurable;
use Magento\Catalog\Block\Product\Context;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Framework\Json\EncoderInterface;
use Magento\ConfigurableProduct\Helper\Data;
use Magento\Catalog\Helper\Product as CatalogProduct;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;
use Magento\Swatches\Helper\Data as SwatchData;
use Magento\Swatches\Helper\Media;
use Magento\Swatches\Model\SwatchAttributesProvider;

/**
 * Class Configurable
 * @package Bss\FastOrder\Block\Product\Renderer
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Configurable extends SwatchesConfigurable
{
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    public $json;

    /**
     * @var \Bss\FastOrder\Helper\PreOrder
     */
    private $bssPreOrder;

    /**
     * Path to template file with Swatch renderer for fastorder module.
     */
    const FASTORDER_RENDERER_TEMPLATE = 'Bss_FastOrder::configurable.phtml';

    /**
     * Configurable constructor.
     * @param Context $context
     * @param ArrayUtils $arrayUtils
     * @param EncoderInterface $jsonEncoder
     * @param Data $helper
     * @param CatalogProduct $catalogProduct
     * @param CurrentCustomer $currentCustomer
     * @param PriceCurrencyInterface $priceCurrency
     * @param ConfigurableAttributeData $configurableAttributeData
     * @param SwatchData $swatchHelper
     * @param Media $swatchMediaHelper
     * @param \Bss\FastOrder\Helper\PreOrder $bssPreOrder
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     * @param array $data
     * @param SwatchAttributesProvider|null $swatchAttributesProvider
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        ArrayUtils $arrayUtils,
        EncoderInterface $jsonEncoder,
        Data $helper,
        CatalogProduct $catalogProduct,
        CurrentCustomer $currentCustomer,
        PriceCurrencyInterface $priceCurrency,
        ConfigurableAttributeData $configurableAttributeData,
        SwatchData $swatchHelper,
        Media $swatchMediaHelper,
        \Bss\FastOrder\Helper\PreOrder $bssPreOrder,
        \Magento\Framework\Serialize\Serializer\Json $json,
        array $data = [],
        SwatchAttributesProvider $swatchAttributesProvider = null
    ) {
        parent::__construct(
            $context,
            $arrayUtils,
            $jsonEncoder,
            $helper,
            $catalogProduct,
            $currentCustomer,
            $priceCurrency,
            $configurableAttributeData,
            $swatchHelper,
            $swatchMediaHelper,
            $data,
            $swatchAttributesProvider
        );
        $this->bssPreOrder = $bssPreOrder;
        $this->json = $json;
    }
    /**
     * Return renderer template
     *
     * @return string
     */
    protected function getRendererTemplate()
    {
        return self::FASTORDER_RENDERER_TEMPLATE;
    }

    /**
     * @return bool|string
     */
    public function getJsonChildProductData()
    {
        if ($this->bssPreOrder->isEnable()) {
            return $this->json->serialize(
                $this->bssPreOrder->getAllData(
                    $this->getProduct()->getId()
                )
            );
        }
        return false;
    }
}
