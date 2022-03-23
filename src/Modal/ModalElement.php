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

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Model;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ModalElement
{
    private Template $template;
    private Model $model;
    private ?Request $request;
    private ?PageModel $page;

    public function __construct(
        private ScopeMatcher $scopeMatcher,
        private ModalConfig $modalConfig,
    ) {
    }

    /**
     * @param Template $template
     */
    public function setTemplate(Template $template): void
    {
        $this->template = $template;
    }

    /**
     * @param Model $model
     */
    public function setModel(Model $model): void
    {
        $this->model = $model;
    }

    /**
     * @param Request|null $request
     */
    public function setRequest(?Request $request): void
    {
        $this->request = $request;
    }

    /**
     * @param PageModel|null $page
     */
    public function setPage(?PageModel $page): void
    {
        $this->page = $page;
    }

    public function generateResponse(): ?Response
    {
        $templateData = $this->template->getData();
        $templateData['showModal'] = false;
        $this->template->setData($templateData);

        // Only handle frontend requests
        if (false === $this->scopeMatcher->isFrontendRequest($this->request)) {
            return null;
        }

        // Check if the current page is excluded
        $excluded = StringUtil::deserialize($this->model->modal_excludedPages, true);
        if ($this->modalConfig->isPageExcluded($this->page, $excluded)) {
            return null;
        }

        // Check if we are visiting the target of the modal
        if ($this->modalConfig->isModalPage($this->page, $this->model->url)) {
            return null;
        }

        $templateData = $this->template->getData();

        $templateData['showModal'] = true;
        $templateData['modalId'] = $this->modalConfig->getModalId($this->model);

        $headlineData = StringUtil::deserialize($this->model->headline, true);
        $templateData['headline'] = $headlineData['value'] ?? null;
        $templateData['hl'] = $headlineData['unit'] ?? null;

        $templateData['text'] = $this->model->text;
        $templateData['html'] = $this->model->html;
        $templateData['url'] = $this->model->url;
        $templateData['target'] = $this->model->target;
        $templateData['linkTitle'] = $this->model->linkTitle ?: $this->model->url;
        $templateData['titleText'] = $this->model->titleText;

        $table = $this->model::getTable();
        $size = ('tl_module' === $table ? 'imgSize' : 'size');
        $templateData['addImage'] = false;
        $imageData = $this->modalConfig->getImageData($this->model->singleSRC, $this->model->{$size}, (bool) $this->model->fullsize, $this->model->url);
        if (null !== $imageData) {
            $templateData['addImage'] = true;
            $templateData['imageData'] = $imageData;
        }

        $this->template->setData($templateData);

        return $this->template->getResponse();
    }
}
