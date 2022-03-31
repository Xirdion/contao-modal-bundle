<?php

declare(strict_types=1);

/*
 * This file is part of SowieSo contao-modal-bundle
 *
 * @copyright  Copyright (c) 2022, Ideenwerkstatt Sowieso GmbH & Co. KG
 * @author     Sowieso GmbH & Co. KG <https://sowieso.team>
 * @link       https://github.com/sowieso-web/contao-modal-bundle
 */

namespace Sowieso\ModalBundle\Modal;

class ContentType
{
    public const OPTION_TEXT = 'modal_text';
    public const OPTION_IMAGE = 'modal_image';
    public const OPTION_HTML = 'modal_html';

    /**
     * Get a Contao specific content class depending on the given content type.
     *
     * @param string $type
     *
     * @return string
     */
    public function getContentClass(string $type): string
    {
        return match ($type) {
            self::OPTION_IMAGE => 'ce_image',
            self::OPTION_HTML => 'ce_html',
            default => 'ce_text',
        };
    }
}
