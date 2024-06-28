<?php

namespace App\Controllers;

use App\Models\Usuarios_model;
use App\Models\Beneficiarios_model;
use App\Models\Municipios_model;
use App\Models\Localidades_model;
use App\Models\Tipos_sangre_model;
use App\Models\Tipos_discapacidad_model;




use CodeIgniter\RESTful\ResourceController;
use Exception;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Config\Services;

helper('jwt');
helper("date");

class Usuarios extends ResourceController
{


    public function login()
    {
        // dd(getKey());
        $rules = [
            "usuario" => "required",
            "password" => "required",
        ];

        $messages = [
            "usuario" => [
                "required" => "Usuario requerido",
            ],
            "password" => [
                "required" => "Contraseña requerida"
            ],
        ];

        if (!$this->validate($rules, $messages)) {
            return $this->fail($this->validator->getErrors(), 422, 'Datos faltantes');
        } else {
            $modelo_usuario = new Usuarios_model();
            $datos_usuario = $modelo_usuario->login(strtoupper(trim($this->request->getVar("usuario"))), strtoupper(trim($this->request->getVar("password"))));
            if (!empty($datos_usuario)) {
                $key = getKey();
                $iat = time(); // current timestamp value
                $nbf = $iat;
                $exp = $iat + 7200;

                $payload = array(
                    "iss" => "DIF_TLAXCALA",
                    "aud" => "curp",
                    "iat" => $iat, // issued at
                    "nbf" => $nbf, //not before in seconds
                    "exp" => $exp, // expire time in seconds
                    "data" => $datos_usuario,
                );
                $token = JWT::encode($payload, $key, 'HS256');
                $response = [
                    'status' => 200,
                    'error' => false,
                    'messages' => 'Verificación exitosa',
                    'datos_usuario' => $datos_usuario,
                    'token' => $token
                ];
                return $this->respond($response);
            } else {
                $response = [
                    'status' => 404,
                    'error' => true,
                    'messages' => 'Usuario no encontrado',
                    'data' => []
                ];
                return $this->failUnauthorized("No se encontro usuario o credenciales incorrectas", 'Unauthorized');
            }
        }
    }

    public function verificar_token()
    {
        $key = getKey();
        $authHeader = $this->request->header("Authorization");
        if ($authHeader->getValue() != null) {
            $authHeader = $authHeader->getValue();
            $token = $authHeader;
            try {
                $decoded = JWT::decode($token, new Key($key, 'HS256'));
                $modelo_usuario = new Usuarios_model;
                $datos_usuario = $modelo_usuario->find($decoded->data->id_usuario);
                $decoded->data =  $datos_usuario;
                $decoded->aud = $datos_usuario['nombre_completo'];
                unset($datos_usuario['fecha_creacion']);
                unset($datos_usuario['fecha_actualizacion']);
                unset($datos_usuario['contrasenia']);
                unset($datos_usuario['eliminacion']);

                $iat = time(); // current timestamp value
                $nbf = $iat;
                $exp = $iat + 31536000;

                $payload = array(
                    "iss" => "DIF_TLAXCALA",
                    "aud" => "curp",
                    "iat" => $iat, // issued at
                    "nbf" => $nbf, //not before in seconds
                    "exp" => $exp, // expire time in seconds
                    "data" => $datos_usuario,
                );
                $token_nuevo = JWT::encode($payload, $key, 'HS256');


                $response = [
                    'usuario' => [$datos_usuario],
                    'token' => $token_nuevo
                ];
                return $this->respondCreated($response);
            } catch (Exception $ex) {
                return $this->failUnauthorized($ex);
            }
        } else {
            return $this->failUnauthorized("Token no enviado", 'Unauthorized');
        }
    }


    public function obtener_municipios()
    {
        $token = getAuthorizationToken($this->request);
        if ($token != NULL) {
            $decoded = validateJWT($token);
        } else {
            return $this->failUnauthorized("Token no enviado", 'Unauthorized');
        }
        dd($token);
        // $key = getKey();
        // $authHeader = $this->request->header("Authorization");
        // if ($authHeader->getValue() != null) {
        //     $authHeader = $authHeader->getValue();
        //     $token = $authHeader;
        //     try {
        //         $decoded = JWT::decode($token, new Key($key, 'HS256'));
        //         $modelo_municipios = new Municipios_model;
        //         $municipios = $modelo_municipios->obtener_lista_municipios();
        //         $response = [
        //             'municipios' => $municipios,
        //         ];
        //         return $this->respondCreated($response);
        //     } catch (Exception $ex) {
        //         return $this->failUnauthorized($ex);
        //     }
        // } else {
        //     return $this->failUnauthorized("Token no enviado", 'Unauthorized');
        // }
    }

