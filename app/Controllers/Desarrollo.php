<?php

namespace App\Controllers;

use App\Models\Localidades_model;
use App\Models\Beneficiarios_model;
use App\Models\Benficiarios_discapacidad_model;
use CodeIgniter\RESTful\ResourceController;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;


helper("date");

class Desarrollo extends ResourceController
{

    public function cargar_padron()
    {
        $archivo = fopen(FCPATH . "../recursos/padron.csv", "r");
        $modelo_beneficiarios = new Beneficiarios_model();
        $modelo_localidades = new Localidades_model();
        $modelo_beneficiarios_discapacidad = new Benficiarios_discapacidad_model();

        $cont_encontrados = 0;
        $cont_no_encontrados = 0;
        $cont_filas = 0;

        $arreglo_beneficiarios = array();
        while (($datos = fgetcsv($archivo, 0, ",")) == true) {
            $localidades = $modelo_localidades->seleccionar_localidades_por_municipio($datos[8]);
            foreach ($localidades as $id => $localidad) {
                $localidad['localidad'] = strtolower($this->eliminar_acentos($localidad['localidad']));
                $localidades[$id] = $localidad;
            }

            foreach ($localidades as $localidad) {
                $localidad_arreglo = strtolower($localidad['localidad']);
                $localidad_abuscar = strtolower($this->eliminar_acentos($datos[6]));
                if ($localidad_arreglo === $localidad_abuscar) {
                    $id_localidad =  $localidad['id_localidad'];
                    $cont_encontrados++;

                    break;
                } else {
                    $cont_no_encontrados++;

                    $id_localidad = '1734';
                }
            }
            // dd($datos);
            $cont_filas++;

            $beneficiario = array(
                "fecha_creacion" => date("Y-m-d H:i:s", now()),
                'estatus' => 1,
                'eliminacion' => null,
                'id_beneficiario' => $cont_filas,
                'folio' => sprintf('%06d', $cont_filas),
                'nombre' => $datos[1],
                'apellido_paterno' => $datos[2],
                'apellido_materno' => $datos[3],
                'curp' => $datos[4],
                'direccion' => $datos[5],
                'id_municipio' => intval($datos[8]),
                'id_localidad' => intval($id_localidad),
                'telefono_emergencia' => $datos[10],
                'alergias' => NULL,
                'tipo_sangre' => 9,
                'comentarios' => 'Sin comentarios',
                'url_fotografia' => 'fotos_credencial/' . sprintf('%06d', $cont_filas)  . '.jpg',
                'url_qr' => 'codigos_qr/' . sprintf('%06d', $cont_filas) . '.png',
            );
            //!important comentadi oar aque no funcione
            $modelo_beneficiarios->insert($beneficiario);

            $id_beneficiario = $modelo_beneficiarios->getInsertID();
            $discapacidades = array(
                'id_tipo_discapacidad' => $datos[9],
                'id_beneficiario' => $id_beneficiario,

            );
            $modelo_beneficiarios_discapacidad->insert($discapacidades);


            array_push($arreglo_beneficiarios, $beneficiario);
        } //end while
        d($cont_encontrados);
        d('No encontrados ' . count($arreglo_beneficiarios) - $cont_encontrados);
        dd($arreglo_beneficiarios);
        // echo "Proceso finalizado   $cont_filas empleados insertados";
        fclose($archivo);
    }

    function cargar_localidades()
    {
        $archivo = fopen(FCPATH . "../recursos/nuevo_catalogo_localidades.csv", "r");
        $modelo_localidades = new Localidades_model();
        $cont_filas = 0;
        while (($datos = fgetcsv($archivo, 0, ",")) == true) {
            $localidad = array(
                "fecha_creacion" => date("Y-m-d H:i:s", now()),
                "fecha_actualizacion" => date("Y-m-d H:i:s", now()),
                "fecha_eliminacion" => NULL,
                "estatus" => 1,
                "localidad" => $this->convertirCadena($datos[3]),
                "id_municipio" => number_format($datos[1]),
                "cve_loc" => $datos[2]
            );
            $modelo_localidades->insert($localidad);
            // dd($localidad);
        } //end while


    }

    function convertirCadena($cadena)
    {
        // Convertir toda la cadena a minúsculas
        $cadena = strtolower($cadena);

        // Convertir a mayúscula la primera letra de cada palabra
        $cadena = ucwords($cadena);

        // Lista de palabras que no deben ser capitalizadas
        $preposiciones = ['De', 'Del', 'La', 'El', 'Y'];

        // Convertir las preposiciones de nuevo a minúsculas, excepto si están al inicio
        $palabras = explode(' ', $cadena);
        foreach ($palabras as &$palabra) {
            if (in_array($palabra, $preposiciones)) {
                $palabra = strtolower($palabra);
            }
        }

        // Unir las palabras en una cadena
        $cadena = implode(' ', $palabras);
        // Asegurarse que la primera letra de la cadena sea mayúscula
        $cadena = ucfirst($cadena);
        return $cadena;
    }

