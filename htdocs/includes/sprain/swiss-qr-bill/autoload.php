<?php

$vendorDir = DOL_DOCUMENT_ROOT .'/includes';

$libs_and_depends = array(
    'kmukku\\phpIso11649\\' => array($vendorDir . '/kmukku/php-iso11649/src'),
    'Symfony\\Polyfill\\Mbstring\\' => array($vendorDir . '/symfony/polyfill-mbstring'),
    'Symfony\\Polyfill\\Intl\\Icu\\' => array($vendorDir . '/symfony/polyfill-intl-icu'),
    'Symfony\\Polyfill\\Ctype\\' => array($vendorDir . '/symfony/polyfill-ctype'),
    'Symfony\\Contracts\\Translation\\' => array($vendorDir . '/symfony/translation-contracts'),
    'Symfony\\Component\\Validator\\' => array($vendorDir . '/symfony/validator'),
    'Symfony\\Component\\Intl\\' => array($vendorDir . '/symfony/intl'),
    'Sprain\\Tests\\SwissQrBill\\' => array($vendorDir . '/sprain/swiss-qr-bill/tests'),
    'Sprain\\SwissQrBill\\' => array($vendorDir . '/sprain/swiss-qr-bill/src'),
    'Endroid\\QrCode\\' => array($vendorDir . '/endroid/qr-code/src'),
    'DASPRiD\\Enum\\' => array($vendorDir . '/dasprid/enum/src'),
    'BaconQrCode\\' => array($vendorDir . '/bacon/bacon-qr-code/src'),
);

spl_autoload_register(function ($class) use ($libs_and_depends) {
    foreach ($libs_and_depends as $classprefix => $paths) {
        if (str_starts_with($class, $classprefix)) {
            $classpostfix = substr($class, strlen($classprefix));

            $classfilepath = str_replace('\\', DIRECTORY_SEPARATOR, $classpostfix).'.php';

            foreach ($paths as $path) {
                $finalpath = $path . DIRECTORY_SEPARATOR . $classfilepath;
                if (file_exists($finalpath)) {
                    require_once($finalpath);
                }
            }
        }
    }
});