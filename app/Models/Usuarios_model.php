<?php

namespace App\Models;

use CodeIgniter\Model;

class Usuarios_model extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'usuarios';
    protected $primaryKey           = 'id_usuario';
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
        "nombre_completo",
        "usuario",
        "contrasenia"
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

    public function login($usuario, $contrasena)
    {
        $resultado = $this->select('nombre_completo, estatus, usuario, id_usuario')
            ->where("usuario", $usuario)
            ->where("contrasenia", $contrasena)
            ->first();
        if ($resultado != null) {
            return $resultado;
        } //end of if
        else {
            return null;
        } //end of else

    } //end function login


}
