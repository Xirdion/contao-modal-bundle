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
use Sowieso\ModalBundle\Modal\ContentType;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCallback('tl_content', 'fields.modal_content_type.options', 'onGetContentTypeOptions')]
#[AsCallback('tl_module', 'fields.modal_content_type.options', 'onGetContentTypeOptions')]
class ContentTypeOptionsCallback
{
    public function __construct(
        private TranslatorInterface $translator,
        private ContentType $contentType,
    ) {
    }

    /**
     * @param DataContainer|null $dataContainer
     *
     * @return array<string|int, string>
     */
    public function onGetContentTypeOptions(?DataContainer $dataContainer): array
    {
        $domain = 'SowiesoModalBundle';

        return [
            $this->contentType::OPTION_TEXT => $this->translator->trans('modal_content_type.text', [], $domain),
            $this->contentType::OPTION_IMAGE => $this->translator->trans('modal_content_type.image', [], $domain),
            $this->contentType::OPTION_HTML => $this->translator->trans('modal_content_type.html', [], $domain),
        ];
    }
}
