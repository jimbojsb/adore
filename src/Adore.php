<?php
namespace Adore;

use Aura\Web\WebFactory;
use Aura\Router\RouterFactory;


class Application
{
    protected $routes = [];
    protected $notFoundAction;
    protected $errorAction;
    protected $actionFactory;
    protected $responderFactory;

    public function run()
    {
        $webFactory = new WebFactory($GLOBALS);
        $request = $webFactory->newRequest();
        $action = $this->route($request);
        $responder = $this->dispatch($action);
        $this->respond($responder);
    }

    protected function route(\Aura\Web\Request $request)
    {
        $routerFactory = new RouterFactory();
        $router = $routerFactory->newInstance();
        foreach ($this->routes as $routeSpec) {
            $route = $router->add(null, $routeSpec["path"]);
            $route->addValues($routeSpec["params"]);
            if ($routeSpec["methods"]) {
                foreach ($routeSpec["methods"] as $method) {
                    $route->addMethod($routeSpec["method"]);
                }
            }
        }

        $route = $router->match($request->url->get(PHP_URL_PATH), $_SERVER);
        if ($route) {
            $actionName = $route->params["action"];
        } else {
            $actionName = $this->notFoundAction;
        }

        $actionFactory = $this->actionFactory;
        $action = $actionFactory($actionName);
        $action->_setRequest($request);
        $action->_setParams($route->params);
        $action->_setResponderFactory($this->responderFactory);
        $action->_init();
        return $action;
    }

    protected function dispatch($action)
    {
        try {

            $r = new \ReflectionMethod(get_class($action), "__invoke");
            $methodParams = $r->getParameters();
            $requestedParams = [];

            foreach ($methodParams as $param) {
                $requestedParams[] = $param->name;
            }

            $availableParams = $action->_getParams();
            $paramsToPass = [];
            foreach ($requestedParams as $param) {
                $paramsToPass[] = $availableParams[$param];
            }

            $responder = call_user_func_array($action, $paramsToPass);
            return $responder;

        } catch (\Exception $e) {
            die('exception'); // @todo make this work
        }
    }

    protected function respond($responder)
    {
        $responder();
        $response = $responder->_getResponse();

        header($response->status->get(), true, $response->status->getCode());

        foreach ($response->headers->get() as $label => $value) {
            header("{$label}: {$value}");
        }

        foreach ($response->cookies->get() as $name => $cookie) {
            setcookie(
                $name,
                $cookie['value'],
                $cookie['expire'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httponly']
            );
        }

        echo $response->content->get();
    }

    public function addRoute($path, $actionName, $methods = [], $additionalParams = [])
    {
        $params = [
            "action" => $actionName
        ];
        $params = array_merge($params, $additionalParams);

        $route = [
            "path" => $path,
            "params" => $params,
            "methods" => $methods
        ];

        $this->routes[] = $route;
    }

    public function setErrorAction($actionName)
    {
        $this->errorAction = $actionName;
    }

    public function setNotFoundAction($actionName)
    {
        $this->notFoundAction = $actionName;
    }

    public function setActionFactory(\Closure $factory)
    {
        $this->actionFactory = $factory;
    }

    public function setResponderFactory(\Closure $factory)
    {
        $responderFactoryProxy = function($responderName) use ($factory) {
            $webFactory = new WebFactory($GLOBALS);
            $response = $webFactory->newResponse();
            $responder = $factory($responderName);
            $responder->_setResponse($response);
            $responder->_init();
            return $responder;
        };
        $this->responderFactory = $responderFactoryProxy;
    }
}

trait ActionTrait
{
    /**
     * @var \Aura\Web\Request
     */
    protected $_request;
    protected $_params = [];
    protected $_responderFactory;

    public function _setRequest(\Aura\Web\Request $request)
    {
        $this->_request = $request;
    }

    public function _setParams($params)
    {
        $this->_params = $params;
    }

    public function _getParams()
    {
        return $this->_params;
    }

    public function _setResponderFactory(\Closure $responderFactory)
    {
        $this->_responderFactory = $responderFactory;
    }

    public function _getResponder($responderName = null)
    {
        $responderFactory = $this->_responderFactory;
        return $responderFactory($responderName);
    }

    public function _init()
    {

    }
}

trait ResponderTrait
{
    /**
     * @var \Aura\Web\Response
     */
    protected $_response;

    public function _setResponse(\Aura\Web\Response $response)
    {
        $this->_response = $response;
    }

    public function _getResponse()
    {
        return $this->_response;
    }

    public function _init()
    {

    }
}