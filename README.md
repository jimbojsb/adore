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

# Creating Actions & Responders
Adore attempts to have a very small footprint on your code. It provides traits instead of interfaces or abstract classes so your application inheritance tree can be completely up to you. The traits that Adore provides are mainly for dependency injection and convenience. They are not strictly required as PHP cannot type check traits, but should you choose not to use them, you would need to implement their methods and properties manually. 

Actions and Responders in Adore are designed to be invokeable objects. The main entry point for execution of your code will be the ```__invoke``` method.

```php 
class MyAction
{
    use \Adore\ActionTrait;
    
    public function __invoke()
    {
        // business logic here
    }
}
