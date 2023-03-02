<?php
defined('TYPO3') || die('Access denied.');

call_user_func(
    function () {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'PxaSurvey',
            'Survey',
            [
                \Pixelant\PxaSurvey\Controller\SurveyController::class => 'show, showResults, answer, finish'
            ],
            // non-cacheable actions
            [
                \Pixelant\PxaSurvey\Controller\SurveyController::class => 'show, showResults, answer, finish'
            ]
        );

        // wizards
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
            '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:pxa_survey/Configuration/TypoScript/PageTS/wizards.ts">'
        );
    }
);
