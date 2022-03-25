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
use Contao\CoreBundle\Routing\ScopeMatcher;
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

        if (false === $this->scopeMatcher->isFrontendRequest($this->request)) {
            return;
        }

        if ('modules' === $name) {
            $GLOBALS['TL_LANG']['FMD']['sowiesoModal'] = [
                $this->translator->trans('sowiesoModal', [], $this->domain),
                $this->translator->trans('sowiesoModal_info', [], $this->domain),
            ];

            return;
        }

        // Get act and id from request and check if it is the sowiesoModal type

        switch ($name) {
            case 'tl_content':
                $GLOBALS['TL_LANG'][$name]['modal_legend'] = $this->translator->trans('text_legend', [], $this->domain);
                break;
            case 'tl_module':
                // Legends
                $GLOBALS['TL_LANG'][$name]['text_legend'] = $this->translator->trans('text_legend', [], $this->domain);
                $GLOBALS['TL_LANG'][$name]['modal_legend'] = $this->translator->trans('text_legend', [], $this->domain);

                // Fields
                $GLOBALS['TL_LANG'][$name]['text'] = [
                    $this->translator->trans('text', [], $this->domain),
                    $this->translator->trans('text_info', [], $this->domain),
                ];
                $GLOBALS['TL_LANG'][$name]['url'] = [
                    $this->translator->trans('url', [], $this->domain),
                    $this->translator->trans('url_info', [], $this->domain),
                ];
                $GLOBALS['TL_LANG'][$name]['titleText'] = [
                    $this->translator->trans('titleText', [], $this->domain),
                    $this->translator->trans('titleText_info', [], $this->domain),
                ];
                $GLOBALS['TL_LANG'][$name]['linkTitle'] = [
                    $this->translator->trans('linkTitle', [], $this->domain),
                    $this->translator->trans('linkTitle_info', [], $this->domain),
                ];
                break;
        }
    }
}
