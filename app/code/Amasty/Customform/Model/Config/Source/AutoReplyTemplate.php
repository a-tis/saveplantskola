<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Customform
 */


namespace Amasty\Customform\Model\Config\Source;

class AutoReplyTemplate extends \Magento\Framework\DataObject implements \Magento\Framework\Option\ArrayInterface
{
    const CODE = 'amasty_customform_autoresponder_template';

    /**
     * @var \Magento\Email\Model\Template\Config
     */
    private $emailConfig;

    /**
     * @var \Magento\Email\Model\ResourceModel\Template\CollectionFactory
     */
    private $templatesFactory;

    public function __construct(
        \Magento\Email\Model\ResourceModel\Template\CollectionFactory $templatesFactory,
        \Magento\Email\Model\Template\Config $emailConfig,
        array $data = []
    ) {
        parent::__construct($data);
        $this->templatesFactory = $templatesFactory;
        $this->emailConfig = $emailConfig;
    }

    /**
     * Generate list of email templates
     *
     * @return array
     */
    public function toOptionArray()
    {
        /** @var $collection \Magento\Email\Model\ResourceModel\Template\Collection */
        $collection = $this->templatesFactory->create()
            ->addFieldToFilter('orig_template_code', ['eq' => self::CODE])
            ->load();

        $options = $collection->toOptionArray();
        array_unshift($options, $this->getDefaultTemplate());

        return $options;
    }

    /**
     * @return array
     */
    private function getDefaultTemplate()
    {
        $templateLabel = $this->emailConfig->getTemplateLabel(self::CODE);
        $templateLabel = __('%1 (Default)', $templateLabel);

        return ['value' => self::CODE, 'label' => $templateLabel];
    }
}
