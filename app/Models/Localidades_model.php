<?php

namespace App\Models;

use CodeIgniter\Model;

class Localidades_model extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'localidades';
    protected $primaryKey           = 'id_localidad';
    protected $useAutoIncrement     = true;
    protected $insertID             = 0;
    protected $returnType           = 'array';
    protected $useSoftDelete        = false;
    protected $protectFields        = true;
    protected $allowedFields        = ['fecha_creacion', 'fecha_actualizacion', 'fecha_eliminacion', 'estatus', 'id_localidad', 'localidad', 'id_municipio', 'cve_loc'];

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

    public function seleccionar_localidades_por_municipio($id_municipio)
    {
        $resultado = $this->select('localidad, id_localidad')
            ->where("id_municipio", $id_municipio)
            ->find();
        if ($resultado != null) {
            return $resultado;
        } //end of if
        else {
            return null;
        } //end of else

    } //end function login


}
