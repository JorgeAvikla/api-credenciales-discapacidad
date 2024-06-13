<?php

namespace App\Models;

use CodeIgniter\Model;

class Tipos_discapacidad_model extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'tipo_discapacidad';
    protected $primaryKey           = 'id_tipo_discapacidad';
    protected $useAutoIncrement     = true;
    protected $insertID             = 0;
    protected $returnType           = 'array';
    protected $useSoftDelete        = false;
    protected $protectFields        = true;
    protected $allowedFields        = [
        "fecha_creacion",
        "fecha_actualizacion",
        "eliminacion",
        "estatus",
        "id_tipo_discapacidad",
        "nombre_discapacidad",
    ];

    // Dates
    protected $useTimestamps        = false;
    protected $dateFormat           = 'datetime';
    protected $createdField         = 'fecha_creacion';
    protected $updatedField         = 'fecha_actualizacion';
    protected $deletedField         = 'eliminacion';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks       = true;
    protected $beforeInsert         = [];
    protected $afterInsert          = [];
    protected $beforeUpdate         = [];
    protected $afterUpdate          = [];
    protected $beforeFind           = [];
    protected $afterFind            = [];
    protected $beforeDelete         = [];
    protected $afterDelete          = [];

    public function obtener_listado_dicapacidades()
    {
        $resultado = $this->select('id_tipo_discapacidad, nombre_discapacidad')
            ->findAll();
        if ($resultado != null) {
            return $resultado;
        } //end of if
        else {
            return null;
        } //end of else

    } //end function login


}
