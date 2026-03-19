<?php
require_once __DIR__ . '/../models/UsuarioModel.php';

class AuthController
{
    private UsuarioModel $usuario;

    public function __construct()
    {
        $this->usuario = new UsuarioModel();
    }

    public function login()
    {
        session_start();

        $input = json_decode(file_get_contents('php://input'), true);

        $usuario = $input['usuario'] ?? null;
        $clave   = $input['clave'] ?? null;

        if (!$usuario || !$clave) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'message' => 'Usuario Y Contraseña Requeridos'
            ]);
            exit;
        }

        $data = $this->usuario->login($usuario, $clave);

        if (!$data) {
            http_response_code(401);
            echo json_encode([
                'ok' => false,
                'message' => 'Perfil No Autorizado'
            ]);
            exit;
        }

        // GUARDAR USUARIO EN SESIÓN
        $_SESSION['usuario'] = $data['cod_usr']; 

        echo json_encode([
            'ok' => true,
            'user' => $data
        ]);
        exit;
    }
}
