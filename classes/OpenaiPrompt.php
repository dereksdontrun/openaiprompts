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
    public $activo;
    public $comentarios;
    public $version_anterior;
    public $usuario_ultima_modificacion;
    public $fecha_ultima_modificacion;
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
            'activo' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'comentarios' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'],
            'version_anterior' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'],
            'usuario_ultima_modificacion' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64],
            'fecha_ultima_modificacion' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
        ],
        'multilang' => false,
    ];

    public static function getByGrupo($grupo)
    {
        $sql = "SELECT * FROM "._DB_PREFIX_."openai_prompts WHERE grupo = '".pSQL($grupo)."' AND activo = 1";
        return Db::getInstance()->executeS($sql);
    }

    public static function getByNombre($nombre)
    {
        $sql = "SELECT * FROM "._DB_PREFIX_."openai_prompts WHERE nombre = '".pSQL($nombre)."' AND activo = 1 LIMIT 1";
        $row = Db::getInstance()->getRow($sql);
        return $row ? new self($row['id_openai_prompt']) : null;
    }

    public static function desactivarPrompt($id_prompt)
    {
        return Db::getInstance()->update('openai_prompts', ['activo' => 0], 'id_openai_prompt = '.(int)$id_prompt);
    }

    public static function duplicarPrompt($id_prompt, $nuevoNombre, $usuario = 'sistema')
    {
        $prompt = new self((int)$id_prompt);
        $nombreBase = $prompt->nombre;
        $grupo = $prompt->grupo;

        // Buscar un nombre disponible
        $nuevoNombre = $nombreBase . ' - copia';
        $contador = 2;

        while (Db::getInstance()->getValue("
            SELECT COUNT(*) FROM "._DB_PREFIX_."openai_prompts
            WHERE nombre = '".pSQL($nuevoNombre)."' AND grupo = '".pSQL($grupo)."'
        ")) {
            $nuevoNombre = $nombreBase . ' - copia (' . $contador . ')';
            $contador++;
        }

        // Crear duplicado
        $nuevo = $prompt->duplicateObject();
        $nuevo->nombre = $nuevoNombre;
        $nuevo->usuario_ultima_modificacion = $usuario;
        $nuevo->fecha_ultima_modificacion = date('Y-m-d H:i:s');

        return $nuevo->save();
    }
}
