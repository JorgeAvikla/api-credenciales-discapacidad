<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Login::index');
$routes->group("api", function ($routes) {
    $routes->post("register", "User::register");
    $routes->post("login", "Beneficiarios::login");
    $routes->get("obtener_datos_credencial", "Beneficiarios::obtener_datos_edicion_beneficiario");
    $routes->put("actualizar_token_firebase", "Beneficiarios::actualizar_token_firebase");
});

$routes->group("auth", function ($routes) {
    $routes->post("login", "Usuarios::login");
    $routes->get('verificar_token', 'Usuarios::verificar_token', ['filter' => 'cors']);
});

$routes->group("beneficiarios", function ($routes) {
    $routes->get('todos_los_beneficiarios', 'Beneficiarios::todos_los_beneficiarios', ['filter' => 'cors']);
    $routes->post("registro_beneficiario", "Beneficiarios::registro_beneficiario", ['filter' => 'cors']);
    $routes->get('obtener_datos_edicion_beneficiario/(:num)', 'Beneficiarios::obtener_datos_edicion_beneficiario/$1', ['filter' => 'cors']);
    $routes->put("actualizar_beneficiario", "Beneficiarios::actualizar_beneficiario", ['filter' => 'cors']);
    $routes->post("actualizar_imagen_beneficiario", "Beneficiarios::actualizar_imagen_beneficiario", ['filter' => 'cors']);
    $routes->post("validar_edicion_curp", "Beneficiarios::validar_edicion_curp", ['filter' => 'cors']);
    $routes->put("actualizar_curp", "Beneficiarios::actualizar_curp", ['filter' => 'cors']);
    $routes->put("actualizar_datos_contacto", "Beneficiarios::actualizar_datos_contacto", ['filter' => 'cors']);
});

$routes->group("generales", function ($routes) {
    $routes->get('obtener_municipios', 'Usuarios::obtener_municipios', ['filter' => 'cors']);
    $routes->post('obtener_localidades_por_municipio', 'Usuarios::obtener_localidades_por_municipio', ['filter' => 'cors']);
    $routes->get('obtener_listado_alergias', 'Usuarios::obtener_listado_alergias', ['filter' => 'cors']);
    $routes->post('validar_curp_duplicado', 'Usuarios::validar_curp_duplicado', ['filter' => 'cors']);
    $routes->get('obtener_listado_dicapacidades', 'Usuarios::obtener_listado_dicapacidades', ['filter' => 'cors']);
});

$routes->group("pruebas", function ($routes) {
    $routes->post('subir_imagen', 'Desarrollo::subir_imagen', ['filter' => 'cors']);
});



$routes->get("cargar_padron", "Desarrollo::cargar_padron");
$routes->get("cargar_localidades", "Desarrollo::cargar_localidades");
$routes->get("renombrar_imagenes_beneficiarios", "Desarrollo::renombrar_imagenes_beneficiarios");
$routes->get("crear_codigos_qr", "Desarrollo::crear_codigos_qr");
