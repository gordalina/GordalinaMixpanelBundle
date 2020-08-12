### v3.0

- Changed autoload from PSR-0 to PSR-4
- v3 now supports Symfony 4.4+ and Symfony 5.0+
- Uses version ~2.8 of `mixpanel/mixpanel-php`
- Using the autowiring
- Remove compatibility with old Symfony versions not maintained
- Setting up Travis + Php-cs Fixer
- Add ``auto_update_user`` parameter in order to send user data on each request
- Add ``enable`` parameter in order to enable/disable send data to Mixpanel
- Add ``extra_data`` config to send more user data
- Create ``MixpanelEvent`` if you want to send data without annotation
- Be able to use ``Expression`` in props
```php
* @Mixpanel\Track("Order Completed", props={
*      "user": @Mixpanel\Expression("user.getId()")
* })
```
- Some fixes (event name, getId not exist, ...)
- Update Readme

