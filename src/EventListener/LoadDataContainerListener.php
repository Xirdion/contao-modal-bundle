<?php

declare(strict_types=1);

/*
 * This file is part of SowieSo contao-modal-bundle
 *
 * @copyright  Copyright (c) 2022, Ideenwerkstatt Sowieso GmbH & Co. KG
 * @author     Sowieso GmbH & Co. KG <https://sowieso.team>
 * @link       https://github.com/sowieso-web/contao-modal-bundle
 */

namespace Sowieso\ModalBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsHook('loadDataContainer', 'onLoadDataContainer')]
class LoadDataContainerListener
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function onLoadDataContainer(string $table): void
    {
        if ('tl_content' !== $table && 'tl_module' !== $table) {
            return;
        }

        // Add some additional fields to tl_module
        if ('tl_module' === $table) {
            $this->addAdditionalModuleFields();
        }

        // Add palette and fields to the DCAs
        $this->addModalPalette($table);
        $this->addModalFields($table);
    }

    private function addModalPalette(string $table): void
    {
        // Build the palette for the different tables
        $palette = '';
        switch ($table) {
            case 'tl_content':
                $palette .= '{type_legend},type,headline;';
                break;
            case 'tl_module':
                $palette .= '{title_legend},name,headline,type;';
                break;
        }
        $palette .= '{text_legend},text,html;{image_legend},singleSRC,' . ('tl_module' === $table ? 'imgSize' : 'size') . ',fullsize;{link_legend},url,target,linkTitle,titleText;{modal_legend},modal_excludedPages,modal_start,modal_stop;';
        switch ($table) {
            case 'tl_content':
                $palette .= '{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';
                break;
            case 'tl_module':
                $palette .= '{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
                break;
        }

        // Add the new palette to the global data container
        $GLOBALS['TL_DCA'][$table]['palettes']['sowiesoModal'] = $palette;
    }

    /**
     * Extend the tl_module DCA.
     *
     * @return void
     */
    private function addAdditionalModuleFields(): void
    {
        $table = 'tl_module';

        if (false === isset($GLOBALS['TL_DCA'][$table]['fields']['text'])) {
            $GLOBALS['TL_DCA'][$table]['fields']['text'] = [
                'label' => &$GLOBALS['TL_LANG']['tl_content']['text'],
                'exclude' => true,
                'inputType' => 'textarea',
                'eval' => ['rte' => 'tinyMCE', 'helpwizard' => true],
                'explanation' => 'insertTags',
                'sql' => ['type' => 'text', 'notnull' => false],
            ];
        }

        if (false === isset($GLOBALS['TL_DCA'][$table]['fields']['target'])) {
            $GLOBALS['TL_DCA'][$table]['fields']['target'] = [
                'label' => &$GLOBALS['TL_LANG']['MSC']['target'],
                'exclude' => true,
                'inputType' => 'checkbox',
                'eval' => ['tl_class' => 'w50 m12'],
                'sql' => ['type' => 'boolean', 'notnull' => true, 'default' => false],
            ];
        }

        if (false === isset($GLOBALS['TL_DCA'][$table]['fields']['titleText'])) {
            $GLOBALS['TL_DCA'][$table]['fields']['titleText'] = [
                'label' => &$GLOBALS['TL_LANG']['tl_content']['titleText'],
                'exclude' => true,
                'inputType' => 'text',
                'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
                'sql' => ['type' => 'string', 'length' => 255, 'notnull' => true, 'default' => ''],
            ];
        }

        if (false === isset($GLOBALS['TL_DCA'][$table]['fields']['linkTitle'])) {
            $GLOBALS['TL_DCA'][$table]['fields']['linkTitle'] = [
                'label' => &$GLOBALS['TL_LANG']['tl_content']['linkTitle'],
                'exclude' => true,
                'inputType' => 'text',
                'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
                'sql' => ['type' => 'string', 'length' => 255, 'notnull' => true, 'default' => ''],
            ];
        }
    }

    /**
     * Add the new fields for the modal settings to the DCAs.
     *
     * @param string $table
     *
     * @return void
     */
    private function addModalFields(string $table): void
    {
        $label = [
            $this->translator->trans('excluded_pages', [], 'SowiesoModalBundle'),
            $this->translator->trans('excluded_pages_info', [], 'SowiesoModalBundle'),
        ];
        $GLOBALS['TL_DCA'][$table]['fields']['modal_excludedPages'] = [
            'label' => $label,
            'exclude' => true,
            'inputType' => 'pageTree',
            'foreignKey' => 'tl_page.title',
            'eval' => ['multiple' => true, 'fieldType' => 'checkbox', 'isSortable' => true],
            'sql' => ['type' => 'blob', 'notnull' => false],
            'relation' => ['type' => 'hasMany', 'load' => 'lazy'],
        ];

        $label = [
            $this->translator->trans('start', [], 'SowiesoModalBundle'),
            $this->translator->trans('start_info', [], 'SowiesoModalBundle'),
        ];
        $GLOBALS['TL_DCA'][$table]['fields']['modal_start'] = [
            'label' => $label,
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'natural', 'nospace' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'integer', 'length' => 10, 'notnull' => true, 'default' => 0],
        ];

        $label = [
            $this->translator->trans('stop', [], 'SowiesoModalBundle'),
            $this->translator->trans('stop_info', [], 'SowiesoModalBundle'),
        ];
        $GLOBALS['TL_DCA'][$table]['fields']['modal_stop'] = [
            'label' => $label,
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'natural', 'nospace' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'integer', 'length' => 10, 'notnull' => true, 'default' => 0],
        ];
    }
}
