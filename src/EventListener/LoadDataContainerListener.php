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
use Sowieso\ModalBundle\EventListener\DataContainer\ContentTypeOptionsCallback;
use Sowieso\ModalBundle\EventListener\DataContainer\OpeningTypeOptionsCallback;
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
        $size = ('tl_module' === $table ? 'imgSize' : 'size');
        $palette = <<<'PALETTE'
            {content_type_legend},modal_content_type;
            {link_legend},url,target,linkTitle,titleText;
            {modal_legend},modal_excludedPages,modal_opening_type;
            {template_legend:hide},customTpl;
            {protected_legend:hide},protected;
            {expert_legend:hide},guests,cssID;
            PALETTE;

        switch ($table) {
            case 'tl_content':
                $palette = '{type_legend},type,headline;' . $palette;
                break;
            case 'tl_module':
                $palette = '{title_legend},name,headline,type;' . $palette;
                break;
        }
        if ('tl_content' === $table) {
            $palette .= '{invisible_legend:hide},invisible,start,stop';
        }

        // Add the new palette to the global data container
        $GLOBALS['TL_DCA'][$table]['palettes']['sowiesoModal'] = $palette;

        // Add additional selector
        $GLOBALS['TL_DCA'][$table]['palettes']['__selector__'][] = 'modal_content_type';
        $GLOBALS['TL_DCA'][$table]['palettes']['__selector__'][] = 'modal_opening_type';

        // Add additional sub palettes
        // content type
        $GLOBALS['TL_DCA'][$table]['subpalettes']['modal_content_type_modal_text'] = '{text_legend},text,{image_legend},singleSRC,' . $size;
        $GLOBALS['TL_DCA'][$table]['subpalettes']['modal_content_type_modal_image'] = '{image_legend},singleSRC,' . $size;
        $GLOBALS['TL_DCA'][$table]['subpalettes']['modal_content_type_modal_html'] = '{text_legend},html';

        // opening type
        $GLOBALS['TL_DCA'][$table]['subpalettes']['modal_opening_type_modal_time'] = 'modal_start,modal_stop';
        $GLOBALS['TL_DCA'][$table]['subpalettes']['modal_opening_type_modal_button'] = 'modal_button';
        $GLOBALS['TL_DCA'][$table]['subpalettes']['modal_opening_type_modal_scroll'] = '';
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
                'exclude' => true,
                'inputType' => 'text',
                'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
                'sql' => ['type' => 'string', 'length' => 255, 'notnull' => true, 'default' => ''],
            ];
        }

        if (false === isset($GLOBALS['TL_DCA'][$table]['fields']['linkTitle'])) {
            $GLOBALS['TL_DCA'][$table]['fields']['linkTitle'] = [
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
            $this->translator->trans('modal_content_type', [], 'SowiesoModalBundle'),
            $this->translator->trans('modal_content_type_info', [], 'SowiesoModalBundle'),
        ];
        $GLOBALS['TL_DCA'][$table]['fields']['modal_content_type'] = [
            'label' => $label,
            'exclude' => true,
            'inputType' => 'select',
            'eval' => ['submitOnChange' => true, 'tl_class' => 'w50'],
            'options_callback' => [ContentTypeOptionsCallback::class, 'onGetContentTypeOptions'],
            'sql' => ['type' => 'string', 'length' => 12, 'notnull' => true, 'default' => 'modal_text'],
        ];

        $label = [
            $this->translator->trans('excluded_pages', [], 'SowiesoModalBundle'),
            $this->translator->trans('excluded_pages_info', [], 'SowiesoModalBundle'),
        ];
        $GLOBALS['TL_DCA'][$table]['fields']['modal_excludedPages'] = [
            'label' => $label,
            'exclude' => true,
            'inputType' => 'pageTree',
            'foreignKey' => 'tl_page.title',
            'eval' => ['multiple' => true, 'fieldType' => 'checkbox', 'isSortable' => true, 'tl_class' => 'clr'],
            'sql' => ['type' => 'blob', 'notnull' => false],
            'relation' => ['type' => 'hasMany', 'load' => 'lazy'],
        ];

        $label = [
            $this->translator->trans('modal_opening_type', [], 'SowiesoModalBundle'),
            $this->translator->trans('modal_opening_type_info', [], 'SowiesoModalBundle'),
        ];
        $GLOBALS['TL_DCA'][$table]['fields']['modal_opening_type'] = [
            'label' => $label,
            'exclude' => true,
            'inputType' => 'select',
            'eval' => ['submitOnChange' => true, 'tl_class' => 'w50'],
            'options_callback' => [OpeningTypeOptionsCallback::class, 'onGetOpeningTypeOptions'],
            'sql' => ['type' => 'string', 'length' => 12, 'notnull' => true, 'default' => 'modal_time'],
        ];

        $label = [
            $this->translator->trans('button', [], 'SowiesoModalBundle'),
            $this->translator->trans('button_info', [], 'SowiesoModalBundle'),
        ];
        $GLOBALS['TL_DCA'][$table]['fields']['modal_button'] = [
            'label' => $label,
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 255, 'notnull' => true, 'default' => ''],
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
