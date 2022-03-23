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

use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\Image\PictureConfiguration;
use Contao\Model;
use Contao\PageModel;
use Contao\StringUtil;

class Modal
{
    public function __construct(
        private InsertTagParser $tagParser,
        private Studio $studio,
    ) {
    }

    /**
     * @param PageModel|null $page
     * @param int[]          $excludedPages
     *
     * @return bool
     */
    public function isPageExcluded(?PageModel $page, array $excludedPages): bool
    {
        if (null === $page) {
            return false;
        }

        return \in_array($page->id, $excludedPages, true);
    }

    public function isModalPage(?PageModel $page, string $url): bool
    {
        if (null === $page) {
            return false;
        }

        // Page can never be a mailto link
        if (0 === strncmp($url, 'mailto:', 7)) {
            return false;
        }

        $url = StringUtil::ampersand($url);
        $url = $this->tagParser->replace($url);

        return $page->getFrontendUrl() === $url;
    }

    /**
     * @param Model $model
     *
     * @return string
     */
    public function getModalId(Model $model): string
    {
        if (\is_string($model->cssID)) {
            $cssId = StringUtil::deserialize($model->cssID, true);
        } else {
            $cssId = $model->cssID;
        }

        // Use the custom cssID or the ID of the module
        if (null !== $cssId && 0 < \count($cssId)) {
            $modalId = $cssId[0];
        } else {
            $modalId = $model->id;
        }

        // Add the current timestamp of the module to handle changes
        $modalId .= '-' . $model->tstamp;

        return $modalId;
    }

    /**
     * @param string|null                                $singleSRC
     * @param int|string|array|PictureConfiguration|null $size
     * @param bool                                       $fullsize
     * @param string                                     $href
     *
     * @return array|null
     *
     * @phpstan-ignore-next-line
     */
    public function getImageData(?string $singleSRC, $size, bool $fullsize, string $href): ?array
    {
        // Check if there is an image source
        if (!$singleSRC) {
            return null;
        }

        // Try to build the image resource
        $figure = $this->studio
            ->createFigureBuilder()
            ->from($singleSRC)
            ->setSize($size)
            ->enableLightbox($fullsize)
            ->setLinkHref($href)
            ->buildIfResourceExists()
        ;

        if (null === $figure) {
            return null;
        }

        $image = $figure->getImage();
        $orgSize = $image->getOriginalDimensions()->getSize();
        $metadata = $figure->hasMetadata() ? $figure->getMetadata() : new Metadata([]);
        $linkAttributes = $figure->getLinkAttributes();
        $href = $figure->getLinkHref();

        // Collect the image data
        return [
            'picture' => [
                'img' => $image->getImg(),
                'sources' => $image->getSources(),
                'alt' => StringUtil::specialchars($metadata->getAlt()),
            ],
            'width' => $orgSize->getWidth(),
            'height' => $orgSize->getHeight(),
            'singleSRC' => $image->getFilePath(),
            'src' => $image->getImageSrc(),
            'fullsize' => ('_blank' === ($linkAttributes['target'] ?? null)) || $figure->hasLightbox(),
            'href' => $href,
            'linkTitle' => (\array_key_exists('title', $linkAttributes) ? $linkAttributes['title'] : $metadata->getTitle()),
        ];
    }
}
