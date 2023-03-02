<?php
defined('TYPO3') || die('Access denied.');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'pxa_survey',
    'Configuration/TypoScript',
    'Simple Survey'
);
