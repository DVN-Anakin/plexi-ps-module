<?php
/**
* 2007-2017 PrestaShop.
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Plexi extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'plexi';
        $this->tab = 'emailing';
        $this->version = '1.0.0';
        $this->author = 'Anakin';
        $this->need_instance = 0;

        /*
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('plexi');
        $this->description = $this->l('Flexibee-Prestashop komunikace');
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update.
     */
    public function install()
    {
        Configuration::updateValue('PLEXI_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('actionValidateOrder');
    }

    public function uninstall()
    {
        Configuration::deleteByName('PLEXI_LIVE_MODE');

        return parent::uninstall();
    }

    /**
     * Load the configuration form.
     */
    public function getContent()
    {
        /*
         * If values have been submitted in the form, process.
         */
        if (((bool) Tools::isSubmit('submitPlexiModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitPlexiModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
            'legend' => array(
            'title' => $this->l('Settings'),
            'icon' => 'icon-cogs',
            ),
            'input' => array(
            array(
            'type' => 'switch',
            'label' => $this->l('Live mode'),
            'name' => 'PLEXI_LIVE_MODE',
            'is_bool' => true,
            'desc' => $this->l('Use this module in live mode'),
            'values' => array(
            array(
            'id' => 'active_on',
            'value' => true,
            'label' => $this->l('Enabled'),
            ),
            array(
            'id' => 'active_off',
            'value' => false,
            'label' => $this->l('Disabled'),
            ),
            ),
            ),
            array(
            'col' => 3,
            'type' => 'text',
            'prefix' => '<i class="icon icon-envelope"></i>',
            'desc' => $this->l('Enter a valid email address'),
            'name' => 'PLEXI_ACCOUNT_EMAIL',
            'label' => $this->l('Email'),
            ),
            array(
            'type' => 'password',
            'name' => 'PLEXI_ACCOUNT_PASSWORD',
            'label' => $this->l('Password'),
            ),
            ),
            'submit' => array(
            'title' => $this->l('Save'),
            ),
        ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'PLEXI_LIVE_MODE' => Configuration::get('PLEXI_LIVE_MODE', true),
            'PLEXI_ACCOUNT_EMAIL' => Configuration::get('PLEXI_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'PLEXI_ACCOUNT_PASSWORD' => Configuration::get('PLEXI_ACCOUNT_PASSWORD', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookActionValidateOrder($params)
    {
        $customer = $params['customer'];
        $order = $params['order'];
        $invoice = new Address((int) $order->id_address_invoice);
        $carrier = new Carrier((int) $order->id_carrier);
        $context = Context::getContext();
        $id_lang = (int) $context->language->id;
        $id_shop = (int) $context->shop->id;
        $configuration = Configuration::getMultiple(
            array(
            'PS_SHOP_EMAIL',
            'PS_MAIL_METHOD',
            'PS_MAIL_SERVER',
            'PS_MAIL_USER',
            'PS_MAIL_PASSWD',
            'PS_SHOP_NAME',
            'PS_MAIL_COLOR',
            ),
            $id_lang,
            null,
            $id_shop
        );

        $products = $params['order']->getProducts();
        $nazevProduktu = array_column($products, 'product_name');
        $pocetProduktu = array_column($products, 'product_quantity');
        $cenaProduktu = array_column($products, 'product_price');
        $myArray = array();
        //$faktura_vydana_polozka = array();
        $sumPolozky = array();
        for ($vn = 0; $vn < count($products); ++$vn) {
            array_push($myArray, $pocetProduktu[$vn].'x '.$nazevProduktu[$vn]);
            $faktura_vydana_polozka = array(
                'ucetni' => 'true',
                'szbDph' => '21.0',
                'zaokrJakK' => 'code:matematicky',
                'zaokrNaK' => 'code:setiny',
                'nazev' => $nazevProduktu[$vn],
                'mnozMj' => $pocetProduktu[$vn],
                'sumCelkem' => $cenaProduktu[$vn] * $pocetProduktu[$vn],
            );
            array_push($sumPolozky, $faktura_vydana_polozka);
            //$sumPolozky = array('faktura-vydana-polozka' => $faktura_vydana_polozka);
            //array_push($sumPolozky,str_replace("faktura_vydana_polozka", "faktura-vydana-polozka", serialize($faktura_vydana_polozka)));
        }
        $celaObjednavka = implode(' + ', $myArray);

        $host = 'xxx';   //url adresa flexibee online rozhraní
        $firma = 'xxx';   //název eshopu ve flexibee rozhraní

        $TfirmaZakaznika = $invoice->company;
        if ($TfirmaZakaznika == '') {
            $TfirmaZakaznika = $customer->firstname.' '.$customer->lastname;
        }
        $TemailZakaznika = $customer->email;
        $Tcena = $order->total_paid;
        $TcisloObjednavky = $order->id;
        $Tulice = $invoice->address1;
        $Tmesto = $invoice->city;
        $Tpsc = $invoice->postcode;

        $doprava = (($carrier->name == '0') ? $configuration['PS_SHOP_NAME'] : $carrier->name);
        if ($doprava == 'Balík Do ruky') {
            $Tdoprava = 'code:ČP';
        } elseif ($doprava == 'Balík Na poštu') {
            $Tdoprava = 'code:ČP';
        } elseif ($doprava == 'xxx') {     // xxx je v Prestashopu nastavený způsob vyzvednutí v sídle firmy
            $Tdoprava = 'code:OSOBNĚ';
        } elseif ($doprava == 'Osobní odběr - Zásilkovna') {
            $Tdoprava = 'code:ZÁSILKOVNA';
        } else {
            $Tdoprava = 'code:DPP';
        }

        $uhrada = Tools::substr($order->payment, 0, 32);
        if ($uhrada == 'Bankovní převod') {
            $Tuhrada = 'code:PREVOD';
        } elseif ($uhrada == 'Platba v hotovosti / dobírka') {
            $Tuhrada = 'code:DOBIRKA';
        } else {
            $Tuhrada = 'code:PAYPAL';
        }

        $popis = $celaObjednavka;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_USERPWD, 'xxx:yyy');    //xxx je username a yyy je heslo
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, true);

        $Tid = $this->send($ch, $host, $firma, $Tuhrada, $Tdoprava, $popis, $Tcena, $TcisloObjednavky, $TfirmaZakaznika, $Tulice, $Tmesto, $Tpsc, $sumPolozky);
        $this->sendEmail($ch, $host, $firma, $Tid, $TemailZakaznika);

        curl_close($ch);
    }

    public function send($ch, $host, $firma, $Tuhrada, $Tdoprava, $popis, $Tcena, $TcisloObjednavky, $TfirmaZakaznika, $Tulice, $Tmesto, $Tpsc, $sumPolozky)
    {
        curl_setopt($ch, CURLOPT_URL, $host.'/c/'.$firma.'/faktura-vydana.json');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        $faktura = array(
            'winstrom' => array(
                'faktura-vydana' => array(
                    'typDokl' => 'code:FAKTURA',
                    'moje' => 'code:WINSTROM',
                    'formaUhradyCis' => $Tuhrada,
                    'formaDopravy' => $Tdoprava,
                    'popis' => $popis,
                    'sumDphZakl' => $Tcena,
                    'bezPolozek' => 'true',
                    'cisObj' => $TcisloObjednavky,
                    'nazFirmy' => $TfirmaZakaznika,
                    'ulice' => $Tulice,
                    'mesto' => $Tmesto,
                    'psc' => $Tpsc,
                    'polozkyFaktury' => array(
                        'faktura-vydana-polozka' => $sumPolozky,
                    ),
                ),
            ),
        );

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($faktura));
        $output = curl_exec($ch);

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200 && curl_getinfo($ch, CURLINFO_HTTP_CODE) != 201) {
            printf('Při operaci nastala chyba (HTTP %d): %sn', curl_getinfo($ch, CURLINFO_HTTP_CODE), $output);
        } else {
            $Tid = $this->get_id($output);
            printf('Uspesne (1) s ID: %s <br/>', $Tid);

            return $Tid;
        }
    }

    public function get_id($output)
    {
        $Tid = 0;
        $jsonIterator = new RecursiveIteratorIterator(new RecursiveArrayIterator(json_decode($output, true)), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($jsonIterator as $key => $val) {
            if (!is_array($val)) {
                if ('id' == $key) {
                    $Tid = $val;
                }
            }
        }

        return $Tid;
    }

    public function sendEmail($ch, $host, $firma, $Tid, $TemailZakaznika)
    {
        curl_setopt($ch, CURLOPT_URL, $host.'/c/'.$firma.'/faktura-vydana/'.$Tid.'/odeslani-dokladu.xml');
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'to='.$TemailZakaznika.'&subject=Faktura');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');

        $output = curl_exec($ch);

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200 && curl_getinfo($ch, CURLINFO_HTTP_CODE) != 201) {
            printf('Při operaci nastala chyba (HTTP %d): %sn', curl_getinfo($ch, CURLINFO_HTTP_CODE), $output);
        } else {
            printf('Uspesne (2)');
        }
    }
}
