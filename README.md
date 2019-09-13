# Imagina Magento 2 Envioclick Module

## Configuration in Magento (Store)

    - The unit of weight must be 'kg'
    - The unit of measure must be 'cm'

    if they are not configured they will be converted automatically

    - Important: You must configure the following attributes of ALL your products:
        - width
        - height
        - length
        - weight

## How to Install

From the command line in magento root:
```ssh
composer require imagina/magento2-envioclick

php bin/magento module:enable Imagina_Envioclick
php bin/magento setup:upgrade

```