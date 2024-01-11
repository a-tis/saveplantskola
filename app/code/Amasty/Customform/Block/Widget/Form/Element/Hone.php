<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Customform
 */

/**
 * Copyright В© 2016 Amasty. All rights reserved.
 */
namespace Amasty\Customform\Block\Widget\Form\Element;

class Hone extends Text
{
    public function _construct()
    {
        parent::_construct();

        $this->options['title'] = __('H1');
    }

    public function generateContent()
    {
        return '<h1 class="title">' . $this->getExamplePhrase() . '</h1>';
    }
}
