<?php

namespace App\Controllers;

use App\Models\Beneficiarios_model;
use App\Models\Benficiarios_discapacidad_model;

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

helper("date");
helper('jwt');


class Beneficiarios extends ResourceController
{
    public function login()
    {
        $rules = [
            "folio" => "required",
            "contrasenia" => "required",
        ];

        $messages = [
            "folio" => [
                "required" => "Folio requerido",
            ],
            "contrasenia" => [
                "required" => "CURP requerido"
            ],
        ];

        if (!$this->validate($rules, $messages)) {
            return $this->fail($this->validator->getErrors(), 422, 'Unprocessable Entity');
        } else {
            $modelo_beneficiario = new Beneficiarios_model();
            $datos_usuario = $modelo_beneficiario->obtener_id_por_curp(strtoupper(trim($this->request->getVar("contrasenia"))));
            if (!empty($datos_usuario)) {
                if ($this->request->getVar("folio") === $datos_usuario['folio']) {
                    $key = getKey();
                    $iat = time(); // current timestamp value
                    $nbf = $iat + 10;
                    $exp = $iat + 31536000;

                    $payload = array(
                        "iss" => "DIF_TLAXCALA",
                        "aud" => "contrasenia",
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
                        'data' => [
                            'token' => $token
                        ]
                    ];
                    return $this->respondCreated($response);
                } else {
                    return $this->fail('No coinciden los datos del token con la bd', 422, 'Unprocessable Entity');
                }
            } else {
                return $this->failNotFound('No se encontrado benficiario, datos incorrectos', 'Not found');
            }
        }
    }

