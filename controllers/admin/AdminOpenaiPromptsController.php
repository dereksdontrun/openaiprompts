<?php
if (!defined('_PS_VERSION_'))
    exit;

require_once _PS_MODULE_DIR_.'openaiprompts/classes/OpenAIPrompt.php';
require_once _PS_MODULE_DIR_.'openaiprompts/classes/PromptManager.php';

class AdminOpenaiPromptsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->table = 'openai_prompts';
        $this->className = 'OpenAIPrompt';
        $this->identifier = 'id_openai_prompt'; //prestashop cree que el id es id_openai_prompts por eso hay que indicarle que el id principal de la tabala es id_openai_prompt
        $this->lang = false;
        $this->bootstrap = true;
        $this->context = Context::getContext();

        // Añadimos mensaje de confirmación personalizado
        $this->_conf[4] = $this->l('Prompt duplicado correctamente.');

        parent::__construct();

        $this->fields_list = [
            'id_openai_prompt' => [
                'title' => 'ID',
                'width' => 50,
                'type' => 'int',
                'orderby' => true,
                'search' => true,
            ],
            'grupo' => [
                'title' => 'Grupo',
                'type' => 'text',
                'orderby' => true,
                'search' => true,
            ],
            'nombre' => [
                'title' => 'Nombre del Prompt',
                'type' => 'text',
                'orderby' => true,
                'search' => true,
            ],
            'modelo' => [
                'title' => 'Modelo',
                'type' => 'text',
                'orderby' => true,
                'search' => true,
            ],
            'temperature' => [
                'title' => 'Temp',
                'type' => 'float',
            ],
            'max_tokens' => [
                'title' => 'Tokens',
                'type' => 'int',
            ],
            'active' => [
                'title' => 'Activo',
                'type' => 'bool',
                'active' => 'status',
                'orderby' => true,
                'search' => true,
            ],
            'fecha_ultima_modificacion' => [
                'title' => 'Modificado',
                'type' => 'datetime',
                'orderby' => true,
                'search' => true,
            ],
            'usuario_ultima_modificacion' => [
                'title' => 'Autor Modificación',
                'type' => 'text',
                'orderby' => true,
                'search' => true,
            ],
            'usuario_creacion' => [
                'title' => 'Creado por',
                'type' => 'text',
                'orderby' => true,
                'search' => true,
            ],
            'date_add' => [
                'title' => 'Fecha creación', 
                'type' => 'datetime'
            ],
        ];

        $this->_where = 'AND a.deleted = 0';

        $this->_orderBy = 'fecha_ultima_modificacion';
        $this->_orderWay = 'DESC';

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Eliminar seleccionados'),
                'confirm' => $this->l('¿Eliminar los elementos seleccionados?')
            ]
        ];        

        $this->addRowAction('edit');
        $this->addRowAction('duplicate');
        // $this->addRowAction('custom_duplicate');
        $this->addRowAction('delete');        
    }

    // public function displayCustomDuplicateLink($token = null, $id, $name = null)
    // {
    //     $token = $token ?: $this->token;

    //     $href = self::$currentIndex .
    //         '&duplicateprompt=1&id_openai_prompt=' . (int)$id .
    //         '&token=' . $token;

    //     return '<a href="' . $href . '" title="' . $this->l('Duplicar') . '">
    //         <i class="icon-copy"></i> ' . $this->l('Duplicar') . '</a>';
    // }

    //como el proceso bulk delete de Prestashop elimina por defecto la entrada de la base de datos y nosotros solo queremos marcar delete = 1 para que no salga en la lista, lo pisamos aquí.
    public function processBulkDelete()
    {
        $ids = Tools::getValue($this->table . 'Box');

        if (!is_array($ids)) {
            $this->errors[] = $this->l('No hay prompts seleccionados para eliminar.');
            return;
        }

        $usuario = $this->context->employee->firstname . ' ' . $this->context->employee->lastname;
        $fecha = date('Y-m-d H:i:s');

        foreach ($ids as $id) {
            $prompt = new OpenAIPrompt((int)$id);
            if (Validate::isLoadedObject($prompt)) {
                $log = date('Y-m-d H:i:s') . " | Prompt eliminado (ID $id):\n";
                $log .= "Grupo: {$prompt->grupo} | Nombre: {$prompt->nombre}\n";
                $log .= "Contenido:\n" . $prompt->prompt . "\n\n";
                file_put_contents(_PS_MODULE_DIR_ . 'openaiprompts/logs/prompt_delete_log.txt', $log, FILE_APPEND);

                $prompt->deleted = 1;
                $prompt->usuario_deleted = $usuario;
                $prompt->fecha_deleted = $fecha;
                $prompt->update();
            }
        }

        $this->confirmations[] = $this->l('Los prompts seleccionados han sido marcados como eliminados.');
    }


    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => 'Editar Prompt',
                'icon' => 'icon-cogs'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => 'Grupo',
                    'name' => 'grupo',
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => 'Nombre',
                    'name' => 'nombre',
                    'required' => true
                ],
                [
                    'type' => 'select',
                    'label' => 'Modelo',
                    'name' => 'modelo',
                    'options' => [
                        'query' => [
                            ['id' => 'gpt-4o', 'name' => 'GPT-4o'],
                            ['id' => 'gpt-4', 'name' => 'GPT-4'],
                            ['id' => 'gpt-3.5-turbo', 'name' => 'GPT-3.5 Turbo']
                        ],
                        'id' => 'id',
                        'name' => 'name'
                    ]
                ],
                [
                    'type' => 'text',
                    'label' => 'Temperature',
                    'name' => 'temperature'
                ],
                [
                    'type' => 'text',
                    'label' => 'Max Tokens',
                    'name' => 'max_tokens'
                ],
                [
                    'type' => 'textarea',
                    'label' => 'Texto del Prompt',
                    'name' => 'prompt',
                    'rows' => 10,
                    'cols' => 60,
                    'autoload_rte' => true
                ],
                [
                    'type' => 'textarea',
                    'label' => 'Comentarios',
                    'name' => 'comentarios',
                    'rows' => 4
                ],
                [
                    'type' => 'switch',
                    'label' => 'Activo',
                    'name' => 'active',
                    'values' => [
                        ['id' => 'on', 'value' => 1, 'label' => 'Sí'],
                        ['id' => 'off', 'value' => 0, 'label' => 'No']
                    ]
                ],
                [
                    'type' => 'textarea',
                    'label' => 'Versión anterior',
                    'name' => 'version_anterior',
                    'readonly' => true
                ],
                [
                    'type' => 'text',
                    'label' => 'Última modificación por',
                    'name' => 'usuario_ultima_modificacion',
                    'readonly' => true
                ],
                [
                    'type' => 'text',
                    'label' => 'Creado por',
                    'name' => 'usuario_creacion',
                    'readonly' => true
                ],
                [
                    'type' => 'text',
                    'label' => 'Fecha de creación',
                    'name' => 'date_add',
                    'readonly' => true
                ]
            ],
            'submit' => [
                'title' => 'Guardar',
                'class' => 'btn btn-default pull-right'
            ]
        ];       

        $form = parent::renderForm();

        // Solo mostramos el botón de duplicar dentro de edición, si estamos editando un prompt existente, Botón HTML manual
        $id_prompt = (int)Tools::getValue('id_openai_prompt');
        if ($id_prompt) {
            $duplicarUrl = self::$currentIndex.'&duplicateprompt=1&id_openai_prompt='.$id_prompt.'&token='.$this->token;

            $form .= '
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon-copy"></i> '.$this->l('Duplicar este Prompt').'
                </div>
                <div class="form-wrapper">
                    <a href="'.$duplicarUrl.'" class="btn btn-default">
                        <i class="icon-copy"></i> '.$this->l('Duplicar este Prompt').'
                    </a>
                </div>
            </div>';
        }
        
        return $form;
    }

    public function postProcess()
    {
        //duplicar desde pantalla de formulario
        if (Tools::isSubmit('duplicateprompt')) {
            return $this->processDuplicate();
        }

        // Detectamos si viene desde el botón "Duplicar" de la lista
        if (Tools::isSubmit('duplicateopenai_prompts')) {
            return $this->processDuplicate();
        }

        return parent::postProcess();
    }

    
    public function processDuplicate()
    {
        $id = (int)Tools::getValue('id_openai_prompt');
        if (!$id) {
            $this->errors[] = 'ID de prompt no válido para duplicar.';
            return false;
        }

        try {           
            PromptManager::duplicarPrompt($id, $this->context->employee->firstname.' '.$this->context->employee->lastname);

            Tools::redirectAdmin(self::$currentIndex.'&conf=4&token='.$this->token);

        } catch (Exception $e) {
            $this->errors[] = 'Error al duplicar el prompt: ' . $e->getMessage();
        }
    }

    public function processUpdate()
    {
        $id = (int)Tools::getValue('id_openai_prompt');
        $prompt = new OpenAIPrompt($id);
        $nuevoPrompt = Tools::getValue('prompt');

        if ($prompt->prompt !== $nuevoPrompt) {
            $prompt->version_anterior = $prompt->prompt;
        }

        $prompt->prompt = $nuevoPrompt;
        $prompt->grupo = Tools::getValue('grupo');
        $prompt->nombre = Tools::getValue('nombre');
        $prompt->modelo = Tools::getValue('modelo');
        $prompt->temperature = (float)Tools::getValue('temperature');
        $prompt->max_tokens = (int)Tools::getValue('max_tokens');
        $prompt->comentarios = Tools::getValue('comentarios');
        $prompt->active = (int)Tools::getValue('active');
        $prompt->usuario_ultima_modificacion = $this->context->employee->firstname.' '.$this->context->employee->lastname;
        $prompt->fecha_ultima_modificacion = date('Y-m-d H:i:s');

        return $prompt->update();
    }

    public function processAdd()
    {       
        $_POST['usuario_creacion'] = $this->context->employee->firstname.' '.$this->context->employee->lastname;
        $_POST['usuario_ultima_modificacion'] = $this->context->employee->firstname.' '.$this->context->employee->lastname;
        $_POST['fecha_ultima_modificacion'] = date('Y-m-d H:i:s');

        return parent::processAdd();
    }

    //añadimos que si está activo no permita eliminar para evitar errores
    public function processDelete()
    {
        $id = (int)Tools::getValue('id_openai_prompt');
        $prompt = new OpenAIPrompt($id);

        if (!Validate::isLoadedObject($prompt)) {
            $this->errors[] = 'Prompt no encontrado.';
            return false;
        }

        if ($prompt->active) {
            $this->errors[] = '❌ No se puede eliminar un prompt activo. Por favor, desactívalo primero.';
            return false;
        }

        $log = date('Y-m-d H:i:s') . " | Prompt eliminado (ID $id):\n";
        $log .= "Grupo: {$prompt->grupo} | Nombre: {$prompt->nombre}\n";
        $log .= "Contenido:\n" . $prompt->prompt . "\n\n";
        file_put_contents(_PS_MODULE_DIR_ . 'openaiprompts/logs/prompt_delete_log.txt', $log, FILE_APPEND);

        $prompt->deleted = 1;
        $prompt->usuario_deleted = $this->context->employee->firstname.' '.$this->context->employee->lastname;
        $prompt->fecha_deleted = date('Y-m-d H:i:s');
        $prompt->usuario_ultima_modificacion = $this->context->employee->firstname.' '.$this->context->employee->lastname;
        $prompt->fecha_ultima_modificacion = date('Y-m-d H:i:s');

        return $prompt->update();
    }



}
