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
namespace Bss\FastOrder\Block\Product\View\Options\Type;

use Magento\Catalog\Block\Product\View\Options\AbstractOptions;
use Magento\Catalog\Model\Product\Option;
use Magento\Framework\View\Element\Html\Select as FrameworkSelect;

/**
 * Class Select
 * @package Bss\FastOrder\Block\Product\View\Options\Type
 */
class Select extends AbstractOptions
{
    /**
     * @param  \Magento\Catalog\Model\Product\Option $option
     * @param  string $selectHtml
     * @param  int $sortOrder
     * @param  string $class
     * @return string
     */
    protected function addRequiredHtml($option, $selectHtml, $sortOrder, $class)
    {
        if (!$option->getIsRequire()) {
            $selectHtml .= '<li class="field choice admin__field admin__field-option">' .
                '<input type="radio" id="bss-options_' .
                $option->getId() .
                '" class="' .
                $class .
                ' product-custom-option" name="bss-options[' .
                $option->getId() .
                ']"' .
                ' data-selector="options[' . $option->getId() . ']"' .
                ($this->getSkipJsReloadPrice() ? '' : ' onclick="opConfig.reloadPrice()"') .
                ' value="" checked="checked" />
                            <input type="hidden" name="bss-fastorder-options[' . $sortOrder . '][' . $option->getId() . ']"
                            class="bss-customoption-select" value="" />
                            <label class="label admin__field-label" for="bss-options_' .
                $option->getId() .
                '"><span>' .
                __('None') . '</span></label></li>';
        };
        return $selectHtml;
    }

    /**
     * Get Checked
     *
     * @param  string $arraySign
     * @param  string $htmlValue
     * @param  array $configValue
     * @return string
     */
    protected function getChecked($arraySign, $htmlValue, $configValue)
    {
        if ($arraySign) {
            return is_array($configValue) && in_array($htmlValue, $configValue) ? 'checked' : '';
        } else {
            return $configValue == $htmlValue ? 'checked' : '';
        }
    }

    /**
     * @param string $arraySign
     * @param string $dataSelector
     * @param string $htmlValue
     */
    protected function getDataSelector($arraySign, &$dataSelector, $htmlValue)
    {
        if ($arraySign) {
            $dataSelector .= '[' . $htmlValue . ']';
        }
    }

    /**
     * @param \Magento\Catalog\Model\Product\Option $_option
     * @param string $arraySign
     * @param array $configValue
     * @param string $selectHtml
     * @param string $require
     * @param string $type
     * @param string $class
     * @param int|\Magento\Store\Model\Store $store
     * @param string $name
     * @return string
     */
    protected function renderHtml(
        $_option,
        $arraySign,
        $configValue,
        $selectHtml,
        $require,
        $type,
        $class,
        $store,
        $name
    ) {
        $count = 1;
        foreach ($_option->getValues() as $_value) {
            $count++;

            $priceStr = $this->_formatPrice(
                [
                    'is_percent' => $_value->getPriceType() == 'percent',
                    'pricing_value' => $_value->getPrice($_value->getPriceType() == 'percent'),
                ]
            );

            $htmlValue = $_value->getOptionTypeId();
            $checked = $this->getChecked($arraySign, $htmlValue, $configValue);
            $dataSelector = 'options[' . $_option->getId() . ']';
            $this->getDataSelector($arraySign, $dataSelector, $htmlValue);
            $selectHtml .= '<li class="field choice admin__field admin__field-option' .
                $require .
                '">' .
                '<input type="' .
                $type .
                '" class="' .
                $class .
                ' ' .
                $require .
                ' product-custom-option"' .
                ($this->getSkipJsReloadPrice() ? '' : ' onclick="opConfig.reloadPrice()"') .
                ' name="bss-options[' .
                $_option->getId() .
                ']' .
                $arraySign .
                '" id="bss-options_' .
                $_option->getId() .
                '_' .
                $count .
                '" value="' .
                $htmlValue .
                '" ' .
                $checked .
                ' data-selector="' . $dataSelector . '"' .
                ' price="' .
                $this->pricingHelper->currencyByStore($_value->getPrice(true), $store, false) .
                '" />' .
                '<input type="hidden" name="'.$name.'" class="bss-customoption-select" value="'.$htmlValue.'" />
                    <label class="label admin__field-label" for="bss-options_' .
                $_option->getId() .
                '_' .
                $count .
                '"><span>' .
                $_value->getTitle() .
                '</span> ' .
                $priceStr .
                '</label>';
            $selectHtml .= '</li>';
        }
        return $selectHtml;
    }

