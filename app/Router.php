<?php
class Router {
    private $routes = [
        'GET'    => [],
        'POST'   => [],
        'PUT'    => [],
        'DELETE' => []
    ];

    // stores middleware per route key "METHOD:uri"
    private $middlewares = [];

    // ==============================
    // ROUTE REGISTRATION METHODS
    // ==============================
    public function get($uri, $action) {
        $this->addRoute('GET', $uri, $action);
    }
    public function post($uri, $action) {
        $this->addRoute('POST', $uri, $action);
    }
    public function put($uri, $action) {
        $this->addRoute('PUT', $uri, $action);
    }
    public function delete($uri, $action) {
        $this->addRoute('DELETE', $uri, $action);
    }

    private function addRoute($method, $uri, $action) {
        $this->routes[$method][$this->format($uri)] = $action;
    }

    // attach a callable middleware to every route registered since last call
    // Usage: $router->get(...); $router->post(...); $router->middleware(fn() => ...);
    // OR use the group() helper below instead
    public function middleware(callable $fn): void {
        // Apply to the last registered route across all methods
        foreach ($this->routes as $method => $routes) {
            $last = array_key_last($routes);
            if ($last !== null) {
                $this->middlewares[$method . ':' . $last] = $fn;
            }
        }
    }

    // group helper - registers multiple routes and applies one middleware to all
    // Usage:
    //   $router->group(fn() => \App\Middleware\AuthMiddleware::check(), function() use ($router) {
    //       $router->get('Admin/Dashboard', 'Admin/DashboardController@index');
    //       $router->get('Admin/Profile',   'Admin/ProfileController@index');
    //   });
    public function group(callable $middleware, callable $routes): void {
        // Snapshot routes before
        $before = [];
        foreach ($this->routes as $method => $map) {
            $before[$method] = array_keys($map);
        }

        // Register routes inside the group
        $routes();

        // Attach middleware to every newly added route
        foreach ($this->routes as $method => $map) {
            foreach (array_keys($map) as $uri) {
                if (!in_array($uri, $before[$method], true)) {
                    $this->middlewares[$method . ':' . $uri] = $middleware;
                }
            }
        }
    }

    private function format($uri) {
        return trim($uri, '/');
    }

    // ==============================
    // DYNAMIC SEGMENT MATCHER
    // ==============================
    private function matchDynamic($method, $uri) {
        foreach ($this->routes[$method] as $pattern => $action) {
            if (strpos($pattern, '{') === false) continue;

            $regex = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $pattern);
            $regex = '#^' . $regex . '$#';

            if (preg_match($regex, $uri, $matches)) {
                $params = array_filter(
                    $matches,
                    fn($k) => is_string($k),
                    ARRAY_FILTER_USE_KEY
                );

                return [
                    'action'  => $action,
                    'params'  => array_values($params),
                    'pattern' => $pattern
                ];
            }
        }
        return null;
    }

    // ==============================
    // MAIN ROUTE HANDLER
    // ==============================
    public function resolve() {
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        $uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $base = dirname($_SERVER['SCRIPT_NAME']);

        if ($base !== '/' && strpos($uri, $base) === 0) {
            $uri = substr($uri, strlen($base));
        }

        $uri = $this->format($uri);

        $action = null;
        $params = [];
        $matchedPattern = null; // track the pattern key for middleware lookup

        if (isset($this->routes[$method][$uri])) {
            $action         = $this->routes[$method][$uri];
            $matchedPattern = $uri;

        } elseif ($match = $this->matchDynamic($method, $uri)) {
            $action         = $match['action'];
            $params         = $match['params'];
            $matchedPattern = $match['pattern'];

        } else {
            http_response_code(404);
            require_once __DIR__ . '/Views/errors/404.php';
            return;
        }

        // run middleware if one is registered for this route
        $middlewareKey = $method . ':' . $matchedPattern;
        if (isset($this->middlewares[$middlewareKey])) {
            ($this->middlewares[$middlewareKey])();
        }

        // ==============================
        // CONTROLLER RESOLUTION
        // ==============================
        list($controller, $methodName) = explode('@', $action);

        $controllerNamespace = str_replace('/', '\\', $controller);
        $controllerPath      = str_replace('\\', '/', $controllerNamespace);
        $controllerFile      = __DIR__ . "/Controllers/" . $controllerPath . ".php";

        if (!file_exists($controllerFile)) {
            echo "Controller file not found: " . $controllerFile;
            return;
        }

        require_once $controllerFile;

        $fullClass = "App\\Controllers\\" . $controllerNamespace;

        if (!class_exists($fullClass)) {
            echo "Class not found: " . $fullClass;
            return;
        }

        $controllerObj = new $fullClass();

        if (!method_exists($controllerObj, $methodName)) {
            echo "Method not found: " . $methodName;
            return;
        }

        return call_user_func_array([$controllerObj, $methodName], $params);
    }
}
