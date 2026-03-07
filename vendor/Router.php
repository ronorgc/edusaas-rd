<?php
// =====================================================
// EduSaaS RD - Router Simple
// =====================================================

class Router
{
    private array $routes = [];

    /**
     * Registra una ruta GET.
     * Ejemplo: $router->get('/dashboard', 'DashboardController', 'index');
     */
    public function get(string $ruta, string $controlador, string $metodo): void
    {
        $this->routes['GET'][$ruta] = [$controlador, $metodo];
    }

    /**
     * Registra una ruta POST.
     */
    public function post(string $ruta, string $controlador, string $metodo): void
    {
        $this->routes['POST'][$ruta] = [$controlador, $metodo];
    }

    /**
     * Procesa la URL actual y llama al controlador correcto.
     */
    public function dispatch(): void
    {
        $metodoHttp = $_SERVER['REQUEST_METHOD'];
        $urlActual  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Limpiar la URL base del proyecto si existe
        $base = parse_url(
            (require __DIR__ . '/../config/app.php')['url'],
            PHP_URL_PATH
        );
        $urlActual = '/' . ltrim(substr($urlActual, strlen($base)), '/');

        // Buscar coincidencia exacta primero
        if (isset($this->routes[$metodoHttp][$urlActual])) {
            [$controlador, $metodo] = $this->routes[$metodoHttp][$urlActual];
            $this->ejecutar($controlador, $metodo);
            return;
        }

        // Buscar rutas con parámetros dinámicos  (ej: /estudiantes/{id})
        foreach ($this->routes[$metodoHttp] ?? [] as $patron => [$controlador, $metodo]) {
            $regex  = preg_replace('/\{[a-z_]+\}/', '([^/]+)', $patron);
            $regex  = '#^' . $regex . '$#';

            if (preg_match($regex, $urlActual, $matches)) {
                array_shift($matches); // quitar el match completo
                $this->ejecutar($controlador, $metodo, $matches);
                return;
            }
        }

        // 404
        http_response_code(404);
        require_once __DIR__ . '/../views/errors/404.php';
    }

    private function ejecutar(string $controlador, string $metodo, array $params = []): void
    {
        if (!class_exists($controlador)) {
            die("Controlador [{$controlador}] no encontrado.");
        }

        $instancia = new $controlador();

        if (!method_exists($instancia, $metodo)) {
            die("Método [{$metodo}] no existe en [{$controlador}].");
        }

        $instancia->$metodo(...$params);
    }
}
