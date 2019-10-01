<?php

namespace Imagina\Envioclick\Model\Config;

use Magento\Framework\Option\ArrayInterface;

class ProductDimensions implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'dimensionsDefault', 'label' => __('Medidas por Defecto')],
            ['value' => 'dimensionsTotalProduct', 'label' => __('Medidas por producto')],
        ];
    }
}