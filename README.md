GordalinaMixpanelBundle
=====================

[![Build Status](https://travis-ci.org/gordalina/GordalinaMixpanelBundle.svg?branch=master)](https://travis-ci.org/gordalina/GordalinaMixpanelBundle)

Integration of the [**Mixpanel**](https://github.com/mixpanel/mixpanel-php) library
into Symfony.


* [Installation](#installation)
* [Usage](#usage)
  * [Killer Feature](#killer-feature)
  * [Sending people information to Mixpanel](#sending-people-information-to-mixpanel)
  * [Annotations](#annotations)
  * [Symfony2 Profiler Integration](#symfony2-profiler-integration)
* [Reference Configuration](#reference-configuration)
* [License](#license)


Installation
------------

Require [`gordalina/mixpanel-bundle`](https://packagist.org/packages/gordalina/mixpanel-bundle) using composer

```sh
$ php composer.phar require gordalina/mixpanel-bundle:~3.0
```

Or require
[`gordalina/mixpanel-bundle`](https://packagist.org/packages/gordalina/mixpanel-bundle)
to your `composer.json` file:


```json
{
    "require": {
        "gordalina/mixpanel-bundle": "~3.0"
    }
}
```

Register the bundle in `config/bundles.php`:

```php
// config/bundles.php
    return [
        // ...
        Gordalina\MixpanelBundle\GordalinaMixpanelBundle::class => ['all' => true],
    ];
}
```

Enable the bundle's configuration in `app/config/config.yml`:

``` yaml
# app/config/config.yml

gordalina_mixpanel:
    projects:
        default:
            token: xxxxxxxxxxxxxxxxxxxx
```

Usage
-----

This bundle registers a `gordalina_mixpanel.default`, `mixpanel.default` and `mixpanel`
service which is an instance of `Mixpanel` (from the official library).
You'll be able to do whatever you want with it.

**NOTE:** This bundle sends your client's ip address automatically. If you have
a reverse proxy in you servers you should set it in your front controller `public/index.php`:

```php
// public/index.php
Request::setTrustedProxies(
    // the IP address (or range) of your proxy
    ['192.0.0.1', '10.0.0.0/8'],
    Request::HEADER_X_FORWARDED_ALL
);
```

You can find more documentation on Symfony website: [How to Configure Symfony to Work behind a Load Balancer or a Reverse Proxy](https://symfony.com/doc/current/deployment/proxies.html#solution-settrustedproxies)

### Killer Feature

Track an event with a single annotation

```php
<?php
// CheckoutController.php

use Gordalina\MixpanelBundle\Annotation as Mixpanel;

class CheckoutController
{
    /**
     * @Mixpanel\Track("View Checkout")
     */
    public function view(Request $request)
    {
        // ...
    }
```

### Sending people information to Mixpanel

Mixpanel allows you to track your user's behaviours, but also some user information.
When using annotations which require the [distinct_id](https://help.mixpanel.com/hc/en-us/articles/115004509406-What-is-distinct-id-),
this will be set automatically. This is done automatically provided you have configured it properly.
You are able to override this value if you wish.

```yaml
# config/packages/gordalina_mixpanel.yaml

gordalina_mixpanel:
    projects:
        default:
            token: xxxxxxxxxx
    users:
        Symfony\Component\Security\Core\User\UserInterface:
            id: username
            email: email

        # All possible properties
        YourAppBundle\Entity\User:
            id: id
            first_name: first_name
            last_name: last_name
            email: email
            phone: phone
            extra_data:
                - { key: whatever, value: test }
```

This bundle uses property access to get the values out of the user object, so
event if you dont have a `first_name` property, but have a `getFirstName` method
it will work.

**NOTE:** ``extra_data`` corresponding non-default properties in Mixpanel user profile

```php
<?php
// UserController.php

use Gordalina\MixpanelBundle\Annotation as Mixpanel;

class UserController
{
    /**
     * @Mixpanel\UpdateUser()
     */
    public function userUpdated(User $user, Request $request)
    {
        // ...
    }
```

In the following example, we call UpdateUser, which sends all information registered
in the configuration above, but we override the `id` property with an expression
that evaluates the user id.
The `@Expression` annotation uses [ExpressionLanguage](http://symfony.com/doc/current/components/expression_language/index.html)
for evaluation.

```php
<?php
// OrderController.php

use Gordalina\MixpanelBundle\Annotation as Mixpanel;

class OrderController
{
    /**
     * @Mixpanel\Track("Order Completed", props={
     *      "user_id": @Mixpanel\Expression("user.getId()")
     * })
     * @Mixpanel\TrackCharge(
     *      id=324"),
     *      amount=@Mixpanel\Expression("order.getAmount()")
     * )
     */
    public function orderCompleted(Order $order, Request $request)
    {
        // ...
    }
```


### Annotations

#### Mixpanel Actions

**Events**
- `@Mixpanel\Register(prop="visits", value=3)`
- `@Mixpanel\Track(event="name", props={ "firstTime": true })`
- `@Mixpanel\Unregister(prop="email")`

**Engagement**
- `@Mixpanel\Append(id=324, prop="fruits", value="apples" [, ignoreTime=false])`
- `@Mixpanel\ClearCharges(id=324 [, ignoreTime=false])`
- `@Mixpanel\DeleteUser(id=324 [, ignoreTime=false])`
- `@Mixpanel\Increment(id=324, prop="visits", value=3 [, ignoreTime=false])`
- `@Mixpanel\Remove(id=324, prop="email")`
- `@Mixpanel\Set(id=324, props={ "firstTime": true } [, ignoreTime=false])`
- `@Mixpanel\SetOnce(id=324, props={ "firstTime": true } [, ignoreTime=false])`
- `@Mixpanel\TrackCharge(id=697, amount="20.0" [, ignoreTime=false])`

#### Custom Annotations
- `@Mixpanel\Id()`
- `@Mixpanel\Expression(expression="<expression>")`
- `@Mixpanel\UpdateUser()`

**Note**: The first argument is not required to specify the name explicitly,
e.g: `@Mixpanel\Expression("<expression>")` or `@Mixpanel\Set("<property>", value="<value>")`.

**Note**: All `id` properties can be omitted, as they will be set with the id of
the current user in `security.context`

### MixpanelEvent

You can also send an event through symfony events when the annotations are not sufficient like this:
```php
#In controller
namespace myNamespace;

use Gordalina\MixpanelBundle\Annotation as Annotation;
use Gordalina\MixpanelBundle\Mixpanel\Event\MixpanelEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

// ...

public function edit(User $user, EventDispatcherInterface $eventDispatcher, Request $request)
{
    // Your code
    $annotation = new Annotation\Track();
    $annotation->event = 'My event';
    $annotation->props = [
        'prop 1' => 'data 1',
        'prop 2' => 'data 2',
    ];
    
    $eventDispatcher->dispatch(new MixpanelEvent($annotation, $request));
    // Rest of your code
}
```

### Override Props in all Annotations

In all your annotations, you can have something like this:
```php
    /**
     * @Mixpanel\Track("Your event", props={
     *      "user_id": @Mixpanel\Expression("user.getId()")
     * })
     */
    public function yourAction()
    {
        // ...
    }
```
It can be annoying to always have to put the same properties in your annotations. The functioning of the events allows us to avoid that.

```php
namespace YourNamespace;

use Doctrine\Common\Annotations\Reader;
use Gordalina\MixpanelBundle\Annotation;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Security\Core\Security;

class MixpanelListener
{
    private $annotationReader;
    private $security;

    public function __construct(Reader $annotationReader, Security $security)
    {
        $this->annotationReader = $annotationReader;
        $this->security         = $security; 
    }

    public function onKernelController(ControllerEvent $event)
    {
        if (!\is_array($controller = $event->getController())) {
            return;
        }

        $className = \get_class($controller[0]);
        $object    = new \ReflectionClass($className);
        $method    = $object->getMethod($controller[1]);

        $classAnnotations  = $this->annotationReader->getClassAnnotations($object);
        $methodAnnotations = $this->annotationReader->getMethodAnnotations($method);

        foreach ([$classAnnotations, $methodAnnotations] as $collection) {
            foreach ($collection as $annotation) {
                if ($annotation instanceof Annotation\Annotation && property_exists($annotation, 'props')) {
                    $annotation->props['user_id'] = $this->security->getUser()->getId();
                }
            }
        }
    }
}
```

And in your config:
```yaml
    YourNamespace\MixpanelListener:
        tags:
            - { name: kernel.event_listener, event: kernel.controller, method: onKernelController, priority: -200 }
```


### Symfony Profiler Integration

Mixpanel bundle additionally integrates with Symfony2 profiler. You can
check number of events and engagements sent, total execution time and other information.

![Example
Toolbar](https://raw.githubusercontent.com/gordalina/GordalinaMixpanelBundle/master/Resources/doc/panel.png)


Reference Configuration
-----------------------

You'll find the reference configuration below:

``` yaml
# app/config/config*.yml

gordalina_mixpanel:
    enabled: true                                        # defaults to true
    enable_profiler: %kernel.debug%                      # defaults to %kernel.debug%
    auto_update_user: %kernel.debug%                     # defaults to %kernel.debug%
    throw_on_user_data_attribute_failure: %kernel.debug% # defaults to %kernel.debug%
    projects:
        default:
            token: xxxxxxxxxxxxxxxxxxxxxxxxxxxx # required
            options:
                max_batch_size:  50               # the max batch size Mixpanel will accept is 50,
                max_queue_size:  1000             # the max num of items to hold in memory before flushing
                debug:           false            # enable/disable debug mode (logs messages to error_log)
                consumer:        curl             # which consumer to use (curl, file, socket)
                consumers:
                    custom_consumer:  ConsumerStrategies_CustomConsumConsumer # Your consumer, update above to use it
                host:            api.mixpanel.com # the host name for api calls
                events_endpoint: /track           # host relative endpoint for events
                people_endpoint: /engage          # host relative endpoint for people updates
                use_ssl:         true             # use ssl when available
                error_callback:  'Doctrine\Common\Util\Debug::dump'

        minimum_configuration:
            token: xxxxxxxxxxxxxxxxxxxxxxxxxxxx
    users:
        Symfony\Component\Security\Core\User\UserInterface:
            id: username
            email: email

        # All possible properties
        YourAppBundle\Entity\User:
            id: id
            first_name: first_name
            last_name: last_name
            email: email
            phone: phone
```

Spec
----

In order to run the specs install all components with composer and run:
```
./bin/phpspec run
```

License
-------

This bundle is released under the MIT license. See the complete license in the
bundle:

[Resources/meta/LICENSE](https://github.com/gordalina/GordalinaMixpanelBundle/blob/master/Resources/meta/LICENSE)
