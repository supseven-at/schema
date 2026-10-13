<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

(static function ($extKey, $table): void {
    $languageFileBePrefix = 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_BackendGeneral.xlf:';

    $additionalColumns = [
        'tx_schema_schema_type' => [
            'label'       => $languageFileBePrefix . 'field.pages.tx_schema_schema_type.label',
            'description' => $languageFileBePrefix . 'field.pages.tx_schema_schema_type.description',
            'config'      => [
                'type'       => 'select',
                'renderType' => 'selectSingle',
                'items'      => [
                    [
                        'label' => '',
                        'value' => '',
                    ],
                    [
                        'label' => 'WebPage',
                        'value' => 'webpage',
                    ],
                ],
                'default' => '',
            ],
        ],
    ];
    ExtensionManagementUtility::addTCAcolumns($table, $additionalColumns);

    ExtensionManagementUtility::addToAllTCAtypes(
        $table,
        '--div--;' . $languageFileBePrefix . 'tabs.schema,tx_schema_schema_type',
        (string)PageRepository::DOKTYPE_DEFAULT,
        'after:subtitle'
    );

})('schema', 'pages');
