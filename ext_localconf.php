<?php

defined('TYPO3') || die();

use Mediadreams\MdNotifications\Controller\NotificationController;
use Mediadreams\MdNotifications\Hooks\TCEmainHook;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

(static function() {
    $plugins = [
        'Count',
        'HasSeen',
        'Delete'
    ];

    foreach ($plugins as $plugin) {
        ExtensionUtility::configurePlugin(
            'MdNotifications',
            $plugin,
            [
                NotificationController::class => strtolower($plugin)
            ],
            [
                NotificationController::class => strtolower($plugin)
            ],
            ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
        );
    }

    ExtensionUtility::configurePlugin(
        'MdNotifications',
        'Notifications',
        [
            NotificationController::class => 'list'
        ],
        [
            NotificationController::class => 'list'
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
        @import \'EXT:md_notifications/Configuration/TSconfig/ContentElementWizard.tsconfig\'
    ');

    // Hook into saving process of records
    $GLOBALS['TYPO3_CONF_VARS']
            ['SC_OPTIONS']
            ['t3lib/class.t3lib_tcemain.php']
            ['processDatamapClass']
            ['md_notifications'] = TCEmainHook::class;

    // Hook into deleting process of records
    $GLOBALS['TYPO3_CONF_VARS']
            ['SC_OPTIONS']
            ['t3lib/class.t3lib_tcemain.php']
            ['processCmdmapClass']
            ['md_notifications'] = TCEmainHook::class;
})();
