<?php

declare(strict_types=1);

/*
 * This file is part of SowieSo contao-modal-bundle
 *
 * @copyright  Copyright (c) 2022, Ideenwerkstatt Sowieso GmbH & Co. KG
 * @author     Sowieso GmbH & Co. KG <https://sowieso.team>
 * @link       https://github.com/sowieso-web/contao-modal-bundle
 */

namespace Sowieso\ModalBundle\Tests;

use Contao\TestCase\ContaoTestCase;
use Sowieso\ModalBundle\ContaoModalBundle;

class ContaoModalBundleTest extends ContaoTestCase
{
    public function testCanBeInstantiated(): void
    {
        $bundle = new ContaoModalBundle();
        $this->assertInstanceOf(ContaoModalBundle::class, $bundle);
    }
}
