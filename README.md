# Adore

Adore is an extremely lightweight implementation of the Action-Domain-Responder pattern

#### Background
Action-Domain-Responder is an "improvement" on the traditional idea of MVC as it relates to web applications. Paul Jones
defines it in detail in this paper. The paper defines patterns and separation of concerns, but does not provide any
concrete implementation details, and specifically addresses a lack of "glue" for the various concepts. Adore attempts
to be that glue, providing routing, dispatching, and response handling. Additionally, Adore provides a way to wire
together the action and response components.  Furthermore, Adore subscribes to the Ed Finkler's Micro-PHP Manifesto. Adore
is provided in it's entirety as one PHP file containing one class and two traits.

#### Dependencies
In an attempt to not reinvent the wheel, Adore relies on ```Aura\Web``` and ```Aura\Router``` rather than providing a
bespoke implementation of these functions. This allows Adore to be extremely concise and focus on solving only the unsolved
portion of the problem.

## Installation & Runtime Configuration
Adore, while provided as a single PHP file, does have external dependencies. As such, the only recommended installation
method is with Composer. Add the following to your ```composer.json```

```json
{
    "require": {
        "jimbojsb/adore": "dev-master"
    }
}
```

#### Bootstrapping
The first step to using Adore is to create a new instance of ```Adore\Application```. This would normally happen in your ```index.php```.

```php
require_once __DIR__ . '/../vendor/autoload.php';

$app = new Adore\Application;
```

#### Wiring Actions & Responders
Adore assumes you'll generally provide your own methods for loading Action and Responder classes, presumably PSR autoloading. Adore still needs to know how to resolve the names of these classes. This is done with factory closures. The following examples assumes but does not enforce a basic application namespace organization.

```php
$app->setActionFactory(function($actionName) {
    $properActionName = ucfirst($actionName) . "Action";
    $className = "\\MyApp\\Action\\$properActionName";
    return new $className;
});

$app->setResponderFactory(function($responderName) {
    $properResponderName = ucfirst($responderName) . "Responder";
    $className = "\\MyApp\\Responder\\$properResponderName";
    return new $className;
});

```

Any initial dependency injection needed for your Actions & Responders should be handled within these closures.

*NOTE: The responder factory your specify will be wrapped in another closure to aid in injecting a ```Response``` object.*

#### Error Handling
Adore attempts to provide some rudimentary error handling capabilities. This is done by providing an action name to dispatch if no route is matched, and additional one to dispatch in the case an exception is thrown during execution. The actual names of these actions can be any name you like, and they are resolved through the same action factory provided by you.

```php
$app->setErrorAction("Error");
$app->setNotFoundAction("Notfound");
```

#### Routing
Adore proxies ```Aura\Router```. For a full routing syntax, refer to the ```Aura\Router``` documentation. Routes take at a minumum, a path to match and an action name. Optionally, you may specificy which HTTP verbs routes match to, as well as additional parameters to be injection into the action.

```php
// Route with plain path matching
$app->addRoute("/", "Homepage");

// Route with url parameters
$app->addRoute("/blog/post/{post_slug}", "BlogPost");

// Route that will only match on a POST request
$app->addRoute("/login", "Login", ["POST"]);

// Route with additional hard-coded parameters
$app->addRoute("/about", "StaticContent", ["file" => "about.md"]);
```

#### Dispatching
Once you've configured your ```Adore\Application``` instance, actually dispatching it is as simple as calling:

```php
$app->run();
```

The order of operations for dispatching a request is roughly as follows:

1. Create a new ```Aura\Web\Request``` object
2. Create a new ```Aura\Router``` object and load it with the route definitions
3. Route the request
4. Create the appropriate action object using the supplied action factory
5. Inject params, request, responder factory onto the action
6. Call ```_init()``` on the action
7. Dispatch the action, which should return a responder
8. Invoke the responder
9. Get the response object from the responder and send it to the client


# Creating Actions & Responders
Adore attempts to have a very small footprint on your code. It provides traits instead of interfaces or abstract classes so your application inheritance tree can be completely up to you. The traits that Adore provides are mainly for dependency injection and convenience. They are not strictly required as PHP cannot type check traits, but should you choose not to use them, you would need to implement their methods and properties manually. 

Actions and Responders in Adore are designed to be invokeable objects. The main entry point for execution of your code will be the ```__invoke``` method.

All methods and properties on the Adore traits are prefixed with _ to avoid any name conflicts with your code.

#### Actions
An action should be a simple PHP class that uses ```Adore\ActionTrait``` and contains an ```_invoke()``` method. Additionally,
you may provide an ```_init()``` method if you need to do additional setup before dispatch. ```_init()``` is called after 
the action has been fully wired. *Your action is expected to return an invokeable responder.*
 
```php 
class MyAction
{
    use \Adore\ActionTrait;
    
    public function __invoke()
    {
        // business logic here
        return new Responder($data);
    }
}
```

If your ```_invoke``` method has arguments, Adore will attempt to match those named properties with keys from the request
parameters such that the params are passed to your function, as a convenience.

The ```Adore\ActionTrait``` provides the following protected properties:

* ```_params``` - An array of all parameters derived from the routing process
* ```_request``` - An instance of ```Aura\Web\Request``` that represents the current HTTP request context

The ```Adore\ActionTrait``` has a reference to the Responder factory, and provides a helper method ```->getResponder($responderName)```
that can generate wired and initialized responders from inside your action. This is the preferred method to instantiate a Responder.

#### Responders

A responder should be a simple PHP class that uses ```Adore\ResponderTrait``` and contains an ```_invoke()``` method. Additionally,
you may provide an ```_init()``` method if you need to do additional setup before dispatch. ```_init()``` is called after 
the responder has been fully wired. Your responder will be pre-injected with a ```Aura\Web\Response``` object. It should 
act on that object appropriately. After invoking your responder, Adore will use that response object to send a properly
formed HTTP response to the client.
 
```php 
class MyResponder
{
    use \Adore\ResponderTrait;
    
    public function __invoke()
    {
        // presentation logic here
        $this->_response->content->set("Hello World");
    }
}
```

The ```Adore\ResponderTrait``` provides the following protected properties:

* ```_response``` - An instance of ```Aura\Web\Request```. For a full listing of the functionality of the ```Aura\Web\Response``` 
object, please see the Aura.Web documentation.