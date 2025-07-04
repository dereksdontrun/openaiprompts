<?php

if (!defined('_PS_VERSION_')) exit;

class OpenaiPrompts extends Module
{
    public function __construct()
    {
        $this->name = 'openaiprompts';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'La Frikilería';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Gestor de Prompts OpenAI');
        $this->description = $this->l('Gestiona y centraliza los prompts utilizados por otros módulos.');

        $this->confirmUninstall = $this->l('¿Estás seguro de que quieres desinstalar el gestor de prompts?');
    }

    public function install()
    {
        return parent::install()
            && $this->createTables()
            && $this->installTab('AdminOpenaiPromptsController', 'AdminTools', 'OpenAI Prompts')
            && $this->registerHook('actionAfterPromptUpdate');;
    }

    public function uninstall()
    {
        return $this->uninstallTab('AdminOpenaiPromptsController')
            && parent::uninstall();
    }

    private function installTab($className, $parentClassName, $tabName)
    {
        $idParent = Tab::getIdFromClassName($parentClassName);
        if (!$idParent) {
            $idParent = 0;
        }

        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $className;
        $tab->name = [];

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $tabName;
        }

        $tab->id_parent = $idParent;
        $tab->module = $this->name;

        return $tab->add();
    }

    private function uninstallTab($className)
    {
        $idTab = Tab::getIdFromClassName($className);
        if (!$idTab) {
            return true;
        }

        $tab = new Tab($idTab);
        return $tab->delete();
    }


    private function createTables()
    {
        $sql = "CREATE TABLE `lafrips_openai_prompts` (
            `id_openai_prompt` INT(11) NOT NULL AUTO_INCREMENT,
            `nombre` VARCHAR(100) NOT NULL,
            `grupo` VARCHAR(50) NOT NULL, -- Ej: 'clasificacion', 'descripcion', etc.
            `prompt` TEXT NOT NULL,
            `modelo` VARCHAR(50) DEFAULT 'gpt-4o',
            `temperature` FLOAT DEFAULT 0.7,
            `max_tokens` INT DEFAULT 1000,
            `activo` TINYINT(1) DEFAULT 1,
            `comentarios` TEXT,
            `version_anterior` TEXT,
            `usuario_ultima_modificacion` VARCHAR(64),
            `fecha_ultima_modificacion` DATETIME,
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
