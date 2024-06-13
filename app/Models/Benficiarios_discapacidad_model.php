<?php

namespace App\Models;

use CodeIgniter\Model;

class Benficiarios_discapacidad_model extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'beneficiarios_discapacidad';
    protected $primaryKey           = 'beneficiarios_discapacidad';
    protected $useAutoIncrement     = true;
    protected $insertID             = 0;
    protected $returnType           = 'array';
    protected $useSoftDelete        = false;
    protected $protectFields        = true;
    protected $allowedFields        = [
        "fecha_creacion",
        "fecha_actualizacion",
        "eliminacion",
        "id_beneficiario",
        "id_tipo_discapacidad"
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

    public function obtener_discapacidades($id_beneficiario)
    {
        $resultado = $this->select('tipo_discapacidad.nombre_discapacidad as discapacidad')
            ->join('tipo_discapacidad', 'tipo_discapacidad.id_tipo_discapacidad = beneficiarios_discapacidad.id_tipo_discapacidad')
            ->where('beneficiarios_discapacidad.id_beneficiario', $id_beneficiario)
            ->find();
        return ($resultado != null) ? $resultado : NULL;
    } //End obtener_discapacidades
}
