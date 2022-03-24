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

use Contao\Model;
use Contao\PageModel;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Controller
{
    public function __construct(
        private Builder $modal,
    ) {
    }

    public function getResponse(Template $template, Model $model, Request $request, ?PageModel $pageModel): ?Response
    {
        // Set no modal as default
        $templateData = $template->getData();
        $templateData['showModal'] = false;

        $modalData = $this->modal
            ->setRequest($request)
            ->setModel($model)
            ->setPage($pageModel)
            ->build()
        ;

        if (null === $modalData) {
            $template->setData($templateData);

            return $template->getResponse();
        }

        // Add the modal data to the template
        $templateData = array_merge($templateData, $modalData);
        $templateData['showModal'] = true;
        $templateData['modalStart'] = (int) $model->__get('modal_start');
        $templateData['modalStop'] = (int) $model->__get('modal_stop');
        $template->setData($templateData);

        $GLOBALS['TL_CSS']['modal'] = 'bundles/contaomodal/css/modal.min.css';
        $GLOBALS['TL_BODY']['modal'] = Template::generateScriptTag('bundles/contaomodal/js/modal.min.js');

        return $template->getResponse();
    }
}