    public function datos_credencial()
    {
        $key = getKey();
        $authHeader = $this->request->header("Authorization");
        if ($authHeader === NULL) {
            return $this->failUnauthorized("Token no valido", 'Unauthorized');
        } else {
            $authHeader = $authHeader->getValue();
            $token = $authHeader;
        }
        try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            if ($decoded) {
                $modelo_beneficiario = new Beneficiarios_model();
                $modelo_benficiarios_discapacidad_model = new Benficiarios_discapacidad_model();
                $datos_credencial = $modelo_beneficiario->obtener_datos_credencial($decoded->data->id_beneficiario);


                $decoded->data =  $datos_credencial;
                $decoded->aud = $datos_credencial['curp'];
                $discapacidades  = $modelo_benficiarios_discapacidad_model->obtener_discapacidades($datos_credencial['id_beneficiario']);
                $datos_credencial['discapacidades'] = $discapacidades;
                $response = ['data' => $datos_credencial];
                return $this->respondCreated($response);
            }
        } catch (Exception $ex) {
            return $this->failUnauthorized("Token no valido", 'Unauthorized');
        }
    }
    public function actualizar_token_firebase()
    {
        $key = getKey();
        $authHeader = $this->request->header("Authorization");
        if ($authHeader === NULL) {
            return $this->failUnauthorized("Token no valido", 'Unauthorized');
        } else {
            $authHeader = $authHeader->getValue();
            $token = $authHeader;
        }
        $rules = [
            "token_firebase" => "required",
            "contrasenia" => "required",
        ];

        $messages = [
            "token_firebase" => [
                "required" => "token_firebase requerido",
            ],
            "contrasenia" => [
                "required" => "Contraseña requerido"
            ],
        ];

        if (!$this->validate($rules, $messages)) {
            return $this->fail($this->validator->getErrors(), 422, 'Datos faltantes');
        } else {
            try {
                $decoded = JWT::decode($token, new Key($key, 'HS256'));
                if ($decoded) {
                    $modelo_beneficiario = new Beneficiarios_model();
                    if ($decoded->data->id_beneficiario != null) {
                        if ($decoded->data->curp == $this->request->getVar("curp")) {
                            if ($modelo_beneficiario->update($decoded->data->id_beneficiario, array('token_firebase' => $this->request->getVar("token_firebase")))) {
                                $response = [
                                    'status' => 200,
                                    'error' => false,
                                    'messages' => 'Token actualizado correctamente',
                                    'data' => []
                                ];
                                return $this->respondCreated($response);
                            } else {
                                return $this->failServerError('Error al actualizar el token :(', 'Internal Server Error');
                            }
                        } else {
                            return $this->failServerError('CURP no correcto', 'Internal Server Error');
                        }
                    } else {
                        return $this->failNotFound('No se encontro al benficiario, datos incorrectos', 'Not found');
                    }
                }
            } catch (Exception $ex) {
                return $this->failUnauthorized("Token no valido", 'Unauthorized');
            }
        }
    }

    public function todos_los_beneficiarios()
    {
        $key = getKey();
        $authHeader = $this->request->header("Authorization");

        if ($authHeader != null) {
            $authHeader = $authHeader->getValue();
            $token = $authHeader;
            try {
                $decoded = JWT::decode($token, new Key($key, 'HS256'));
                $modelo_beneficiarios = new Beneficiarios_model;
                $datos_beneficiarios = $modelo_beneficiarios->obtener_datos_beneficiarios_tabla();
                $response = [
                    'beneficiarios' => $datos_beneficiarios,
                ];
                return $this->respondCreated($response);
            } catch (Exception $ex) {
                return $this->failUnauthorized($ex);
            }
        } else {
            return $this->failUnauthorized("Token no enviado", 'Unauthorized');
        }
    }

    public function registro_beneficiario()
    {

        $rules = [
            "nombre" => "required",
            "apellido_paterno" => "required",
            "apellido_materno" => "required",
            "curp" => "required|is_unique[beneficiarios.curp]",
            // "curp" => "required|min_length[18]|max_length[18]",
            "direccion" => "required|max_length[200]",
            "id_municipio" => "required",
            "id_localidad" => "required",
            "id_discapacidad" => "required",
            "telefono_emergencia" => "required",
            "alergias" => "required",
            "tipo_sangre" => "required",
            'imagen_beneficiario' => 'uploaded[imagen_beneficiario]|max_size[imagen_beneficiario,1024]|ext_in[imagen_beneficiario,jpg,png]',
            "nombre_contacto_emergencia" => "required",
            "telefono_contacto_emergencia" => "required"
        ];
        $messages = [
            "nombre" => [
                "required" => "Nombre requerido",
                "min_length" => "Nombre muy corto",
                "max_length" => "Nombre muy largo",
            ],
            "apellido_paterno" => [
                "required" => "Apellido paterno requerido",
                "min_length" => "Apellido paterno muy corto",
                "max_length" => "Apellido paterno muy largo",
            ],
            "apellido_materno" => [
                "required" => "Apellido materno requerido",
                "min_length" => "Apellido materno muy corto",
                "max_length" => "Apellido materno muy largo",
            ],
            "curp" => [
                "required" => "CURP requerido",
                "min_length" => "CURP muy corto",
                "max_length" => "CURP muy largo",
                "is_unique" => "CURP ya registrado",
            ],
            "direccion" => [
                "required" => "Direccion requerida",
                "min_length" => "Direccion muy corta",
                "max_length" => "Direccion muy larga",
            ],
            "id_municipio" => [
                "required" => "Muncipio requerido",
                "integer" => "ID incorrecto",
            ],
            "id_localidad" => [
                "required" => "Localidad requerida",
                "integer" => "ID incorrecto",
            ],
            "id_discapacidad" => [
                "required" => "Discapacidad requerida",
            ],
            "telefono_emergencia" => [
                "required" => "Teléfono requerido",
                "min_length" => "Teléfono muy corto",
                "max_length" => "Teléfono muy largo",
            ],
            "alergias" => [
                "required" => "Alergia requerida",
                "min_length" => "Alergia muy corta",
                "max_length" => "Alergia muy larga",
            ],
            "tipo_sangre" => [
                "required" => "Tipo sangre requerida",
                "integer" => "ID incorrecto",
            ],
            "nombre_contacto_emergencia" => [
                "required" => "Nombre de contacto requerido",
            ],
            "telefono_contacto_emergencia" => [
                "required" => "telefono de contacto requerido",
            ],
        ];
        if (!$this->validate($rules, $messages)) {

            $response = [
                'status' => 500,
                'error' => true,
                'message' => $this->validator->getErrors(),
                'data' => []
            ];
        } else {

            $imagen = $this->request->getFile('imagen_beneficiario');
            $ruta_imagen = $this->subir_imagen($imagen);

            if ($ruta_imagen != null) {
                $modelo_beneficiario = new Beneficiarios_model();
                $data_insertar = [
                    "fecha_creacion" => date("Y-m-d H:i:s", now()),
                    "nombre" => ucfirst(trim($this->request->getVar("nombre"))),
                    "apellido_paterno" => ucfirst(trim($this->request->getVar("apellido_paterno"))),
                    "apellido_materno" => ucfirst(trim($this->request->getVar("apellido_materno"))),
                    "curp" => strtoupper(trim($this->request->getVar("curp"))),
                    "direccion" => ucfirst(trim($this->request->getVar("direccion"))),
                    "id_municipio" => $this->request->getVar("id_municipio"),
                    "id_localidad" => $this->request->getVar("id_localidad"),
                    "id_discapacidad" => $this->request->getVar("id_discapacidad"),
                    "telefono_emergencia" => trim($this->request->getVar("telefono_emergencia")),
                    "alergias" => ucfirst(trim($this->request->getVar("alergias"))),
                    "comentarios" => ucfirst(trim($this->request->getVar("comentarios"))),
                    "tipo_sangre" => $this->request->getVar("tipo_sangre"),
                    "nombre_contacto_emergencia" => $this->request->getVar("nombre_contacto_emergencia"),
                    "telefono_contacto_emergencia" => $this->request->getVar("telefono_contacto_emergencia"),
                    "url_fotografia" => $ruta_imagen,
                ];
                if ($modelo_beneficiario->insert($data_insertar)) {
                    $id_beneficiario = $modelo_beneficiario->getInsertID();
                    $modelo_benficiarios_discapacidad_model = new Benficiarios_discapacidad_model();
                    $tipos_discapacidad =  explode(",", $this->request->getVar("id_discapacidad"));
                    for ($i = 0; $i < count($tipos_discapacidad); $i++) {
                        $data_insertar = array(
                            "fecha_creacion" => date("Y-m-d H:i:s", now()),

                            'id_tipo_discapacidad' => $tipos_discapacidad[$i],
                            'id_beneficiario' => $id_beneficiario,
                        );
                        if (!$modelo_benficiarios_discapacidad_model->insert($data_insertar)) {
                            return $this->failServerError('Error al insertar los tipos de  discpacidad', 'Internal Server Error');
                        }
                    }
                    $numero_folio = sprintf('%06d', $id_beneficiario);
                    $ruta_qr = $this->generar_qr($numero_folio);
                    $data_update = array(
                        'folio' => $numero_folio,
                        'url_qr' => $ruta_qr
                    );

                    if ($modelo_beneficiario->update($id_beneficiario, $data_update)) {
                        $response = [
                            'status' => 200,
                            "error" => false,
                            'messages' => 'Usuario creado correctamente',
                            'data' => ['numero_folio' => $numero_folio]
                        ];
                    } else {
                        return $this->failServerError('Error al actualizar el folio los tipos de  discpacidad', 'Internal Server Error');
                    }
                } else {
                    return $this->failServerError('Error al registrar beneficiario', 'Internal Server Error');
                }
            } else {
                return $this->failServerError('Error al subir imagen', 'Internal Server Error');
            }
        }
        return $this->respondCreated($response);
    }

    public function obtener_datos_edicion_beneficiario()
    {
        $key = getKey();
        $authHeader = $this->request->header("Authorization");
        if ($authHeader != null) {
            $authHeader = $authHeader->getValue();
            $token = $authHeader;
            try {
                $decoded = JWT::decode($token, new Key($key, 'HS256'));
                $modelo_beneficiarios = new Beneficiarios_model;
                $datos_beneficiarios = $modelo_beneficiarios->obtener_datos_beneficiario_para_actualizar($decoded->data->id_beneficiario);
                $response = [
                    'datos_beneficiario' => $datos_beneficiarios,
                ];
                return $this->respondCreated($response);
            } catch (Exception $ex) {
                return $this->failUnauthorized($ex);
            }
        } else {
            return $this->failUnauthorized("Token no enviado", 'Unauthorized');
        }
    }

    public function actualizar_beneficiario()
    {
        $rules = [
            "nombre" => "required|min_length[3]|max_length[30]",
            "apellido_paterno" => "required|min_length[3]|max_length[30]",
            "apellido_materno" => "required|min_length[3]|max_length[30]",
            // "curp" => "required|min_length[18]|max_length[18]|is_unique[beneficiarios.curp]",
            // "curp" => "required|min_length[18]|max_length[18]",
            "direccion" => "required|min_length[5]|max_length[200]",
            "id_municipio" => "required|integer",
            "id_localidad" => "required|integer",
            "id_discapacidad" => "required",
            "telefono_emergencia" => "required|min_length[10]|max_length[30]",
            "alergias" => "required",
            "tipo_sangre" => "required|integer",
            "comentarios" => "required",
            "id_beneficiario" => "required",

        ];
        $messages = [
            "nombre" => [
                "required" => "Nombre requerido",
                "min_length" => "Nombre muy corto",
                "max_length" => "Nombre muy largo",
            ],
            "apellido_paterno" => [
                "required" => "Apellido paterno requerido",
                "min_length" => "Apellido paterno muy corto",
                "max_length" => "Apellido paterno muy largo",
            ],
            "apellido_materno" => [
                "required" => "Apellido materno requerido",
                "min_length" => "Apellido materno muy corto",
                "max_length" => "Apellido materno muy largo",
            ],
            // "curp" => [
            //     "required" => "CURP requerido",
            //     "min_length" => "CURP muy corto",
            //     "max_length" => "CURP muy largo",
            //     "is_unique" => "CURP ya registrado",
            // ],
            "direccion" => [
                "required" => "Direccion requerida",
                "min_length" => "Direccion muy corta",
                "max_length" => "Direccion muy larga",
            ],
            "id_municipio" => [
                "required" => "Muncipio requerido",
                "integer" => "ID incorrecto",
            ],
            "id_localidad" => [
                "required" => "Localidad requerida",
                "integer" => "ID incorrecto",
            ],
            "id_discapacidad" => [
                "required" => "Discapacidad requerida",
            ],
            "telefono_emergencia" => [
                "required" => "Teléfono requerido",
                "min_length" => "Teléfono muy corto",
                "max_length" => "Teléfono muy largo",
            ],
            "alergias" => [
                "required" => "Alergia requerida",
            ],
            "tipo_sangre" => [
                "required" => "Sipo sangre requerida",
                "integer" => "ID incorrecto",
            ],
            "comentarios" => [
                "required" => "Comentario requerido",
            ],
            "id_beneficiario" => [
                "required" => "ID requerido",
            ]
        ];
        if ($this->validate($rules, $messages)) {
            $key = getKey();
            $authHeader = $this->request->header("Authorization");
            if ($authHeader != null) {
                $authHeader = $authHeader->getValue();
                $token = $authHeader;
                try {
                    $modelo_beneficiarios = new Beneficiarios_model;
                    $data_usuario_actualizar = [
                        "fecha_actualizacion" => date("Y-m-d H:i:s", now()),
                        "nombre" => ucfirst(trim($this->request->getVar("nombre"))),
                        "apellido_paterno" => ucfirst(trim($this->request->getVar("apellido_paterno"))),
                        "apellido_materno" => ucfirst(trim($this->request->getVar("apellido_materno"))),
                        "direccion" => ucfirst(trim($this->request->getVar("direccion"))),
                        "id_municipio" => $this->request->getVar("id_municipio"),
                        "id_localidad" => $this->request->getVar("id_localidad"),
                        "id_discapacidad" => $this->request->getVar("id_discapacidad"),
                        "telefono_emergencia" => trim($this->request->getVar("telefono_emergencia")),
                        "alergias" => ucfirst(trim($this->request->getVar("alergias"))),
                        "tipo_sangre" => $this->request->getVar("tipo_sangre"),
                        "comentarios" => $this->request->getVar("comentarios"),
                    ];

                    if ($this->request->getVar("id_beneficiario") != null) {
                        if ($modelo_beneficiarios->update($this->request->getVar("id_beneficiario"), $data_usuario_actualizar)) {
                            $modelo_beneficiarios = new Beneficiarios_model;
                            $datos_beneficiarios = $modelo_beneficiarios->obtener_datos_beneficiario_para_actualizar($this->request->getVar("id_beneficiario"));
                            $response = [
                                'status' => 200,
                                'error' => false,
                                'messages' => 'Token actualizado correctamente',
                                'data' => $datos_beneficiarios,
                            ];
                            return $this->respondCreated($response);
                        } else {
                            return $this->failServerError('Error al actualizar benficiario :(', 'Internal Server Error');
                        }
                    } else {
                        return $this->failNotFound('No se encontro al benficiario, datos incorrectos', 'Not found');
                    }
                } catch (Exception $ex) {
                    return $this->failUnauthorized($ex);
                }
            } else {
                return $this->failUnauthorized("Token no enviado", 'Unauthorized');
            }
        } else {
            return $this->fail($this->validator->getErrors(), 422, 'Datos faltantes');
        }
    }

    public function actualizar_imagen_beneficiario()
    {
        $rules = [
            'imagen_beneficiario' => 'uploaded[imagen_beneficiario]|max_size[imagen_beneficiario,1024]|ext_in[imagen_beneficiario,jpg,png]',
            "id_beneficiario" => "required"
        ];
        $messages = [
            "imagen_beneficiario" => [
                "required" => "Imagen requerida",
            ],

            "id_beneficiario" => [
                "required" => "ID requerido",
            ]
        ];
        if ($this->validate($rules, $messages)) {
            $key = getKey();
            $authHeader = $this->request->header("Authorization");
            if ($authHeader != null) {
                $authHeader = $authHeader->getValue();
                $token = $authHeader;
                try {
                    $modelo_beneficiarios = new Beneficiarios_model;
                    $imagen = $this->request->getFile('imagen_beneficiario');
                    $ruta_imagen = $this->subir_imagen($imagen);
                    $data_usuario_actualizar = [
                        "fecha_actualizacion" => date("Y-m-d H:i:s", now()),
                        "url_fotografia" => $ruta_imagen,
                    ];

                    if ($this->request->getVar("id_beneficiario") != null) {
                        if ($modelo_beneficiarios->update($this->request->getVar("id_beneficiario"), $data_usuario_actualizar)) {
                            $datos_beneficiarios = $modelo_beneficiarios->obtener_datos_beneficiario_para_actualizar($this->request->getVar("id_beneficiario"));
                            $response = [
                                'status' => 200,
                                'error' => false,
                                'messages' => 'Token actualizado correctamente',
                                'data' => $datos_beneficiarios,
                            ];
                            return $this->respondCreated($response);
                        } else {
                            return $this->failServerError('Error al actualizar benficiario :(', 'Internal Server Error');
                        }
                    } else {
                        return $this->failNotFound('No se encontro al benficiario, datos incorrectos', 'Not found');
                    }
                } catch (Exception $ex) {
                    return $this->failUnauthorized($ex);
                }
            } else {
                return $this->failUnauthorized("Token no enviado", 'Unauthorized');
            }
        } else {
            return $this->fail($this->validator->getErrors(), 422, 'Datos faltantes');
        }
    }

    public function prueba_imagen()
    {
        print_r($this->request->getVar());
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

    public function subir_imagen($imagen)
    {
        if ($imagen->isValid() && !$imagen->hasMoved()) {
            $nuevoNombre = $imagen->getRandomName();
            $path = 'fotos_credencial/' . $nuevoNombre;
            $imagen->move(FCPATH  . '/fotos_credencial', $nuevoNombre);
            return $path;
        } else {
            return  null;
        }
    }

    public function validar_edicion_curp()
    {

        $rules = [
            "curp" => "required",
            "id_beneficiario" => "required",
        ];

        $messages = [
            "curp" => [
                "required" => "curp requerido",
            ],
            "id_beneficiario" => [
                "required" => "curp requerido",
            ],
        ];

        if (!$this->validate($rules, $messages)) {
            return $this->fail($this->validator->getErrors(), 422, 'Datos faltantes');
        } else {
            $authHeader = $this->request->header("Authorization");
            if ($authHeader != null) {
                $authHeader = $authHeader->getValue();
                try {
                    $curp = $this->request->getVar("curp");
                    $id_beneficiario = $this->request->getVar("id_beneficiario");
                    $modelo_beneficiarios = new Beneficiarios_model();
                    $datos_beneficiario = $modelo_beneficiarios->find($id_beneficiario)['curp'];
                    if ($datos_beneficiario == $curp) {
                        $response = [
                            'estatus' => TRUE,
                        ];
                        return $this->respondCreated($response);
                    } else {
                        $resultado_veficiacion = $modelo_beneficiarios->validar_curp_duplicado($curp);

                        $response = [
                            'estatus' => $resultado_veficiacion,
                        ];
                        return $this->respondCreated($response);
                    }
                } catch (Exception $ex) {
                    return $this->failUnauthorized($ex);
                }
            } else {
                return $this->failUnauthorized("Token no enviado", 'Unauthorized');
            }
        }
    }

    public function actualizar_curp()
    {
        $rules = [
            "curp" => "required",
            "id_beneficiario" => "required",
        ];

        $messages = [
            "curp" => [
                "required" => "curp requerido",
            ],
            "id_beneficiario" => [
                "required" => "curp requerido",
            ],
        ];

        if (!$this->validate($rules, $messages)) {
            return $this->fail($this->validator->getErrors(), 422, 'Datos faltantes');
        } else {
            $authHeader = $this->request->header("Authorization");
            if ($authHeader != null) {
                $authHeader = $authHeader->getValue();
                try {
                    $curp = $this->request->getVar("curp");
                    $id_beneficiario = $this->request->getVar("id_beneficiario");
                    $modelo_beneficiarios = new Beneficiarios_model();
                    $data_curp = array(
                        'curp' => $curp,
                    );

                    if ($modelo_beneficiarios->update($id_beneficiario, $data_curp)) {
                        $datos_beneficiarios = $modelo_beneficiarios->obtener_datos_beneficiario_para_actualizar($id_beneficiario);
                        $response = [
                            'status' => 200,
                            'error' => false,
                            'messages' => 'CURP actualizado correctamente',
                            'data' => $datos_beneficiarios,
                        ];
                        return $this->respondCreated($response);
                    } else {
                        return $this->failServerError('Error al actualizar curp :(', 'Internal Server Error');
                    }
                } catch (Exception $ex) {
                    return $this->failUnauthorized($ex);
                }
            } else {
                return $this->failUnauthorized("Token no enviado", 'Unauthorized');
            }
        }
    }
}
