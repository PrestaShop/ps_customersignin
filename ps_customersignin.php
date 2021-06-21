<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Ps_CustomerSignIn extends Module implements WidgetInterface
{
    /**
     * @var string Name of the module running on PS 1.6.x. Used for data migration.
     */
    const PS_16_EQUIVALENT_MODULE = 'blockuserinfo';

    private $templateFile;

    public function __construct()
    {
        $this->name = 'ps_customersignin';
        $this->author = 'PrestaShop';
        $this->version = '2.0.4';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->getTranslator()->trans('Customer "Sign in" link', [], 'Modules.Customersignin.Admin');
        $this->description = $this->getTranslator()->trans('Make your customers feel at home on your store, invite them to sign in!', [], 'Modules.Customersignin.Admin');
        $this->ps_versions_compliancy = ['min' => '1.7.1.0', 'max' => _PS_VERSION_];

        $this->templateFile = 'module:ps_customersignin/ps_customersignin.tpl';
    }

    public function install()
    {
        // Migrate data from 1.6 equivalent module (if applicable), then uninstall
        if (Module::isInstalled(self::PS_16_EQUIVALENT_MODULE)) {
            $oldModule = Module::getInstanceByName(self::PS_16_EQUIVALENT_MODULE);
            if ($oldModule) {
                $oldModule->uninstall();
            }
        }

        return parent::install()
            && $this->registerHook('displayNav2');
    }

    public function getWidgetVariables($hookName, array $configuration)
    {

        $logged = $this->context->customer->isLogged();
        $link = $this->context->link;
  
        // Add links to sections on my account page
        $my_account_urls = array(
            2 => array(
                'title' => $this->trans('Orders', array(), 'Admin.Global'),
                'url' => $link->getPageLink('history', true),
            ),
            3 => array(
                'title' => $this->trans('Credit slips', array(), 'Modules.Customeraccountlinks.Admin'),
                'url' => $link->getPageLink('order-slip', true),
            ),
            4 => array(
                'title' => $this->trans('Addresses', array(), 'Shop.Theme.Global'),
                'url' => $link->getPageLink('addresses', true),
            ),
            0 => array(
                'title' => $this->trans('Personal info', array(), 'Modules.Customeraccountlinks.Admin'),
                'url' => $link->getPageLink('identity', true),
            ),
        );

        // Add returns, if enabled
        if ((int)Configuration::get('PS_ORDER_RETURN')) {
            $my_account_urls[1] = array(
                'title' => $this->trans('Merchandise returns', array(), 'Modules.Customeraccountlinks.Admin'),
                'url' => $link->getPageLink('order-follow', true),
            );
        }

        // Add vouchers, if enabled
        if (CartRule::isFeatureActive()) {
            $my_account_urls[5] = array(
                'title' => $this->trans('Vouchers', array(), 'Shop.Theme.Customeraccount'),
                'url' => $link->getPageLink('discount', true),
            );
        }

        // Sort Account links base in his index
        ksort($my_account_urls);
        
        // If customer is logged, we want to get his name
        if ($logged) {
            $customerName = $this->getTranslator()->trans(
                '%firstname% %lastname%',
                [
                    '%firstname%' => $this->context->customer->firstname,
                    '%lastname%' => $this->context->customer->lastname,
                ],
                'Modules.Customersignin.Admin'
            );
        } else {
            $customerName = '';
        }

        return [
            /*
            * @deprecated
            */            
            'logged' => $logged,
            'my_account_urls' => $my_account_urls,
            'customerName' => $customerName,
            /*
            * @deprecated
            */
            'logout_url' => $link->getPageLink('index', true, null, 'mylogout'),
            /*
            * @deprecated
            */
            'my_account_url' => $link->getPageLink('my-account', true),
        ];
    }

    public function renderWidget($hookName, array $configuration)
    {
        $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));

        return $this->fetch($this->templateFile);
    }
}
