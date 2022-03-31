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

use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Model;
use Contao\ModuleModel;
use Sowieso\ModalBundle\Modal\ContentType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsCallback('tl_content', 'config.onload', 'onLoadDataContainer')]
#[AsCallback('tl_module', 'config.onload', 'onLoadDataContainer')]
class LoadDataContainerCallback
{
    private Request $request;

    public function __construct(
        RequestStack $requestStack,
        private ContentType $contentType,
    ) {
        $request = $requestStack->getCurrentRequest();
        if (null === $request) {
            throw new \Exception('Missing Request object in ' . __CLASS__);
        }
        $this->request = $request;
    }

    public function onLoadDataContainer(?DataContainer $dataContainer = null): void
    {
        /*
         * Check if a DataContainer instance is given.
         * Check if the DataContainer instance has an ID.
         * Check if we are currently in edit-mode.
         */
        if (null === $dataContainer || !$dataContainer->id || 'edit' !== $this->request->query->get('act')) {
            return;
        }

        /** @var ContentModel|ModuleModel $modelClass */
        $modelClass = Model::getClassFromTable($dataContainer->table);

        /** @var ContentModel|ModuleModel|null $model */
        $model = $modelClass::findById($dataContainer->id);

        if (null === $model || 'sowiesoModal' !== $model->type) {
            return;
        }

        $contentType = $model->__get('modal_content_type');
        $textMandatory = match ($contentType) {
            $this->contentType::OPTION_IMAGE, $this->contentType::OPTION_HTML => false,
            default => true,
        };
        // text field should not be mandatory
        $GLOBALS['TL_DCA'][$dataContainer->table]['fields']['text']['eval']['mandatory'] = $textMandatory;

        // image field should not be mandatory
        $GLOBALS['TL_DCA'][$dataContainer->table]['fields']['singleSRC']['eval']['mandatory'] = ($this->contentType::OPTION_IMAGE === $contentType);

        // url field should not be mandatory
        $GLOBALS['TL_DCA'][$dataContainer->table]['fields']['url']['eval']['mandatory'] = false;

        // url field should always be a DCA picker
        $GLOBALS['TL_DCA'][$dataContainer->table]['fields']['url']['eval']['dcaPicker'] = true;
    }
}
