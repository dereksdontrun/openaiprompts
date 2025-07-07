<?php

class OpenAIPrompt extends ObjectModel
{
    public $id_openai_prompt;
    public $nombre;
    public $grupo;
    public $prompt;
    public $modelo;
    public $temperature;
    public $max_tokens;
    public $active;
    public $comentarios;
    public $version_anterior;
    public $usuario_ultima_modificacion;
    public $fecha_ultima_modificacion;
    public $usuario_creacion;
    public $deleted;
    public $usuario_deleted;
    public $fecha_deleted;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'openai_prompts',
        'primary' => 'id_openai_prompt',
        'fields' => [
            'nombre' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 100],
            'grupo' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 50],
            'prompt' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'required' => true],
            'modelo' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 50],
            'temperature' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'max_tokens' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'comentarios' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'],
            'version_anterior' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'],
            'usuario_creacion' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 128],
            'usuario_ultima_modificacion' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 128],
            'fecha_ultima_modificacion' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'deleted' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'usuario_deleted' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 128],
            'fecha_deleted' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
        ],
        'multilang' => false,
    ];

    public static function getByGrupo($grupo)
    {
        $sql = "SELECT * FROM "._DB_PREFIX_."openai_prompts WHERE grupo = '".pSQL($grupo)."' AND active = 1 AND deleted = 0";
        return Db::getInstance()->executeS($sql);
    }

    public static function getByNombre($nombre)
    {
        $sql = "SELECT * FROM "._DB_PREFIX_."openai_prompts WHERE nombre = '".pSQL($nombre)."' AND active = 1 AND deleted = 0 LIMIT 1";
        $row = Db::getInstance()->getRow($sql);
        return $row ? new self($row['id_openai_prompt']) : null;
    }

    public static function desactivarPrompt($id_prompt)
    {
        return Db::getInstance()->update('openai_prompts', ['active' => 0], 'id_openai_prompt = '.(int)$id_prompt);
    }

    public static function duplicarPrompt($id_prompt, $usuario = 'sistema')
    {
        $original = new self((int)$id_prompt);
        if (!Validate::isLoadedObject($original)) {
            throw new Exception("Prompt no encontrado para duplicar.");
        }

        $nuevo = new self();
        $nuevo->nombre = self::generarNombreUnico($original->nombre, $original->grupo);
        $nuevo->grupo = $original->grupo;
        $nuevo->prompt = $original->prompt;
        $nuevo->modelo = $original->modelo;
        $nuevo->temperature = $original->temperature;
        $nuevo->max_tokens = $original->max_tokens;
        $nuevo->comentarios = $original->comentarios;
        $nuevo->version_anterior = '';
        $nuevo->active = 0;
        $nuevo->usuario_creacion = $usuario;        
        $nuevo->deleted = 0;

        return $nuevo->add();
    }   

    private static function generarNombreUnico($nombreBase, $grupo): string
    {
        // Si el nombre ya termina en " - copia" o " - copia (n)", eliminamos esa parte
        $nombreLimpio = preg_replace('/ - copia(?: \(\d+\))?$/', '', $nombreBase);

        $base = $nombreLimpio . ' - copia';
        $nuevoNombre = $base;
        $contador = 2;

        while (Db::getInstance()->getValue("
            SELECT COUNT(*) FROM "._DB_PREFIX_."openai_prompts
            WHERE nombre = '".pSQL($nuevoNombre)."'
            AND grupo = '".pSQL($grupo)."'
            AND deleted = 0
        ")) {
            $nuevoNombre = $base . ' (' . $contador . ')';
            $contador++;
        }

        return $nuevoNombre;
    }


}
