## [v5.0.3](https://github.com/gordalina/GordalinaMixpanelBundle/releases/tag/5.0.3)

[5.0.2...5.0.3](https://github.com/gordalina/GordalinaMixpanelBundle/compare/5.0.2...5.0.3)

### Fixes

- Don't send DateTimeInterface as value for properties in extra_data [#36](https://github.com/gordalina/GordalinaMixpanelBundle/pull/36)

## [v5.0.2](https://github.com/gordalina/GordalinaMixpanelBundle/releases/tag/5.0.2)

[5.0.1...5.0.2](https://github.com/gordalina/GordalinaMixpanelBundle/compare/5.0.1...5.0.2)

### Fixes

- Registry does not match doctrine proxies [#33](https://github.com/gordalina/GordalinaMixpanelBundle/pull/33)

## [v5.0.1](https://github.com/gordalina/GordalinaMixpanelBundle/releases/tag/5.0.1)

[5.0.0...5.0.1](https://github.com/gordalina/GordalinaMixpanelBundle/compare/5.0.0...5.0.1)

### Fixes

- `getUserProperties()` returns null if instance is a doctrine proxy [#32](https://github.com/gordalina/GordalinaMixpanelBundle/pull/32)

## [v5.0.0](https://github.com/gordalina/GordalinaMixpanelBundle/releases/tag/5.0.0)

[4.0.0...5.0.0](https://github.com/gordalina/GordalinaMixpanelBundle/compare/4.0.0...5.0.0)

### Breaking Changes

- Dropped support for PHP `<8.0` & Symfony `<5.4` [#30](https://github.com/gordalina/GordalinaMixpanelBundle/pull/30)

## [v4.0.0](https://github.com/gordalina/GordalinaMixpanelBundle/releases/tag/4.0.0)

[3.5.0...4.0.0](https://github.com/gordalina/GordalinaMixpanelBundle/compare/3.5.0...4.0.0)

### Breaking Changes

- Dropped support for Symfony `<5.1.5`

### Features

- Register LoginSuccessEvent for new authenticator system. [#26](https://github.com/gordalina/GordalinaMixpanelBundle/pull/26)

## [v3.5.0](https://github.com/gordalina/GordalinaMixpanelBundle/releases/tag/3.5.0)

[3.4.0...3.5.0](https://github.com/gordalina/GordalinaMixpanelBundle/compare/3.4.0...3.5.0)

### Features

- Added support for PHP 8.0
- Switch to GitHub Actions from Travis CI

## [v3.4.0](https://github.com/gordalina/GordalinaMixpanelBundle/releases/tag/3.4.0)

[3.3.0...3.4.0](https://github.com/gordalina/GordalinaMixpanelBundle/compare/3.3.0...3.4.0)

### Features

- Added a new configuration option `send_user_ip` which decides whether to send the user ip to mix panel. [#19](https://github.com/gordalina/GordalinaMixpanelBundle/pull/19)

## [v3.3.0](https://github.com/gordalina/GordalinaMixpanelBundle/releases/tag/3.3.0)

[3.2.0...3.3.0](https://github.com/gordalina/GordalinaMixpanelBundle/compare/3.2.0...3.3.0)

### Security Fixes

- Upgraded `symfony/http-kernel` to `^4.4.13 || 5.1.5` to address [CVE-2020-15094](https://github.com/advisories/GHSA-754h-5r27-7x3r)

## [v3.2.0](https://github.com/gordalina/GordalinaMixpanelBundle/releases/tag/3.2.0)

[3.1.0...3.2.0](https://github.com/gordalina/GordalinaMixpanelBundle/compare/3.1.0...3.2.0)

### Features

- Used `Profile properties`. [#16](https://github.com/gordalina/GordalinaMixpanelBundle/pull/16) by @RomulusED69

### Deprecations

- In your config file replace

```yaml
Symfony\Component\Security\Core\User\UserInterface:
  id: username
  email: email
App\Entity\User:
  id: id
  first_name: first_name
  last_name: last_name
  email: email
  phone: phone
```

By

```yaml
Symfony\Component\Security\Core\User\UserInterface:
  id: username
  $email: email
App\Entity\User:
  id: id
  $first_name: first_name
  $last_name: last_name
  $email: email
  $phone: phone
```

## [v3.1.0](https://github.com/gordalina/GordalinaMixpanelBundle/releases/tag/3.1.0)

[3.0.0...3.1.0](https://github.com/gordalina/GordalinaMixpanelBundle/compare/3.0.0...3.1.0)

### Features

- Add `throw_on_user_data_attribute_failure` parameter. [#12](https://github.com/gordalina/GordalinaMixpanelBundle/pull/12) by @RomulusED69
- Condition evaluation in Actions. [#10](https://github.com/gordalina/GordalinaMixpanelBundle/pull/10) by @RomulusED69

## [v3.0.0](https://github.com/gordalina/GordalinaMixpanelBundle/releases/tag/3.0.0)

[2.6.2.3...3.0.0](https://github.com/gordalina/GordalinaMixpanelBundle/compare/2.6.2.3...3.0.0)

### Features

- Added support for Symfony ^4.4 and ^5.0
- Upgrade `mixpanel/mixpanel-php` from ~2.6 to ~2.8
- Configuration: add `enabled` parameter, to enable/disable data sending to Mixpanel
- Configuration: add `auto_update_user` parameter, to automatically send user-related data on each request
- Configuration: add `extra_data` parameter, to send more user-related data on each request
- Create `MixpanelEvent` event, to send data to Mixpanel without using annotation. Example:

```php
$annotation = new Annotation\Track();
$annotation->event = 'My event';
$annotation->props = [
  'prop 1' => 'data 1',
  'prop 2' => 'data 2',
];

$eventDispatcher->dispatch(new MixpanelEvent($annotation, $request));
```

- Annotation `Mixpanel\Expression` can now be used in props. Example, to send the current user's ID:

```php
/**
 * @Mixpanel\Track("Order Completed", props={
 *     "user": @Mixpanel\Expression("user.getId()")
 * })
 */
```

### Breaking changes

- PHP 7.1 support has been removed
- Symfony 2.6 and ^3.0 support has been removed, Symfony 4.3 is not supported
- Autoloading has been changed from PSR-0 to PSR-4

### Chores

- Setup CI with Travis
- Add PHP-CS-Fixer and lint code
