<?php

namespace StevenLiebregt\CrispySystem\Routing;

class Route
{
    /**
     * @var Router
     */
    protected static $routerInstance;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string|\Closure
     */
    protected $handler;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $rules = [];

    /**
     * @var string
     */
    protected $regex;

    /**
     * @var array
     */
    protected $parameterNames = [];

    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * Route constructor.
     * @param Router $router Router instance
     * @param string $verb Method to match
     * @param string $path Path to match
     * @param string|\Closure $handler Handler to use
     */
    public function __construct(Router $router, string $verb, string $path, $handler)
    {
        $this->router = $router;
        $this->path = $this->router->getPathPrefix() . $path;
        if (!is_callable($handler)) {
            $this->handler = $this->router->getHandlerPrefix() . $handler;
        } else {
            $this->handler = $handler;
        }
    }

    /**
     * Add a GET route
     * @param string $path
     * @param $handler
     * @return Route
     */
    public static function get(string $path, $handler) : Route
    {
        return static::add('GET', $path, $handler);
    }

    /**
     * Add a POST route
     * @param string $path
     * @param $handler
     * @return Route
     */
    public static function post(string $path, $handler) : Route
    {
        return static::add('POST', $path, $handler);
    }

    /**
     * Add a PUT route
     * @param string $path
     * @param $handler
     * @return Route
     */
    public static function put(string $path, $handler) : Route
    {
        return static::add('PUT', $path, $handler);
    }

    /**
     * Add a PATCH route
     * @param string $path
     * @param $handler
     * @return Route
     */
    public static function patch(string $path, $handler) : Route
    {
        return static::add('PATCH', $path, $handler);
    }

    /**
     * Add a DELETE route
     * @param string $path
     * @param $handler
     * @return Route
     */
    public static function delete(string $path, $handler) : Route
    {
        return static::add('DELETE', $path, $handler);
    }

    /**
     * Creates a new route
     * @param string $verb
     * @param string $path
     * @param $handler
     * @return Route
     */
    protected static function add(string $verb, string $path, $handler) : Route
    {
        /** @var Router $router */
        $router = static::getRouterInstance();

        $route = new static($router, $verb, $path, $handler);

        Router::addRoute($verb, $route);

        return $route;
    }

    /**
     * @param string $parameter
     * @param string $regex
     * @return Route
     */
    public function where(string $parameter, string $regex) : Route
    {
        $this->rules[$parameter] = $regex;

        return $this;
    }

    /**
     * @param string $name
     * @param bool $override
     * @return Route
     */
    public function setName(string $name, bool $override = false) : Route
    {
        if ($override) {
            $this->name = $name;
            return $this;
        }
        $this->name = ($this->router->getNamePrefix() === '' ? $name : $this->router->getNamePrefix() . '.' . $name);

        return $this;
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPath() : string
    {
        return $this->path;
    }

    /**
     * @return callable|\Closure|string
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * @return array
     */
    public function getParameters() : array
    {
        return $this->parameters;
    }

    /**
     * Creates a regular expression to match the route
     */
    public function createRegex() : void
    {
        $parts = explode('/', ltrim($this->path, '/'));
        $regex = '/^'; // Matches the start of the string
        foreach ($parts as $part) {
            $regex .= '\/'; // Matches starting slash
            // Handle non-variable parts
            if (stripos($part, '{') === false &&
                stripos($part, '}') === false) {
                $regex .= $part; // Exactly match this part
                continue;
            }
            // Handle variable parts
            $name = str_ireplace(['{', '}'], '', $part);
            $this->parameterNames[] = $name;
            $this->parameters[$name] = null;
            // Check if a rule exists for this part
            if (isset($this->rules[$name])) {
                $regex .= $this->rules[$name];
                continue;
            }
            // No rule exists, match all except slashes
            $regex .= '([^\/]+)';
        }
        $regex .= '$/'; // Matches end of string
        $this->regex = $regex;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function isMatch(string $path) : bool
    {
        if (preg_match_all($this->regex, $path, $matches) !== 0 &&
            !empty($matches) &&
            !empty($matches[0])) {
            array_shift($matches);
            foreach ($matches as $i => $match) {
                $this->parameters[$this->parameterNames[$i]] = $match[0];
            }
            return true;
        }
        return false; // Match not found
    }

    /**
     * @return Router
     */
    protected static function getRouterInstance() : Router
    {
        static::$routerInstance = Router::getInstance();

        return static::$routerInstance;
    }
}
