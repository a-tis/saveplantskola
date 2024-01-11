<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Customform
 */


namespace Amasty\Customform\Plugin\Data\Form\FormKey;

use Magento\Framework\Exception\LocalizedException;

class Validator
{
    const FULL_ACTION_NAME = 'amasty_customform_form_submit';

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    public function __construct(\Magento\Framework\Message\ManagerInterface $messageManager)
    {
        $this->messageManager = $messageManager;
    }

    /**
     * Change CSRF validation logic if post data larger than in php.ini
     *
     * @param \Magento\Framework\Data\Form\FormKey\Validator $subject
     * @param $result
     * @param \Magento\Framework\App\Request\Http $request
     * @return bool
     */
    public function afterValidate(
        \Magento\Framework\Data\Form\FormKey\Validator $subject,
        $result,
        \Magento\Framework\App\Request\Http $request = null
    ) {
        if ($request && $request->getFullActionName() == self::FULL_ACTION_NAME && empty($request->getParams())) {
            $result = true;
            $this->messageManager->addErrorMessage(
                __('Can\'t submit form. Post data is too large.')
            );
        }

        return $result;
    }
}
