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
namespace Bss\FastOrder\Setup;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Class UpgradeData
 *
 * @package Bss\FastOrder\Setup
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @param \Magento\Cms\Model\PageFactory
     */
    private $pageFactory;

    /**
     * @param \Magento\Cms\Model\PageFactory
     * @param \Magento\UrlRewrite\Model\UrlRewriteFactory
     */
    public function __construct(
        \Magento\Cms\Model\PageFactory $pageFactory
    ) {
        $this->pageFactory = $pageFactory;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Exception
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    { 
        $setup->startSetup();
        $checkBlockExists = 0;
        if (version_compare($context->getVersion(), '1.2.5') < 0) {
            $pageCollection = $this->pageFactory->create()->getCollection();
            foreach ($pageCollection as $item) {
                $content = $item->getContent();
                if (strpos($content, 'Bss\FastOrder\Block\FastOrder') !== false) {
                    $checkBlockExists = 1;
                    break;
                }
            }

            if ($checkBlockExists == 0) {
                $cmsPageData = [
                    'title' => __('Fast Order'),
                    'page_layout' => '1column',
                    'meta_keywords' => 'Fast order',
                    'meta_description' => 'Fast order',
                    'identifier' => 'fast-order',
                    'content_heading' => 'Fast order',
                    'content' => '{{block class="Bss\FastOrder\Block\FastOrder" template="Bss_FastOrder::fastorder.phtml"}}',
                    'is_active' => 1,
                    'stores' => [0],
                    'sort_order' => 0
                ];
                $this->pageFactory->create()->addData($cmsPageData)->save();
            }

        }
        $setup->endSetup();
    }
}
