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
class ContentTypeOptionsCallback
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
    public function onGetContentTypeOptions(?DataContainer $dataContainer): array
    {
        $domain = 'SowiesoModalBundle';

        return [
            'modal_text' => $this->translator->trans('modal_content_type.text', [], $domain),
            'modal_image' => $this->translator->trans('modal_content_type.image', [], $domain),
            'modal_html' => $this->translator->trans('modal_content_type.html', [], $domain),
        ];
    }

    public static function getContentClass(string $type): string
    {
        return match ($type) {
            'modal_image' => 'ce_image',
            'modal_html' => 'ce_html',
            default => 'ce_text',
        };
    }
}
