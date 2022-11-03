<?php declare(strict_types=1);

/**
* To plugin Bootstrap file.
*
* Copyright (C) BrandCrock GmbH. All rights reserved
*
* If you have found this script useful a small
* recommendation as well as a comment on our
* home page(https://brandcrock.com/)
* would be greatly appreciated.
*
* @author BrandCrock GmbH
* @package BrandCrockMegaMenu
*/
namespace Brandcrock\BrandCrockMegaMenu;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class BrandCrockMegaMenu extends Plugin
{

	public function Install(InstallContext $context): void
    {
		parent::Install($context);
	}

	public function uninstall(UninstallContext $context): void
    {
		parent::uninstall($context);

	}
}
