<?php

namespace App\Models;

use CodeIgniter\Model;

class Beneficiarios_model extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'beneficiarios';
    protected $primaryKey           = 'id_beneficiario';
    protected $useAutoIncrement     = true;
    protected $insertID             = 0;
    protected $returnType           = 'array';
    protected $useSoftDelete        = false;
    protected $protectFields        = true;
    protected $allowedFields        = [
        "id_beneficiario",
        "fecha_creacion",
        "folio",
        "token_firebase",
        "nombre",
        "apellido_paterno",
        "apellido_materno",
        "curp",
        "direccion",
        "id_municipio",
        "id_localidad",
        "telefono_emergencia",
        "alergias",
        "tipo_sangre",
        "nombre_contacto_emergencia",
        "telefono_contacto_emergencia",
        "url_fotografia",
        "url_qr",
        "comentarios",
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


    public function obtener_id_por_curp($curp)
    {
        $resultado = $this->select('id_beneficiario, folio, curp')
            ->where("SHA2(beneficiarios.curp, 256)", $curp)
            ->first();
        return ($resultado != null) ? $resultado : NULL;
    } //End obtener_id_por_curp

    public function obtener_datos_credencial($id_beneficiario)
    {
        $resultado = $this->select('beneficiarios.id_beneficiario,IF(beneficiarios.estatus = 1, "Activo","Inactivo") as estatus_beneficiario,beneficiarios.folio, IF(beneficiarios.url_fotografia IS NULL, "",CONCAT("' . base_url() . '",beneficiarios.url_fotografia))as fotografia, CONCAT(beneficiarios.nombre," ",beneficiarios.apellido_paterno," ",beneficiarios.apellido_materno) AS nombre_completo, beneficiarios.curp, CONCAT(beneficiarios.direccion,", ",municipios.nombre,", ",localidades.localidad) AS direccion, IF( beneficiarios.url_qr IS NULL, "", CONCAT("' . base_url() . '",beneficiarios.url_qr))as url_codigo_qr, beneficiarios.telefono_emergencia,IF(beneficiarios.alergias IS NULL, "",beneficiarios.alergias)as alergia, tipo_sangre.nombre as tipo_sangre, beneficiarios.nombre_contacto_emergencia,beneficiarios.telefono_contacto_emergencia')
            ->join('tipo_sangre', 'tipo_sangre.id_tipo_sangre = beneficiarios.tipo_sangre')
            ->join('municipios', 'municipios.id_municipio = beneficiarios.id_municipio')
            ->join('localidades', 'localidades.id_localidad = beneficiarios.id_localidad')
            ->where('beneficiarios.id_beneficiario', $id_beneficiario)
            ->first();
        return ($resultado != null) ? $resultado : NULL;
    } //End obtener_id_por_curp

    public function obtener_datos_beneficiarios_tabla()
    {
        $resultado = $this->select('beneficiarios.id_beneficiario,IF(beneficiarios.estatus = 1, "Activo","Inactivo") as estatus_beneficiario,beneficiarios.folio, IF(beneficiarios.url_fotografia IS NULL, "",CONCAT("' . base_url() . '",beneficiarios.url_fotografia))as fotografia, CONCAT(beneficiarios.nombre," ",beneficiarios.apellido_paterno," ",beneficiarios.apellido_materno) AS nombre_completo, beneficiarios.curp, CONCAT(beneficiarios.direccion,", ",municipios.nombre,", ",localidades.localidad) AS direccion, IF( beneficiarios.url_qr IS NULL, "", CONCAT("' . base_url() . '",beneficiarios.url_qr))as url_codigo_qr, beneficiarios.telefono_emergencia,IF(beneficiarios.alergias IS NULL, "",beneficiarios.alergias)as alergia, tipo_sangre.nombre as tipo_sangre,')
            ->join('tipo_sangre', 'tipo_sangre.id_tipo_sangre = beneficiarios.tipo_sangre')
            ->join('municipios', 'municipios.id_municipio = beneficiarios.id_municipio')
            ->join('localidades', 'localidades.id_localidad = beneficiarios.id_localidad')
            ->findAll();
        return ($resultado != null) ? $resultado : NULL;
    }

    public function validar_curp_duplicado($curp)
    {
        $resultado = $this->select('curp')
            ->where('beneficiarios.curp', $curp)
            ->first();
        return ($resultado != null) ? null : true;
    }

    public function obtener_datos_beneficiario_para_actualizar($id_beneficiario)
    {
        $resultado = $this->select('beneficiarios.nombre, beneficiarios.apellido_paterno, beneficiarios.apellido_materno,
            beneficiarios.curp, beneficiarios.direccion,beneficiarios.id_municipio, beneficiarios.id_localidad,beneficiarios.telefono_emergencia,
            beneficiarios.alergias, beneficiarios.tipo_sangre, beneficiarios.comentarios, beneficiarios.nombre_contacto_emergencia,beneficiarios.telefono_contacto_emergencia,
            tipo_discapacidad.id_tipo_discapacidad, 
            CONCAT("' . base_url() . '",beneficiarios.url_fotografia)as url_fotografia, municipios.nombre as nombre_municipio, 
            localidades.localidad as nombre_localidad, tipo_discapacidad.nombre_discapacidad, 
            IF(tipo_sangre.id_tipo_sangre = 9, "No_disponible",tipo_sangre.nombre) as nombre_tipo_sangre,
            IF(beneficiarios.alergias is NULL, "No_disponible",beneficiarios.alergias) as nombre_alergias')
            ->join('beneficiarios_discapacidad', 'beneficiarios_discapacidad.id_beneficiario = beneficiarios.id_beneficiario')
            ->join('tipo_discapacidad', 'tipo_discapacidad.id_tipo_discapacidad = beneficiarios_discapacidad.id_tipo_discapacidad')
            ->join('municipios', 'municipios.id_municipio = beneficiarios.id_municipio')
            ->join('localidades', 'localidades.id_localidad = beneficiarios.id_localidad')
            ->join('tipo_sangre', 'tipo_sangre.id_tipo_sangre = beneficiarios.tipo_sangre')
            ->where('beneficiarios.id_beneficiario', $id_beneficiario)

            ->first();
        return ($resultado != null) ? $resultado : NULL;
    }
}
