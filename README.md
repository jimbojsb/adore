Adore
=====

Adore is an extremely lightweight implementation of the Action-Domain-Responder pattern

Background
----------
Action-Domain-Responder is an "improvement" on the traditional idea of MVC as it relates to web applications. Paul Jones
defines it in detail in this paper. The paper defines patterns and separation of concerns, but does not provide any
concrete implementation details, and specifically addresses a lack of "glue" for the various concepts. Adore attempts
to be that glue, providing routing, dispatching, and response handling. Additionally, Adore provides a way to wire
together the action and response components.  Furthermore, Adore subscribes to the Ed Finkler's Micro-PHP Manifesto. Adore
is provided in it's entirety as one PHP file containing one class and two traits.

Dependencies
------------
In an attempt to not reinvent the wheel, Adore relies on ```Aura\Web``` and ```Aura\Router``` rather than providing a
bespoke implementation of these functions. This allows Adore to be extremely concise and focus on solving only the unsolved
portion of the problem.

Installation
------------
Adore, while provided as a single PHP file, does have external dependencies. As such, the only recommended installation
method is with Composer. Add the following to your ```composer.json```

```json
{
    require: {
        "jimbojsb/adore": "dev-master"
    }
}