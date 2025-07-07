<?php

if (!defined('_PS_VERSION_')) exit;

class OpenaiPrompts extends Module
{
    public function __construct()
    {
        $this->name = 'openaiprompts';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Sergio';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->admin_tab[] = array('classname' => 'AdminOpenaiPrompts', 'parent' => 'AdminTools', 'displayname' => 'OpenAI Prompts');

        $this->displayName = $this->l('Gestor de Prompts OpenAI');

        $this->description = $this->l('Gestiona y centraliza los prompts utilizados por otros módulos.');

        $this->confirmUninstall = $this->l('¿Estás seguro de que quieres desinstalar el gestor de prompts?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.7');
    }

    public function install()
    {       
        //añadimos link en pestaña de productos llamando a installTab
        foreach ($this->admin_tab as $tab) {
            $this->installTab($tab['classname'], $tab['parent'], $this->name, $tab['displayname']);
        }

        return parent::install() 
            && $this->createTables()            
            && $this->registerHook('actionAfterPromptUpdate');;
    }

    public function uninstall()
    {       
        //desinstalar el link de la pestaña productos llamando a unistallTab
        foreach ($this->admin_tab as $tab) {
            $this->unInstallTab($tab['classname']);
        }            

        return parent::uninstall();
    }

    protected function installTab($classname = false, $parent = false, $module = false, $displayname = false) {
        // Si ya existe la pestaña, no la vuelve a crear
        if (Tab::getIdFromClassName($classname)) {
            return true;
        }

        if (!$classname)
            return true;

        $tab = new Tab();
        $tab->class_name = $classname;
        if ($parent)
            if (!is_int($parent))
                $tab->id_parent = (int) Tab::getIdFromClassName($parent);
            else
                $tab->id_parent = (int) $parent;
        if (!$module)
            $module = $this->name;
        $tab->module = $module;
        $tab->active = true;
        if (!$displayname)
            $displayname = $this->displayName;
        $tab->name[(int) (Configuration::get('PS_LANG_DEFAULT'))] = $displayname;

        if (!$tab->add())
            return false;

        return true;
    }
   
    protected function unInstallTab($classname = false) {
        if (!$classname)
            return true;

        $idTab = Tab::getIdFromClassName($classname);
        if ($idTab) {
            $tab = new Tab($idTab);
            return $tab->delete();
            ;
        }
        return true;
    }

    private function createTables()
    {
        $tabla = _DB_PREFIX_ . 'openai_prompts';

        // Verifica si la tabla ya existe
        $existe = Db::getInstance()->executeS("SHOW TABLES LIKE '".pSQL($tabla)."'");

        if ($existe) {
            return true; // No hace falta crearla
        }

        $sql = "CREATE TABLE `$tabla` (
            `id_openai_prompt` INT(11) NOT NULL AUTO_INCREMENT,
            `nombre` VARCHAR(100) NOT NULL,
            `grupo` VARCHAR(50) NOT NULL,
            `prompt` TEXT NOT NULL,
            `modelo` VARCHAR(50) DEFAULT 'gpt-4o',
            `temperature` FLOAT DEFAULT 0.7,
            `max_tokens` INT DEFAULT 1000,
            `active` TINYINT(1) DEFAULT 1,
            `comentarios` TEXT,
            `version_anterior` TEXT,
            `usuario_ultima_modificacion` VARCHAR(128),
            `fecha_ultima_modificacion` DATETIME,
            `deleted` TINYINT(1) NOT NULL DEFAULT 0,
            `usuario_deleted` VARCHAR(128),
            `fecha_deleted` DATETIME,
            `usuario_creacion` VARCHAR(128),
            `date_add` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `date_upd` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_openai_prompt`),
            UNIQUE KEY `unico_nombre_grupo` (`nombre`, `grupo`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        return Db::getInstance()->execute($sql);
    }

    public function getContent()
    {
        return '<div class="alert alert-info">Próximamente podrás editar los prompts desde aquí.</div>';
    }

    public function hookActionAfterPromptUpdate($params)
    {
        
        // Esto permite que otros módulos escuchen este hook si están interesados.
    }
}