    /**
     * Get Values Html
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.RequestAwareBlockMethod)
     */
    public function getValuesHtml()
    {
        $sortOrder = $this->getRequest()->getParam('sortOrder');
        $_option = $this->getOption();
        $configValue = $this->getProduct()->getPreconfiguredValues()->getData('options/' . $_option->getId());
        $store = $this->getProduct()->getStore();
        $this->setSkipJsReloadPrice(1);
        // Remove inline prototype onclick and onchange events
        if ($_option->getType() == Option::OPTION_TYPE_DROP_DOWN
            || $_option->getType() == Option::OPTION_TYPE_MULTIPLE
        ) {
            return $this->getTypeMultiple($_option, $store, $configValue, $sortOrder);
        }

        if ($_option->getType() == Option::OPTION_TYPE_RADIO
            || $_option->getType() == Option::OPTION_TYPE_CHECKBOX
        ) {
            $selectHtml = '<ul class="options-list nested" id="bss-options-' . $_option->getId() . '-list">';
            $require = $_option->getIsRequire() ? ' required' : '';
            $arraySign = '';
            switch ($_option->getType()) {
                case Option::OPTION_TYPE_RADIO:
                    $type = 'radio';
                    $class = 'radio admin__control-radio';
                    $selectHtml = $this->addRequiredHtml($_option, $selectHtml, $sortOrder, $class);
                    $name = 'bss-fastorder-options['.$sortOrder.']['.$_option->getId().']';
                    break;
                case Option::OPTION_TYPE_CHECKBOX:
                    $type = 'checkbox';
                    $class = 'checkbox admin__control-checkbox';
                    $arraySign = '[]';
                    $name = 'bss-fastorder-options['.$sortOrder.']['.$_option->getId().'][]';
                    break;
            }
            $selectHtml = $this->renderHtml(
                $_option,
                $arraySign,
                $configValue,
                $selectHtml,
                $require,
                $type,
                $class,
                $store,
                $name
            );
            $selectHtml .= '</ul>';

            return $selectHtml;
        }
    }

    /**
     * Get Type Multiple
     *
     * @param  \Magento\Catalog\Model\Product\Option $_option
     * @param  int|\Magento\Store\Model\Store|null $store $store
     * @param  string|null $configValue
     * @param  int|null $sortOrder
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getTypeMultiple($_option = null, $store = null, $configValue = null, $sortOrder = null)
    {
        $require = $_option->getIsRequire() ? ' required' : '';
        $extraParams = '';
        $select = $this->getLayout()->createBlock(
            FrameworkSelect::class
        )->setData(
            [
                'id' => 'bss-select_' . $_option->getId(),
                'class' => $require . ' product-custom-option admin__control-select'
            ]
        );
        if ($_option->getType() == Option::OPTION_TYPE_DROP_DOWN) {
            $select->setName('bss-options[' . $_option->getid() . ']')->addOption('', __('-- Please Select --'));
        } else {
            $select->setName('bss-options[' . $_option->getid() . '][]');
            $select->setClass('multiselect admin__control-multiselect' . $require . ' product-custom-option');
        }
        foreach ($_option->getValues() as $_value) {
            $priceStr = $this->_formatPrice(
                [
                    'is_percent' => $_value->getPriceType() == 'percent',
                    'pricing_value' => $_value->getPrice($_value->getPriceType() == 'percent'),
                ],
                false
            );
            $select->addOption(
                $_value->getOptionTypeId(),
                $_value->getTitle() . ' ' . strip_tags($priceStr) . '',
                ['price' => $this->pricingHelper->currencyByStore($_value->getPrice(true), $store, false)]
            );
        }
        if ($_option->getType() == Option::OPTION_TYPE_MULTIPLE) {
            $extraParams = ' multiple="multiple"';
        }
        if (!$this->getSkipJsReloadPrice()) {
            $extraParams .= ' onchange="opConfig.reloadPrice()"';
        }
        $extraParams .= ' data-selector="' . $select->getName() . '"';
        $select->setExtraParams($extraParams);

        if ($configValue) {
            $select->setValue($configValue);
        }

        $clone = "<input type='hidden' class='bss-customoption-select'
        name='bss-fastorder-options[$sortOrder][{$_option->getid()}]' value=''/>";
        return $select->getHtml() . $clone;
    }
}
