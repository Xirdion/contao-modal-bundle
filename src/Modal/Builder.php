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
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Model;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;

class Builder
{
    private Model $model;
    private Request $request;
    private ?PageModel $page = null;

    public function __construct(
        private ScopeMatcher $scopeMatcher,
        private Studio $studio,
        private InsertTagParser $tagParser,
    ) {
    }

    /**
     * @param Model $model
     *
     * @return $this
     */
    public function setModel(Model $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * @param Request $request
     *
     * @return $this
     */
    public function setRequest(Request $request): self
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @param PageModel|null $page
     *
     * @return $this
     */
    public function setPage(?PageModel $page): self
    {
        $this->page = $page;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function build(): ?array
    {
        // Check if minimum data is given
        if (false === isset($this->model) || null === $this->page || false === isset($this->request)) {
            return null;
        }

        // Only handle frontend requests
        if (false === $this->scopeMatcher->isFrontendRequest($this->request)) {
            return null;
        }

        // Check if the current page is excluded
        $excluded = StringUtil::deserialize($this->model->__get('modal_excludedPages'), true);
        if ($this->isPageExcluded($this->page, $excluded)) {
            return null;
        }

        // Check if we are visiting the target of the modal
        if ($this->isModalPage($this->page, $this->model->__get('url'))) {
            return null;
        }

        $modalId = $this->getModalId($this->model);
        $modalData['modalId'] = $modalId;
        $modalData['modalClass'] = 'modal';

        // Headline data
        $headlineData = StringUtil::deserialize($this->model->__get('headline'), true);
        $modalData['headline'] = $headlineData['value'] ?? null;
        $modalData['hl'] = $headlineData['unit'] ?? null;
        $modalData['headlineId'] = 'modalHeadline-' . $modalId;

        // Content data
        $modalData['contentId'] = 'modalContent-' . $modalId;
        $contentType = $this->model->__get('modal_content_type');
        match ($contentType) {
            'modal_image' => $this->addImageData($modalData),
            'modal_html' => $this->addHtmlData($modalData),
            default => $this->addTextData($modalData),
        };

        // Link data
        // If a single image is the only content of the modal the extra link should not get generated
        $modalData['url'] = ('modal_image' !== $contentType ? $this->model->__get('url') : '');
        $modalData['target'] = $this->model->__get('target');
        $modalData['linkTitle'] = $this->model->__get('linkTitle') ?: $this->model->__get('url');
        $modalData['titleText'] = $this->model->__get('titleText');

        $openingType = $this->model->__get('modal_opening_type');
        $modalData['openingType'] = match ($openingType) {
            'modal_button' => 'button',
            'modal_scroll' => 'scroll',
            default => 'time',
        };

        // Adding additional attributes to the modal
        $attributes = [
            'role' => 'dialog',
            'aria-labelledby' => (null !== $modalData['headline'] ? $modalData['headlineId'] : ''),
            'aria-describedby' => $modalData['contentId'],
        ];

        // Add additional modal opening button
        if ('modal_button' === $openingType) {
            $modalData['modalButton'] = $this->model->__get('modal_button');
        }

        if ('modal_scroll' === $openingType) {
            // Add additional modal class
            $modalData['modalClass'] .= ' js-modal-scroll';
            $modalData['modalStop'] = (int) $this->model->__get('modal_stop') * 1000;
            $attributes['data-stop-time'] = (string) $modalData['modalStop'];
        }

        // Add start and stop properties
        if ('modal_time' === $openingType) {
            // Add additional modal class
            $modalData['modalClass'] .= ' js-modal-time';
            $modalData['modalStart'] = (int) $this->model->__get('modal_start') * 1000;
            $modalData['modalStop'] = (int) $this->model->__get('modal_stop') * 1000;

            // Add attributes
            $attributes['data-start-time'] = (string) $modalData['modalStart'];
            $attributes['data-stop-time'] = (string) $modalData['modalStop'];
        }

        // Generate attributes string
        $modalData['attributes'] = array_reduce(
            array_keys($attributes),
            static fn ($carry, $key) => $carry . ' ' . $key . '="' . htmlspecialchars($attributes[$key]) . '"',
            '',
        );

        return $modalData;
    }

    /**
     * @param PageModel|null $page
     * @param int[]          $excludedPages
     *
     * @return bool
     */
    private function isPageExcluded(?PageModel $page, array $excludedPages): bool
    {
        if (null === $page) {
            return false;
        }

        return \in_array($page->id, $excludedPages, true);
    }

    private function isModalPage(?PageModel $page, string $url): bool
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

        // Set url to an empty string if it is "./" as this indicates the start page
        $url = './' === $url ? '' : $url;

        return $page->getFrontendUrl() === $url;
    }

    /**
     * @param Model $model
     *
     * @return string
     */
    private function getModalId(Model $model): string
    {
        if (\is_string($model->__get('cssID'))) {
            $cssId = StringUtil::deserialize($model->__get('cssID'), true);
        } else {
            $cssId = $model->__get('cssID');
        }

        // Use the custom cssID or the ID of the module
        if (null !== $cssId && 0 < \count($cssId)) {
            $modalId = $cssId[0];
        } else {
            $modalId = $model->__get('id');
        }

        // Add the current timestamp of the module to handle changes
        $modalId .= '-' . $model->__get('tstamp');

        return $modalId;
    }

    /**
     * @param array<string, mixed> $modalData
     *
     * @return void
     */
    private function addImageData(array &$modalData): void
    {
        // Reset the other content type variables
        $modalData['text'] = null;
        $modalData['html'] = null;

        // Add specific image content variables
        $modalData['contentClass'] = 'ce_image';
        $imageData = $this->getImageData();
        if (null !== $imageData) {
            $modalData['addImage'] = true;
            $modalData['imageData'] = $imageData;
        }
    }

    /**
     * @param array<string, mixed> $modalData
     *
     * @return void
     */
    private function addHtmlData(array &$modalData): void
    {
        // Reset the other content type variables
        $modalData['text'] = null;
        $modalData['addImage'] = false;

        // Add specific html content variables
        $modalData['contentClass'] = 'ce_html';
        $modalData['html'] = $this->model->__get('html');
    }

    /**
     * @param array<string, mixed> $modalData
     *
     * @return void
     */
    private function addTextData(array &$modalData): void
    {
        // Reset the other content type variables
        $modalData['html'] = null;
        $modalData['addImage'] = false;

        // Add specific text content variables
        $this->addImageData($modalData);
        $modalData['contentClass'] = 'ce_text';
        $modalData['text'] = $this->model->__get('text');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getImageData(): ?array
    {
        // Check if there is an image source
        $singleSRC = $this->model->__get('singleSRC');
        if (!$singleSRC) {
            return null;
        }

        $table = $this->model::getTable();
        $size = $this->model->__get('tl_module' === $table ? 'imgSize' : 'size');

        $metadata = new Metadata([
            Metadata::VALUE_ALT => $this->model->__get('linkTitle') ?? '',
            Metadata::VALUE_TITLE => $this->model->__get('titleText') ?? '',
            Metadata::VALUE_URL => $this->tagParser->replaceInline($this->model->__get('url') ?? ''),
        ]);

        // Try to build the image resource
        $figure = $this->studio
            ->createFigureBuilder()
            ->from($singleSRC)
            ->setSize($size)
            ->setMetadata($metadata)
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
            'href' => $href,
            'linkTitle' => (\array_key_exists('title', $linkAttributes) ? $linkAttributes['title'] : $metadata->getTitle()),
        ];
    }
}
