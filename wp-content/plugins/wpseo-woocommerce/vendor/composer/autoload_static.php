<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit3714c20fcbba510f30f81eb8524d0d20
{
    public static $classMap = array (
        'WPSEO_Option_Woo' => __DIR__ . '/../..' . '/classes/option-woo.php',
        'WPSEO_WooCommerce_Abstract_Product_Availability_Presenter' => __DIR__ . '/../..' . '/classes/presenters/woocommerce-abstract-product-availability-presenter.php',
        'WPSEO_WooCommerce_Abstract_Product_Presenter' => __DIR__ . '/../..' . '/classes/presenters/woocommerce-abstract-product-presenter.php',
        'WPSEO_WooCommerce_OpenGraph' => __DIR__ . '/../..' . '/classes/woocommerce-opengraph.php',
        'WPSEO_WooCommerce_Pinterest_Product_Availability_Presenter' => __DIR__ . '/../..' . '/classes/presenters/woocommerce-pinterest-product-availability-presenter.php',
        'WPSEO_WooCommerce_Product_Availability_Presenter' => __DIR__ . '/../..' . '/classes/presenters/woocommerce-product-availability-presenter.php',
        'WPSEO_WooCommerce_Product_Brand_Presenter' => __DIR__ . '/../..' . '/classes/presenters/woocommerce-product-brand-presenter.php',
        'WPSEO_WooCommerce_Product_Condition_Presenter' => __DIR__ . '/../..' . '/classes/presenters/woocommerce-product-condition-presenter.php',
        'WPSEO_WooCommerce_Product_OpenGraph_Deprecation_Presenter' => __DIR__ . '/../..' . '/classes/presenters/woocommerce-product-opengraph-deprecation-presenter.php',
        'WPSEO_WooCommerce_Product_Price_Amount_Presenter' => __DIR__ . '/../..' . '/classes/presenters/woocommerce-product-price-amount-presenter.php',
        'WPSEO_WooCommerce_Product_Price_Currency_Presenter' => __DIR__ . '/../..' . '/classes/presenters/woocommerce-product-price-currency-presenter.php',
        'WPSEO_WooCommerce_Product_Retailer_Item_ID_Presenter' => __DIR__ . '/../..' . '/classes/presenters/woocommerce-product-retailer-item-id-presenter.php',
        'WPSEO_WooCommerce_Schema' => __DIR__ . '/../..' . '/classes/woocommerce-schema.php',
        'WPSEO_WooCommerce_Twitter' => __DIR__ . '/../..' . '/classes/woocommerce-twitter.php',
        'WPSEO_WooCommerce_Utils' => __DIR__ . '/../..' . '/classes/woocommerce-utils.php',
        'WPSEO_WooCommerce_Yoast_Tab' => __DIR__ . '/../..' . '/classes/woocommerce-yoast-tab.php',
        'WPSEO_Woocommerce_Permalink_Watcher' => __DIR__ . '/../..' . '/classes/woocommerce-permalink-watcher.php',
        'Yoast_I18n_WordPressOrg_v3' => __DIR__ . '/..' . '/yoast/i18n-module/src/i18n-wordpressorg-v3.php',
        'Yoast_I18n_v3' => __DIR__ . '/..' . '/yoast/i18n-module/src/i18n-v3.php',
        'Yoast_WooCommerce_Dependencies' => __DIR__ . '/../..' . '/classes/woocommerce-dependencies.php',
        'Yoast_WooCommerce_SEO' => __DIR__ . '/../..' . '/classes/woocommerce-seo.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit3714c20fcbba510f30f81eb8524d0d20::$classMap;

        }, null, ClassLoader::class);
    }
}
