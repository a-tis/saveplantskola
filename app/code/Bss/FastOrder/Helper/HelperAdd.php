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

/**
 * Class HelperAdd
 * @package Bss\FastOrder\Helper
 */
class HelperAdd
{
    /**
     * @var Data
     */
    protected $helperBss;

    /**
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\Checkout\Helper\Cart
     */
    protected $helperCart;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $resolverInterface;

    /**
     * HelperAdd constructor.
     *
     * @param \Magento\Framework\Escaper                  $escaper
     * @param \Psr\Log\LoggerInterface                    $logger
     * @param Data                                        $helperBss
     * @param \Magento\Catalog\Model\ProductFactory       $productFactory
     * @param \Magento\Framework\Registry                 $registry
     * @param \Magento\Framework\Locale\ResolverInterface $resolverInterface
     * @param \Magento\Checkout\Helper\Cart               $helperCart
     */
    public function __construct(
        \Magento\Framework\Escaper $escaper,
        \Psr\Log\LoggerInterface $logger,
        Data $helperBss,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Locale\ResolverInterface $resolverInterface,
        \Magento\Checkout\Helper\Cart $helperCart
    ) {
        $this->productFactory = $productFactory;
        $this->registry = $registry;
        $this->escaper = $escaper;
        $this->logger = $logger;
        $this->helperBss = $helperBss;
        $this->resolverInterface = $resolverInterface;
        $this->helperCart = $helperCart;
    }

    /**
     * @return \Magento\Catalog\Model\ProductFactory
     */
    public function getProductFactory()
    {
        return $this->productFactory;
    }

    /**
     * @return \Magento\Framework\Registry
     */
    public function getRegistry()
    {
        return $this->registry;
    }

    /**
     * @return \Magento\Framework\Escaper
     */
    public function getEscaper()
    {
        return $this->escaper;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return Data
     */
    public function getHelperBss()
    {
        return $this->helperBss;
    }

    /**
     * @return \Magento\Framework\Locale\ResolverInterface
     */
    public function getResolverInterface()
    {
        return $this->resolverInterface;
    }

    /**
     * @return \Magento\Checkout\Helper\Cart
     */
    public function getHelperCart()
    {
        return $this->helperCart;
    }
}
