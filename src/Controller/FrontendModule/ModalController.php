<?php

declare(strict_types=1);

/*
 * This file is part of SowieSo contao-modal-bundle
 *
 * @copyright  Copyright (c) 2022, Ideenwerkstatt Sowieso GmbH & Co. KG
 * @author     Sowieso GmbH & Co. KG <https://sowieso.team>
 * @link       https://github.com/sowieso-web/contao-modal-bundle
 */

namespace Sowieso\ModalBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\Template;
use Sowieso\ModalBundle\Modal\Modal;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule('sowiesoModal', 'application', 'mod_modal')]
class ModalController extends AbstractFrontendModuleController
{
    public function __construct(
        private ScopeMatcher $scopeMatcher,
        private Modal $modal,
    ) {
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): ?Response
    {
        $templateData = $template->getData();
        $templateData['showModal'] = false;
        $template->setData($templateData);

        // Only handle frontend requests
        if (false === $this->scopeMatcher->isFrontendRequest($request)) {
            return null;
        }

        // Check if the current page is excluded
        $excluded = StringUtil::deserialize($model->modal_excludedPages, true);
        if ($this->modal->isPageExcluded($this->getPageModel(), $excluded)) {
            return null;
        }

        // Check if we are visiting the target of the modal
        if ($this->modal->isModalPage($this->getPageModel(), $model->url)) {
            return null;
        }

        $templateData = $template->getData();

        $templateData['showModal'] = true;
        $templateData['modalId'] = $this->modal->getModalId($model);

        $headlineData = StringUtil::deserialize($model->headline, true);
        $templateData['headline'] = $headlineData['value'] ?? null;
        $templateData['hl'] = $headlineData['unit'] ?? null;

        $templateData['text'] = $model->text;
        $templateData['html'] = $model->html;
        $templateData['url'] = $model->url;
        $templateData['target'] = $model->target;
        $templateData['linkTitle'] = $model->linkTitle ?: $model->url;
        $templateData['titleText'] = $model->titleText;

        $templateData['addImage'] = false;
        $imageData = $this->modal->getImageData($model->singleSRC, $model->imgSize, (bool) $model->fullsize, $model->url);
        if (null !== $imageData) {
            $templateData['addImage'] = true;
            $templateData['imageData'] = $imageData;
        }

        $template->setData($templateData);

        return $template->getResponse();
    }
}
