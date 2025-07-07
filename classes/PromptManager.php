<?php

require_once dirname(__FILE__) . '/OpenAIPrompt.php';

class PromptManager
{
    public static function obtenerPrompt($grupo, $nombre)
    {
        $sql = "
            SELECT prompt, modelo, temperature, max_tokens
            FROM "._DB_PREFIX_."openai_prompts
            WHERE grupo = '".pSQL($grupo)."'
              AND nombre = '".pSQL($nombre)."'
              AND active = 1
              AND deleted = 0
            LIMIT 1
        ";

        $fila = Db::getInstance()->getRow($sql);

        if (!$fila) {
            throw new Exception("No se encontró el prompt '$nombre' en el grupo '$grupo'.");
        }

        return $fila;
    }

    public static function obtenerPromptsDeGrupo($grupo)
    {
        return OpenAIPrompt::getByGrupo($grupo);
    }

    public static function obtenerPromptPorNombre($nombre)
    {
        return OpenAIPrompt::getByNombre($nombre);
    }

    public static function actualizarPrompt($id_prompt, $nuevoPrompt, $usuario)
    {
        if (trim($nuevoPrompt) === '') {
            throw new Exception("El nuevo prompt no puede estar vacío.");
        }

        $actual = Db::getInstance()->getRow(
            'SELECT prompt FROM '._DB_PREFIX_.'openai_prompts WHERE id_openai_prompt = '.(int)$id_prompt
        );

        if (!$actual) {
            throw new Exception("Prompt no encontrado con ID $id_prompt");
        }

        return Db::getInstance()->update('openai_prompts', [
            'version_anterior' => pSQL($actual['prompt']),
            'prompt' => pSQL($nuevoPrompt),
            'usuario_ultima_modificacion' => pSQL($usuario),
            'fecha_ultima_modificacion' => date('Y-m-d H:i:s'),
        ], 'id_openai_prompt = '.(int)$id_prompt);
    }

    public static function crearPrompt($grupo, $nombre, $prompt, $modelo = 'gpt-4o', $usuario = 'sistema', $temperature = 0.7, $max_tokens = 1000, $comentarios = '')
    {
        $existe = Db::getInstance()->getValue("
            SELECT COUNT(*) FROM "._DB_PREFIX_."openai_prompts
            WHERE grupo = '".pSQL($grupo)."'
              AND nombre = '".pSQL($nombre)."'              
              AND deleted = 0
        ");

        if ($existe) {
            throw new Exception("Ya existe un prompt activo con nombre '$nombre' en el grupo '$grupo'.");
        }

        return Db::getInstance()->insert('openai_prompts', [
            'grupo' => pSQL($grupo),
            'nombre' => pSQL($nombre),
            'prompt' => pSQL($prompt),
            'modelo' => pSQL($modelo),
            'temperature' => (float)$temperature,
            'max_tokens' => (int)$max_tokens,
            'comentarios' => pSQL($comentarios),
            'usuario_creacion' => pSQL($usuario),            
            'date_add' => date('Y-m-d H:i:s'),            
            'active' => 1,
            'deleted' => 0
        ]);
    }

    public static function duplicarPrompt($id_prompt, $usuario = 'sistema')
    {
        return OpenAIPrompt::duplicarPrompt($id_prompt, $usuario);
    }


    public static function lanzarHookActualizacion($id_prompt)
    {
        Hook::exec('actionAfterPromptUpdate', [
            'id_prompt' => $id_prompt
        ]);
    }
}