    function renombrar_imagenes_beneficiarios()
    {
        // Ruta de la carpeta con las imágenes
        $carpeta = FCPATH . 'fotos_credencial';

        // Obtener la lista de archivos en la carpeta
        $archivos = scandir($carpeta);
        // dd($archivos);

        // Iterar sobre cada archivo
        foreach ($archivos as $archivo) {
            // Saltar los directorios "." y ".."
            if ($archivo === '.' || $archivo === '..') {
                continue;
            }

            // Generar el nuevo nombre con hash MD5
            $nuevoNombre = sprintf('%06d', $archivo) . '.' . pathinfo($archivo, PATHINFO_EXTENSION);

            // Renombrar el archivo
            $rutaAntigua = $carpeta . '/' . $archivo;
            $rutaNueva = $carpeta . '/' . $nuevoNombre;
            // rename($rutaAntigua, $rutaNueva);

            // Mostrar información del archivo renombrado
            echo "Archivo renombrado: $archivo -> $nuevoNombre <br>";
        }
    }

    function crear_codigos_qr()
    {
        $modelo_beneficiarios = new Beneficiarios_model();
        $beneficiarios = $modelo_beneficiarios->find();
        $contador = 0;
        foreach ($beneficiarios as $beneficiario) {
            // dd($beneficiario);
            $this->generar_qr($beneficiario['folio']);
            $contador++;
            // dd($beneficiario);
        }

        echo '<pre>';
        print_r('Se crearon ' . $contador . ' codigos QR');
        echo '<pre>';
    }

    function eliminar_acentos($cadena)
    {
        //Reemplazamos la A y a
        $cadena = str_replace(
            array('Á', 'À', 'Â', 'Ä', 'á', 'à', 'ä', 'â', 'ª'),
            array('A', 'A', 'A', 'A', 'a', 'a', 'a', 'a', 'a'),
            $cadena
        );

        //Reemplazamos la E y e
        $cadena = str_replace(
            array('É', 'È', 'Ê', 'Ë', 'é', 'è', 'ë', 'ê'),
            array('E', 'E', 'E', 'E', 'e', 'e', 'e', 'e'),
            $cadena
        );

        //Reemplazamos la I y i
        $cadena = str_replace(
            array('Í', 'Ì', 'Ï', 'Î', 'í', 'ì', 'ï', 'î'),
            array('I', 'I', 'I', 'I', 'i', 'i', 'i', 'i'),
            $cadena
        );

        //Reemplazamos la O y o
        $cadena = str_replace(
            array('Ó', 'Ò', 'Ö', 'Ô', 'ó', 'ò', 'ö', 'ô'),
            array('O', 'O', 'O', 'O', 'o', 'o', 'o', 'o'),
            $cadena
        );

        //Reemplazamos la U y u
        $cadena = str_replace(
            array('Ú', 'Ù', 'Û', 'Ü', 'ú', 'ù', 'ü', 'û'),
            array('U', 'U', 'U', 'U', 'u', 'u', 'u', 'u'),
            $cadena
        );

        //Reemplazamos la N, n, C y c
        $cadena = str_replace(
            array('Ñ', 'ñ', 'Ç', 'ç'),
            array('N', 'n', 'C', 'c'),
            $cadena
        );

        $cadena_limpia = str_replace(' ', '', trim(rtrim($cadena)));
        // dd($cadena_limpia);

        return str_replace('.', '', $cadena_limpia);
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


    public function subir_imagen()
    {

        // Validación de datos (opcional)
        $validationRules = [
            'nombre' => 'required',
            'imagen' => 'uploaded[imagen]|max_size[imagen,1024]|ext_in[imagen,jpg,png]'
        ];

        if (!$this->validate($validationRules)) {
            return $this->fail($this->validator->getErrors());
        }

        // Obtener la imagen
        $imagen = $this->request->getFile('imagen');

        if ($imagen->isValid() && !$imagen->hasMoved()) {
            $nuevoNombre = $imagen->getRandomName();
            $imagen->move(FCPATH  . '/pruebas', $nuevoNombre);

            // Aquí puedes guardar el nombre de la imagen en tu base de datos, si es necesario.

            return $this->respond(['message' => 'Imagen recibida y guardada.', 'nombreImagen' => $nuevoNombre]);
        } else {
            return $this->failServerError('Error al procesar la imagen.');
        }
    }
}
