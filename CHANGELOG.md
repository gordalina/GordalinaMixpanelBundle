## [v3.0.0](https://github.com/gordalina/GordalinaMixpanelBundle/releases/tag/3.0.0)

[2.6.2.3...3.0.0](https://github.com/gordalina/GordalinaMixpanelBundle/compare/2.6.2.3...3.0.0)

### Features

- Added support for Symfony 4.4+ and 5+
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
- Symfony 4.3 support has been removed
- Autoloading has been changed from PSR-0 to PSR-4

### Chores

- Setup CI with Travis
- Add PHP-CS-Fixer and lint code
