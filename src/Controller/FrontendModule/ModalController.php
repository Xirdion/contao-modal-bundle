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
use Contao\ModuleModel;
use Contao\Template;
use Sowieso\ModalBundle\Modal\ModalElement;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule('sowiesoModal', 'application', 'mod_modal')]
class ModalController extends AbstractFrontendModuleController
{
    public function __construct(
        private ModalElement $modal,
    ) {
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): ?Response
    {
        $this->modal->setTemplate($template);
        $this->modal->setModel($model);
        $this->modal->setRequest($request);
        $this->modal->setPage($this->getPageModel());

        return $this->modal->generateResponse();
    }
}
