<?php

namespace App\Models;

use CodeIgniter\Model;

class Municipios_model extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'municipios';
    protected $primaryKey           = 'id_municipio';
    protected $useAutoIncrement     = true;
    protected $insertID             = 0;
    protected $returnType           = 'array';
    protected $useSoftDelete        = false;
    protected $protectFields        = true;
    protected $allowedFields        = [];

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

    public function obtener_lista_municipios()
    {
        $resultado = $this->select('id_municipio, nombre')
            ->findAll();
        if ($resultado != null) {
            return $resultado;
        } //end of if
        else {
            return null;
        } //end of else

    } //end function login
}
