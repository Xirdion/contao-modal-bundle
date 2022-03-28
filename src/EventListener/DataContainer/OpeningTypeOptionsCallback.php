<?php

declare(strict_types=1);

/*
 * This file is part of SowieSo contao-modal-bundle
 *
 * @copyright  Copyright (c) 2022, Ideenwerkstatt Sowieso GmbH & Co. KG
 * @author     Sowieso GmbH & Co. KG <https://sowieso.team>
 * @link       https://github.com/sowieso-web/contao-modal-bundle
 */

namespace Sowieso\ModalBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCallback('tl_content', 'fields.modal_content_type.options', 'onGetContentTypeOptions')]
#[AsCallback('tl_module', 'fields.modal_content_type.options', 'onGetContentTypeOptions')]
class OpeningTypeOptionsCallback
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param DataContainer|null $dataContainer
     *
     * @return array<string, string>
     */
    public function onGetOpeningTypeOptions(?DataContainer $dataContainer): array
    {
        $domain = 'SowiesoModalBundle';

        return [
            'modal_time' => $this->translator->trans('modal_opening_type.time', [], $domain),
            'modal_button' => $this->translator->trans('modal_opening_type.button', [], $domain),
            'modal_scroll' => $this->translator->trans('modal_opening_type.scroll', [], $domain),
        ];
    }
}
