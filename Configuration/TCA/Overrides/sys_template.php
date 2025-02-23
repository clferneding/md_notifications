<?php
defined('TYPO3') || die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'md_notifications',
    'Configuration/TypoScript',
    'Notifications'
);
