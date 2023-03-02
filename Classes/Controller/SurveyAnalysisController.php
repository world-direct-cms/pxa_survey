<?php

namespace Pixelant\PxaSurvey\Controller;

/***
 *
 * This file is part of the "Simple Survey" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2017 Andriy Oprysko
 *
 ***/

use Pixelant\PxaSurvey\Domain\Model\Survey;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class SurveyAnalysisController
 * @package Pixelant\PxaSurvey\Controller
 */
class SurveyAnalysisController extends AbstractController
{
    /**
     * ModuleTemplateFactory
     *
     * @var ModuleTemplateFactory
     */
    protected ModuleTemplateFactory $moduleTemplateFactory;
    /**
     * Current page
     *
     * @var int
     */
    protected $pid = 0;

    public function __construct(
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    /**
     * Initialize
     */
    public function initializeAction()
    {
        $this->pid = (int)GeneralUtility::_GET('id');
    }

    /**
     * Main action
     */
    public function mainAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        $this->createButtons($moduleTemplate);

        if ($this->pid) {
            $surveys = $this->surveyRepository->findByPid($this->pid);
        }

        $this->view->assign('surveys', $surveys ?? []);

        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * Display analysis for survey
     *
     * @param Survey $survey
     */
    public function seeAnalysisAction(Survey $survey): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        $data = $this->generateAnalysisData($survey);

        $this->view->assign('dataJson', json_encode($data));
        $this->view->assign('data', $data);

        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * Export data as csv file
     *
     * @param Survey $survey
     */
    public function exportCsvAction(Survey $survey): ResponseInterface
    {
        $data = $this->generateAnalysisData($survey);

        $lines = [
            [$survey->getName() . ($survey->getTitle() ? (' (' . $survey->getTitle() . ')') : '')]
        ];

        foreach ($data as $questionData) {
            $lines[] = []; // empty line
            $lines[] = [$questionData['label']];


            $lines[] = []; // empty line
            $lines[] = [
                $this->translate('module.answers'),
                $this->translate('module.percentages'),
                $this->translate('module.count'),
            ];

            foreach ($questionData['questionData'] as $questionAnswerData) {
                $lines[] = [
                    $questionAnswerData['label'],
                    $questionAnswerData['percents'] . ' %',
                    $questionAnswerData['count']
                ];
            }
            $lines[] = [
                '',
                '',
                $this->translate('module.total_answers', [$questionData['allAnswersCount']])
            ];

            $lines[] = []; // empty line
        }

        $fileName = str_replace(' ', '_', $survey->getName()) . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=' . $fileName,
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ];
        foreach ($headers as $header => $headerValue) {
            header("$header: $headerValue");
        }

        $output = fopen('php://output', 'w');
        foreach ($lines as $singleLine) {
            fputcsv($output, $singleLine);
        }
        fclose($output);

        exit(0);
    }

    /**
     * Add menu buttons
     *
     * @return void
     */
    protected function createButtons(ModuleTemplate $moduleTemplate)
    {
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $button = $buttonBar->makeLinkButton()
            ->setHref($this->buildNewSurveyUrl())
            ->setTitle($this->translate('module.new_survey'))
            ->setIcon($iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL));

        $buttonBar->addButton($button, ButtonBar::BUTTON_POSITION_LEFT);
    }

    /**
     * Generate url to create new survey
     *
     * @return string
     */
    protected function buildNewSurveyUrl(): string
    {
        $urlParameters = [
            'edit[tx_pxasurvey_domain_model_survey][' . $this->pid . ']' => 'new',
            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
        ];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        return (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
    }

    /**
     * Translate label
     *
     * @param string $key
     * @param array|null $arguments
     * @return string
     */
    protected function translate(string $key, array $arguments = null): string
    {
        return LocalizationUtility::translate($key, 'PxaSurvey', $arguments) ?? '';
    }
}
