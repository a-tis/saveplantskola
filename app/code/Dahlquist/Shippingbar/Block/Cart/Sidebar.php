<?php

namespace Dahlquist\Shippingbar\Block\Cart;

use Magento\Framework\View\Element\Template;

class Sidebar extends Template
{
    /**
     * @var \Dahlquist\Jobs\Helper\Data
     */
    private $helper;
    
    /**
     * Sidebar constructor.
     * @param Template\Context $context
     * @param \Dahlquist\Jobs\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Dahlquist\Shippingbar\Helper\Data $helper,
        array $data = []
    )
    {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }
    
    public function getConfigForShippingBar()
    {
        return $this->helper->getPriceForShippingBar();
    }
}