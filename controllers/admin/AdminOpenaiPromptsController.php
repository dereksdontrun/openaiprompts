<?php

class AdminOpenaiPromptsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->table = 'openai_prompts';
        $this->className = 'OpenAIPrompt';
        $this->lang = false;
        $this->bootstrap = true;
        $this->context = Context::getContext();

        // Añadimos mensaje de confirmación personalizado
        $this->_conf[4] = $this->l('Prompt duplicado correctamente.');

        parent::__construct();

        $this->fields_list = [
            'id_openai_prompt' => ['title' => 'ID', 'width' => 50],
            'grupo' => ['title' => 'Grupo'],
            'nombre' => ['title' => 'Nombre del Prompt'],
            'modelo' => ['title' => 'Modelo'],
            'temperature' => ['title' => 'Temp'],
            'max_tokens' => ['title' => 'Tokens'],
            'activo' => [
                'title' => 'Activo',
                'type' => 'bool',
                'active' => 'status',
                'orderby' => false
            ],
            'fecha_ultima_modificacion' => ['title' => 'Modificado', 'type' => 'datetime'],
            'usuario_ultima_modificacion' => ['title' => 'Autor']
        ];        

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Eliminar seleccionados'),
                'confirm' => $this->l('¿Eliminar los elementos seleccionados?')
            ]
        ];        

        $this->addRowAction('duplicate');
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
                    'name' => 'activo',
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
                ]
            ],
            'submit' => [
                'title' => 'Guardar',
                'class' => 'btn btn-default pull-right'
            ]
        ];

        $form = parent::renderForm();

        // Solo mostramos el botón de duplicar si estamos editando un prompt existente
        $id_prompt = (int)Tools::getValue('id_openai_prompt');
        if ($id_prompt) {
            $duplicarUrl = self::$currentIndex.'&duplicateprompt=1&id_openai_prompt='.$id_prompt.'&token='.$this->token;
            $form .= '<a href="'.$duplicarUrl.'" class="btn btn-default" style="margin-top: 10px;"><i class="icon-copy"></i> Duplicar este Prompt</a>';
        }

        return $form;
    }

    public function postProcess()
    {
        if (Tools::isSubmit('duplicateprompt') && $id = (int)Tools::getValue('id_openai_prompt')) {
            try {
                PromptManager::duplicarPrompt($id, $this->context->employee->firstname);
                Tools::redirectAdmin(self::$currentIndex.'&conf=4&token='.$this->token);
            } catch (Exception $e) {
                $this->errors[] = 'Error al duplicar el prompt: ' . $e->getMessage();
            }
        }

        parent::postProcess();
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
        $prompt->activo = (int)Tools::getValue('activo');
        $prompt->usuario_ultima_modificacion = $this->context->employee->firstname;
        $prompt->fecha_ultima_modificacion = date('Y-m-d H:i:s');

        return $prompt->update();
    }

    public function processAdd()
    {
        $_POST['usuario_ultima_modificacion'] = $this->context->employee->firstname;
        $_POST['fecha_ultima_modificacion'] = date('Y-m-d H:i:s');
        return parent::processAdd();
    }

    

}
