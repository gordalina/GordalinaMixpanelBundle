GordalinaMixpanelBundle
=====================

[![Build Status](https://github.com/gordalina/GordalinaMixpanelBundle/workflows/ci/badge.svg)](https://github.com/gordalina/GordalinaMixpanelBundle/actions)

Integration of the [**Mixpanel**](https://github.com/mixpanel/mixpanel-php) library
into Symfony.

- [Installation](#installation)
- [Usage](#usage)
- [Killer Feature](#killer-feature)
- [Sending people information to Mixpanel](#sending-people-information-to-mixpanel)
- [Attributes](#attributes)
    - [Mixpanel Actions](#mixpanel-actions)
    - [Value Placeholders](#value-placeholders)
- [Send an event based on a condition](#send-an-event-based-on-a-condition)
- [MixpanelEvent](#mixpanelevent)
- [Override Props in all Attributes](#override-props-in-all-attributes)
- [Upgrading from 6.x](#upgrading-from-6x)
    - [If you still use Doctrine annotations](#if-you-still-use-doctrine-annotations)
    - [If you already use attributes](#if-you-already-use-attributes)
    - [Manual migration](#manual-migration)
- [Symfony Profiler Integration](#symfony-profiler-integration)
- [Reference Configuration](#reference-configuration)
- [Spec](#spec)
- [License](#license)

Installation
------------

This version requires PHP `^8.2` and Symfony `^6.4 || ^7.4 || ^8.0`.

Require [`gordalina/mixpanel-bundle`](https://packagist.org/packages/gordalina/mixpanel-bundle) using composer

```sh
$ php composer.phar require gordalina/mixpanel-bundle:~7.0
```

Or require
[`gordalina/mixpanel-bundle`](https://packagist.org/packages/gordalina/mixpanel-bundle)
to your `composer.json` file:


```json
{
    "require": {
        "gordalina/mixpanel-bundle": "~7.0"
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

Enable the bundle's configuration in `config/packages/gordalina_mixpanel.yaml`:

``` yaml
# config/packages/gordalina_mixpanel.yaml

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
    Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO
);
```

You can find more documentation on Symfony website: [How to Configure Symfony to Work behind a Load Balancer or a Reverse Proxy](https://symfony.com/doc/current/deployment/proxies.html#solution-settrustedproxies)

### Killer Feature

Track an event with a single attribute

```php
<?php
// CheckoutController.php

use Gordalina\MixpanelBundle\Attribute as Mixpanel;

class CheckoutController
{
    #[Mixpanel\Track('View Checkout')]
    public function view(Request $request)
    {
        // ...
    }
```

### Sending people information to Mixpanel

Mixpanel allows you to track your user's behaviours, but also some user information.
When using attributes which require the [distinct_id](https://help.mixpanel.com/hc/en-us/articles/115004509406-What-is-distinct-id-),
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
            $email: email

        # All possible properties
        YourAppBundle\Entity\User:
            id: id
            $first_name: first_name
            $last_name: last_name
            $email: email
            $phone: phone
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

use Gordalina\MixpanelBundle\Attribute as Mixpanel;

class UserController
{
    #[Mixpanel\UpdateUser]
    public function userUpdated(User $user, Request $request)
    {
        // ...
    }
```

In the following example, we call UpdateUser, which sends all information registered
in the configuration above, but we override the `id` property with an expression
that evaluates the user id.
The `Expression` placeholder uses [ExpressionLanguage](http://symfony.com/doc/current/components/expression_language/index.html)
for evaluation.

```php
<?php
// OrderController.php

use Gordalina\MixpanelBundle\Attribute as Mixpanel;

class OrderController
{
    #[Mixpanel\Track('Order Completed', props: [
        'user_id' => new Mixpanel\Expression('user.getId()'),
    ])]
    #[Mixpanel\TrackCharge(
        amount: new Mixpanel\Expression('order.getAmount()'),
        id: 324,
    )]
    public function orderCompleted(Order $order, Request $request)
    {
        // ...
    }
```


### Attributes

Every action below can be placed on a controller class or on a controller method,
and can be repeated as many times as needed.

#### Mixpanel Actions

**Events**
- `#[Mixpanel\Register(prop: 'visits', value: 3)]`
- `#[Mixpanel\Track(event: 'name', props: ['firstTime' => true])]`
- `#[Mixpanel\Unregister(prop: '$email')]`

**Engagement**
- `#[Mixpanel\Append(prop: 'fruits', value: 'apples' [, id: 324, ignoreTime: false])]`
- `#[Mixpanel\ClearCharges([id: 324, ignoreTime: false])]`
- `#[Mixpanel\DeleteUser([id: 324, ignoreTime: false])]`
- `#[Mixpanel\Increment(prop: 'visits', value: 3 [, id: 324, ignoreTime: false])]`
- `#[Mixpanel\Remove(props: ['$email' => 'a@b.com'] [, id: 324, ignoreTime: false])]`
- `#[Mixpanel\Set(props: ['firstTime' => true] [, id: 324, ignoreTime: false])]`
- `#[Mixpanel\SetOnce(props: ['firstTime' => true] [, id: 324, ignoreTime: false])]`
- `#[Mixpanel\TrackCharge(amount: '20.0' [, id: 697, ignoreTime: false])]`
- `#[Mixpanel\UpdateUser]`

**Note**: Required arguments come first, so they can also be passed positionally,
e.g. `#[Mixpanel\Track('name')]` or `#[Mixpanel\Set(['firstTime' => true])]`.

**Note**: All `id` arguments can be omitted, as they will be set with the id of
the currently authenticated user.

**Note**: Every action also accepts a `project` argument to target a project other
than the default one.

#### Value Placeholders

These are not attributes, they are objects you pass as an argument value of an
action and that get resolved at runtime:

- `new Mixpanel\Id()` — the current user's distinct id
- `new Mixpanel\Expression('<expression>')` — the result of an ExpressionLanguage expression

```php
#[Mixpanel\Set(props: ['plan' => 'pro'], id: new Mixpanel\Id())]
```

### Send an event based on a condition

In all attributes, you can add conditions with [Expression Language](https://symfony.com/doc/current/components/expression_language.html)
```php
#[Mixpanel\Track('Your event', condition: "request.getMethod() in ['GET']")]
public function yourAction()
{
  // ...
}
```

**Note**: By default condition is true and is not required.

### MixpanelEvent

You can also send an event through symfony events when the attributes are not sufficient like this:
```php
#In controller
namespace myNamespace;

use Gordalina\MixpanelBundle\Attribute as Mixpanel;
use Gordalina\MixpanelBundle\Mixpanel\Event\MixpanelEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

// ...

public function edit(User $user, EventDispatcherInterface $eventDispatcher, Request $request)
{
    // Your code
    $attribute = new Mixpanel\Track('My event', [
        'prop 1' => 'data 1',
        'prop 2' => 'data 2',
    ]);

    $eventDispatcher->dispatch(new MixpanelEvent($attribute, $request));
    // Rest of your code
}
```

### Override Props in all Attributes

In all your attributes, you can have something like this:
```php
    #[Mixpanel\Track('Your event', props: [
        'user_id' => new Mixpanel\Expression('user.getId()'),
    ])]
    public function yourAction()
    {
        // ...
    }
```
It can be annoying to always have to put the same properties in your attributes. The functioning of the events allows us to avoid that.

```php
namespace YourNamespace;

use Gordalina\MixpanelBundle\Attribute;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class MixpanelListener
{
    public function __construct(private Security $security)
    {
    }

    public function onKernelController(ControllerEvent $event): void
    {
        foreach ($event->getAttributes() as $attributes) {
            foreach ($attributes as $attribute) {
                if ($attribute instanceof Attribute\Action && property_exists($attribute, 'props')) {
                    $attribute->props['user_id'] = $this->security->getUser()->getId();
                }
            }
        }
    }
}
```

And in your config:
```yaml
# config/services.yaml

services:
    YourNamespace\MixpanelListener:
        tags:
            - { name: kernel.event_listener, event: kernel.controller, method: onKernelController, priority: -200 }
```

**Note**: the priority must be higher than `-256`, which is the priority the bundle's
own listener runs at. `ControllerEvent::getAttributes()` returns the very instances
the bundle will read, so mutating them here is enough.

**Upgrading**: if such a listener used a `Doctrine\Common\Annotations\Reader` to
reach the annotations, it must be rewritten as above. A reader never sees PHP
attributes, so it stops enriching anything — silently, with the events still being
sent without the added properties.

### Upgrading from 6.x

Two things changed, and neither fails loudly.

**Doctrine annotations are no longer read.** They were deprecated in 6.0 and are
gone in 7.0: the bundle no longer depends on `doctrine/annotations`, and the
`gordalina_mixpanel.annotation_reader` service it used to register is gone. An
un-migrated `@Mixpanel\Track(...)` docblock is just a comment, so the controller
keeps working and the event is silently never sent.

**The `Annotation` namespace is now `Attribute`.** The 14 action classes keep their
names, but two things around them changed:

| 6.x | 7.0 |
| --- | --- |
| `Gordalina\MixpanelBundle\Annotation` | `Gordalina\MixpanelBundle\Attribute` |
| `Annotation\Annotation` (base class) | `Attribute\Action` |
| `MixpanelEvent::getAnnotation()` | `MixpanelEvent::getAttribute()` |

That one *does* fail loudly — a missing class or method is a hard error — so you
cannot ship it half-done by accident.

Pick the path that matches your code. Both assume you have already bumped the
bundle to 7.0, since the tooling needs the new classes to exist.

#### If you still use Doctrine annotations

One Rector config does both migrations in a single pass: docblocks become
attributes *and* they point at the new namespace. The `as Mixpanel` alias is
rewritten in place, so the call sites keep the style the docs use.

```php
// rector.php
declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\AnnotationToAttributeRector;
use Rector\Php80\ValueObject\AnnotationToAttribute;

return RectorConfig::configure()
    ->withPaths([__DIR__.'/src'])
    ->withConfiguredRule(AnnotationToAttributeRector::class, [
        // events
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\Register', 'Gordalina\MixpanelBundle\Attribute\Register'),
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\Track', 'Gordalina\MixpanelBundle\Attribute\Track'),
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\Unregister', 'Gordalina\MixpanelBundle\Attribute\Unregister'),
        // engagement
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\Append', 'Gordalina\MixpanelBundle\Attribute\Append'),
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\ClearCharges', 'Gordalina\MixpanelBundle\Attribute\ClearCharges'),
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\DeleteUser', 'Gordalina\MixpanelBundle\Attribute\DeleteUser'),
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\Increment', 'Gordalina\MixpanelBundle\Attribute\Increment'),
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\Remove', 'Gordalina\MixpanelBundle\Attribute\Remove'),
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\Set', 'Gordalina\MixpanelBundle\Attribute\Set'),
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\SetOnce', 'Gordalina\MixpanelBundle\Attribute\SetOnce'),
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\TrackCharge', 'Gordalina\MixpanelBundle\Attribute\TrackCharge'),
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\UpdateUser', 'Gordalina\MixpanelBundle\Attribute\UpdateUser'),
        // value placeholders, for their nested usages
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\Expression', 'Gordalina\MixpanelBundle\Attribute\Expression'),
        new AnnotationToAttribute('Gordalina\MixpanelBundle\Annotation\Id', 'Gordalina\MixpanelBundle\Attribute\Id'),
    ]);
```

```sh
$ vendor/bin/rector --dry-run   # review first
$ vendor/bin/rector
```

```php
// before
use Gordalina\MixpanelBundle\Annotation as Mixpanel;

/**
 * @Mixpanel\Track("Order Completed", props={
 *      "user_id": @Mixpanel\Expression("user.getId()")
 * })
 * @Mixpanel\Set(id=@Mixpanel\Id, props={"plan": "pro"})
 */

// after
use Gordalina\MixpanelBundle\Attribute as Mixpanel;

#[Mixpanel\Track('Order Completed', props: ['user_id' => new Mixpanel\Expression('user.getId()')])]
#[Mixpanel\Set(id: new Mixpanel\Id(), props: ['plan' => 'pro'])]
```

Rector only rewrites an annotation whose target class it can load and finds marked
`#[Attribute]`, and it skips it silently otherwise. So if the dry-run reports no
change on a controller you know has annotations, the bundle is not autoloadable
from where you ran it — that, or a previous run cached the file (`--clear-cache`).

Versions 6.0 to 6.x shipped a ready-made set for this
(`MixpanelSetList::ANNOTATIONS_TO_ATTRIBUTES`). It was removed in 7.0 — the config
above supersedes it, and lives in the one place where the Rector version in use is
actually known.

#### If you already use attributes

Since every action class kept its name, the aliased style migrates with a single
`use` line per file:

```sh
$ grep -rl 'MixpanelBundle\\Annotation' src/ \
    | xargs sed -i 's/MixpanelBundle\\Annotation/MixpanelBundle\\Attribute/g'
```

Then fix the two renames by hand — `Annotation\Annotation` becomes
`Attribute\Action`, `getAnnotation()` becomes `getAttribute()` — which only affects
listeners of your own that inspect Mixpanel attributes. Or let Rector do all three:

```php
// rector.php
declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Renaming\ValueObject\MethodCallRename;

return RectorConfig::configure()
    ->withPaths([__DIR__.'/src'])
    ->withImportNames(importShortClasses: false)
    ->withConfiguredRule(RenameClassRector::class, [
        'Gordalina\MixpanelBundle\Annotation\Annotation' => 'Gordalina\MixpanelBundle\Attribute\Action',
        'Gordalina\MixpanelBundle\Annotation\Register' => 'Gordalina\MixpanelBundle\Attribute\Register',
        'Gordalina\MixpanelBundle\Annotation\Track' => 'Gordalina\MixpanelBundle\Attribute\Track',
        'Gordalina\MixpanelBundle\Annotation\Unregister' => 'Gordalina\MixpanelBundle\Attribute\Unregister',
        'Gordalina\MixpanelBundle\Annotation\Append' => 'Gordalina\MixpanelBundle\Attribute\Append',
        'Gordalina\MixpanelBundle\Annotation\ClearCharges' => 'Gordalina\MixpanelBundle\Attribute\ClearCharges',
        'Gordalina\MixpanelBundle\Annotation\DeleteUser' => 'Gordalina\MixpanelBundle\Attribute\DeleteUser',
        'Gordalina\MixpanelBundle\Annotation\Increment' => 'Gordalina\MixpanelBundle\Attribute\Increment',
        'Gordalina\MixpanelBundle\Annotation\Remove' => 'Gordalina\MixpanelBundle\Attribute\Remove',
        'Gordalina\MixpanelBundle\Annotation\Set' => 'Gordalina\MixpanelBundle\Attribute\Set',
        'Gordalina\MixpanelBundle\Annotation\SetOnce' => 'Gordalina\MixpanelBundle\Attribute\SetOnce',
        'Gordalina\MixpanelBundle\Annotation\TrackCharge' => 'Gordalina\MixpanelBundle\Attribute\TrackCharge',
        'Gordalina\MixpanelBundle\Annotation\UpdateUser' => 'Gordalina\MixpanelBundle\Attribute\UpdateUser',
        'Gordalina\MixpanelBundle\Annotation\Expression' => 'Gordalina\MixpanelBundle\Attribute\Expression',
        'Gordalina\MixpanelBundle\Annotation\Id' => 'Gordalina\MixpanelBundle\Attribute\Id',
    ])
    ->withConfiguredRule(RenameMethodRector::class, [
        new MethodCallRename('Gordalina\MixpanelBundle\Mixpanel\Event\MixpanelEvent', 'getAnnotation', 'getAttribute'),
    ]);
```

Note that `RenameClassRector` replaces the `as Mixpanel` alias with one import per
class (`#[Mixpanel\Track]` becomes `#[Track]`), which is why the `sed` above is
worth preferring if you like the prefixed style.

#### Manual migration

Converting annotations by hand, three things to watch out for:

- Nested actions such as `@Mixpanel\Id` or `@Mixpanel\Expression(...)` become real
  object instantiations: `new Mixpanel\Id()`, `new Mixpanel\Expression('...')`
- `@Mixpanel\Remove` takes a `props` array, not a `prop` scalar — the previous
  README documented this incorrectly
- Any listener of your own that reads Mixpanel annotations through a
  `Doctrine\Common\Annotations\Reader` must move to
  [`ControllerEvent::getAttributes()`](#override-props-in-all-attributes) at the
  same time, otherwise it stops seeing the migrated controllers without any error

### Symfony Profiler Integration

Mixpanel bundle additionally integrates with the Symfony profiler. You can
check number of events and engagements sent, total execution time and other information.

![Example
Toolbar](https://raw.githubusercontent.com/gordalina/GordalinaMixpanelBundle/master/Resources/doc/panel.png)


Reference Configuration
-----------------------

You'll find the reference configuration below:

``` yaml
# config/packages/gordalina_mixpanel.yaml

gordalina_mixpanel:
    enabled: true                                          # defaults to true
    enable_profiler: '%kernel.debug%'                      # defaults to %kernel.debug%
    auto_update_user: '%kernel.debug%'                     # defaults to %kernel.debug%
    throw_on_user_data_attribute_failure: '%kernel.debug%' # defaults to %kernel.debug%
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
            $email: email

        # All possible properties
        YourAppBundle\Entity\User:
            id: id
            $first_name: first_name
            $last_name: last_name
            $email: email
            $phone: phone
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
