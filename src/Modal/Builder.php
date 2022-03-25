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
use Contao\Image\PictureConfiguration;
use Contao\Model;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;

class Builder
{
    private ?Model $model = null;
    private ?Request $request = null;
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
     * @param Request|null $request
     *
     * @return $this
     */
    public function setRequest(?Request $request): self
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
        if (null === $this->model || null === $this->page || null === $this->request) {
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

        $modalData['modalId'] = $this->getModalId($this->model);

        $headlineData = StringUtil::deserialize($this->model->__get('headline'), true);
        $modalData['headline'] = $headlineData['value'] ?? null;
        $modalData['hl'] = $headlineData['unit'] ?? null;

        $modalData['text'] = $this->model->__get('text');
        $modalData['html'] = $this->model->__get('html');
        $modalData['url'] = $this->model->__get('url');
        $modalData['target'] = $this->model->__get('target');
        $modalData['linkTitle'] = $this->model->__get('linkTitle') ?: $this->model->__get('url');
        $modalData['titleText'] = $this->model->__get('titleText');

        $table = $this->model::getTable();
        $size = ('tl_module' === $table ? 'imgSize' : 'size');
        $modalData['addImage'] = false;
        $imageData = $this->getImageData(
            $this->model->__get('singleSRC'),
            $this->model->__get($size),
            $this->model->__get('url'),
        );
        if (null !== $imageData) {
            $modalData['addImage'] = true;
            $modalData['imageData'] = $imageData;
        }

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
     * @param string|null                                                $singleSRC
     * @param int|string|array<string, string>|PictureConfiguration|null $size
     * @param string                                                     $href
     *
     * @return array<string, mixed>|null
     */
    private function getImageData(?string $singleSRC, mixed $size, string $href): ?array
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
            'href' => $href,
            'linkTitle' => (\array_key_exists('title', $linkAttributes) ? $linkAttributes['title'] : $metadata->getTitle()),
        ];
    }
}
