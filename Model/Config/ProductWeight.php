<?php

namespace Imagina\Envioclick\Model\Config;

use Magento\Framework\Option\ArrayInterface;

class ProductWeight implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'weightDefault', 'label' => __('Peso Total por Defecto')],
            ['value' => 'weightTotalProduct', 'label' => __('Peso Total de los productos')],
        ];
    }
}