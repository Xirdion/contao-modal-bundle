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

use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Model;
use Contao\ModuleModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsHook('loadLanguageFile', 'onLoadLanguageFile')]
class LoadLanguageFileListener
{
    private ?Request $request;
    private string $domain = 'SowiesoModalBundle';

    public function __construct(
        private RequestStack $requestStack,
        private ScopeMatcher $scopeMatcher,
        private TranslatorInterface $translator,
    ) {
        $this->request = $this->requestStack->getCurrentRequest();
    }

    public function onLoadLanguageFile(string $name, string $currentLanguage, string $cacheKey): void
    {
        if (null === $this->request) {
            return;
        }

        if (true === $this->scopeMatcher->isFrontendRequest($this->request)) {
            return;
        }

        try {
            match ($name) {
                // Add general name of the content element type / module type
                'default' => $this->addModuleTranslation(),

                // Add additional translations for the DCA fields
                'tl_content', 'tl_module' => $this->addDcaTranslations($name),
            };
        } catch (\UnhandledMatchError $e) {
            return;
        }
    }

    private function addModuleTranslation(): void
    {
        $GLOBALS['TL_LANG']['FMD']['sowiesoModal'] = [
            $this->translator->trans('sowiesoModal', [], $this->domain),
            $this->translator->trans('sowiesoModal_info', [], $this->domain),
        ];

        $GLOBALS['TL_LANG']['CTE']['sowiesoModal'] = [
            $this->translator->trans('sowiesoModal', [], $this->domain),
            $this->translator->trans('sowiesoModal_info', [], $this->domain),
        ];
    }

    /**
     * Add some additional translations for the new modal to tl_content and tl_module.
     *
     * @param string $table
     *
     * @return void
     */
    private function addDcaTranslations(string $table): void
    {
        if (null === $this->request) {
            return;
        }

        // Check request mode: it must be the edit mode
        if ('edit' !== $this->request->query->get('act')) {
            return;
        }

        // Try to load the model and check the type
        $id = (int) $this->request->query->get('id');

        /** @var Model $modelClass */
        $modelClass = Model::getClassFromTable($table);

        /** @var ContentModel|ModuleModel|null $model */
        $model = $modelClass::findByPk($id);

        // Check if the model could get loaded
        if (null === $model) {
            return;
        }

        // Check the type of the model
        if ('sowiesoModal' !== $model->type) {
            return;
        }

        switch ($table) {
            case 'tl_content':
                $GLOBALS['TL_LANG'][$table]['content_type_legend'] = $this->translator->trans('content_type_legend', [], $this->domain);
                $GLOBALS['TL_LANG'][$table]['modal_legend'] = $this->translator->trans('modal_legend', [], $this->domain);
                break;
            case 'tl_module':
                // Legends
                $GLOBALS['TL_LANG'][$table]['content_type_legend'] = $this->translator->trans('content_type_legend', [], $this->domain);
                $GLOBALS['TL_LANG'][$table]['modal_legend'] = $this->translator->trans('text_legend', [], $this->domain);

                // Fields
                $GLOBALS['TL_LANG'][$table]['text'] = [
                    $this->translator->trans('text', [], $this->domain),
                    $this->translator->trans('text_info', [], $this->domain),
                ];
                $GLOBALS['TL_LANG'][$table]['url'] = [
                    $this->translator->trans('url', [], $this->domain),
                    $this->translator->trans('url_info', [], $this->domain),
                ];
                $GLOBALS['TL_LANG'][$table]['titleText'] = [
                    $this->translator->trans('titleText', [], $this->domain),
                    $this->translator->trans('titleText_info', [], $this->domain),
                ];
                $GLOBALS['TL_LANG'][$table]['linkTitle'] = [
                    $this->translator->trans('linkTitle', [], $this->domain),
                    $this->translator->trans('linkTitle_info', [], $this->domain),
                ];
                break;
        }
    }
}