    public function obtener_localidades_por_municipio()
    {
        $rules = [
            "id_municipio" => "required",
        ];

        $messages = [
            "id_municipio" => [
                "required" => "id municipio requerido",
            ]
        ];

        if (!$this->validate($rules, $messages)) {
            return $this->fail($this->validator->getErrors(), 422, 'Datos faltantes');
        } else {
            $authHeader = $this->request->header("Authorization");
            if ($authHeader->getValue() != null) {
                $authHeader = $authHeader->getValue();
                try {
                    $id_municipio = $this->request->getVar("id_municipio");
                    $modelo_municipios = new Localidades_model;
                    $localidades = $modelo_municipios->seleccionar_localidades_por_municipio($id_municipio);
                    $response = [
                        'localidades' => $localidades,
                    ];
                    return $this->respondCreated($response);
                } catch (Exception $ex) {
                    return $this->failUnauthorized($ex);
                }
            } else {
                return $this->failUnauthorized("Token no enviado", 'Unauthorized');
            }
        }
    }
    public function obtener_listado_alergias()
    {
        $key = getKey();
        $authHeader = $this->request->header("Authorization");
        if ($authHeader->getValue() != null) {
            $authHeader = $authHeader->getValue();
            $token = $authHeader;
            try {
                $modelo_tipos_sangre = new Tipos_sangre_model;
                $listado_tipos_sangre = $modelo_tipos_sangre->obtener_listado_alergias();
                $response = [
                    'tipos_sangre' => $listado_tipos_sangre,
                ];
                return $this->respondCreated($response);
            } catch (Exception $ex) {
                return $this->failUnauthorized($ex);
            }
        } else {
            return $this->failUnauthorized("Token no enviado", 'Unauthorized');
        }
    }
    public function validar_curp_duplicado()
    {

        $rules = [
            "curp" => "required",
        ];

        $messages = [
            "curp" => [
                "required" => "curp requerido",
            ]
        ];

        if (!$this->validate($rules, $messages)) {
            return $this->fail($this->validator->getErrors(), 422, 'Datos faltantes');
        } else {
            $authHeader = $this->request->header("Authorization");
            if ($authHeader->getValue() != null) {
                $authHeader = $authHeader->getValue();
                try {
                    $curp = $this->request->getVar("curp");
                    $modelo_beneficiarios = new Beneficiarios_model();
                    $resultado_veficiacion = $modelo_beneficiarios->validar_curp_duplicado($curp);
                    $response = [
                        'estatus' => $resultado_veficiacion,
                    ];
                    return $this->respondCreated($response);
                } catch (Exception $ex) {
                    return $this->failUnauthorized($ex);
                }
            } else {
                return $this->failUnauthorized("Token no enviado", 'Unauthorized');
            }
        }
    }

    public function obtener_listado_dicapacidades()
    {
        $authHeader = $this->request->header("Authorization");
        if ($authHeader->getValue() != null) {
            $authHeader = $authHeader->getValue();
            try {
                $modelo_tipos_discapacidad = new Tipos_discapacidad_model();
                $listado_tipos_discapacidad = $modelo_tipos_discapacidad->obtener_listado_dicapacidades();
                $response = [
                    'tipos_discapacidad' => $listado_tipos_discapacidad,
                ];
                return $this->respondCreated($response);
            } catch (Exception $ex) {
                return $this->failUnauthorized($ex);
            }
        } else {
            return $this->failUnauthorized("Token no enviado", 'Unauthorized');
        }
    }
    public function generar_qr($folio)
    {
        $writer = new PngWriter();
        $qrCode = QrCode::create($folio)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::Low)
            ->setSize(300)
            ->setMargin(1)
            ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->setForegroundColor(new Color(0, 0, 0));
        $result = $writer->write($qrCode, null, null);
        $file_path = 'codigos_qr/';
        $name_qr = $folio . '.png';
        $result->saveToFile($file_path . $name_qr);
        return   $file_path . $name_qr;
    }
}
