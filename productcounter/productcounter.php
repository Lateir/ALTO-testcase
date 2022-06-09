<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductCounter extends Module
{
    public function __construct()
    {
        $this->name = 'ProductCounter';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Daniil Povetkin';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.6',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Product Counter');
        $this->description = $this->l('Displays the number of products in a given price range.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (!Configuration::get('PRODUCTCOUNTER_FROM')) {
            $this->warning = $this->l('No variables provided');
        }
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

    return (
            parent::install() 
            && $this->registerHook('Footer')
            && Configuration::updateValue('PRODUCTCOUNTER_FROM', '0')
            && Configuration::updateValue('PRODUCTCOUNTER_TO', '10000')
        ); 
    }

    public function uninstall()
    {
        return (
            parent::uninstall() 
            && Configuration::deleteByName('PRODUCTCOUNTER_FROM')
            && Configuration::deleteByName('PRODUCTCOUNTER_TO')
        );
    }

    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitProductCounterModule')) == true) {
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
        $helper->submit_action = 'submitProductCounterModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
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
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '',
                        'desc' => $this->l('For example: 0'),
                        'name' => 'PRODUCTCOUNTER_FROM',
                        'label' => $this->l('Price from'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '',
                        'desc' => $this->l('For example: 10000'),
                        'name' => 'PRODUCTCOUNTER_TO',
                        'label' => $this->l('Price to'),
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
            'PRODUCTCOUNTER_FROM' => Configuration::get('PRODUCTCOUNTER_FROM', null),
            'PRODUCTCOUNTER_TO' => Configuration::get('PRODUCTCOUNTER_TO', null),
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
     * Get count of products with filters.
     */
    public function getCount()
    {
        $sql = 'SELECT COUNT(p.`id_product`) as total
				FROM `'._DB_PREFIX_.'product` p
				WHERE p.`price` BETWEEN '.Configuration::get('PRODUCTCOUNTER_FROM').' AND '.Configuration::get('PRODUCTCOUNTER_TO');
        $result = Db::getInstance()->getRow($sql);

        return $result['total'];
    }

    public function hookDisplayFooter()
    {
        $this->context->smarty->assign([
            'PRODUCTCOUNTER_FROM' => Configuration::get('PRODUCTCOUNTER_FROM'),
            'PRODUCTCOUNTER_TO' => Configuration::get('PRODUCTCOUNTER_TO'),
            'COUNT_OF_PRODUCTS' => $this->getCount()
        ]);

        return $this->display(__FILE__, 'productcounter.tpl');
    }
}