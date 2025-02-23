<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') || die();

$pluginSignature = ExtensionUtility::registerPlugin(
    'MdNotifications',
    'Notifications',
    'LLL:EXT:md_notifications/Resources/Private/Language/locallang_db.xlf:mdnotifications_notifications.name',
    'tx_mdnotifications_notifications',
    null,
    'LLL:EXT:md_notifications/Resources/Private/Language/locallang_db.xlf:mdnotifications_notifications.description',
);

ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:md_notifications/Configuration/FlexForms/flexform_notifications.xml',
    $pluginSignature
);

ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    '--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.plugin,pi_flexform,pages,recursive',
    $pluginSignature,
    'after:palette:headers',
);
