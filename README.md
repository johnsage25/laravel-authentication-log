![Package Logo](https://banners.beyondco.de/Laravel%20Authentication%20Log.png?theme=dark&packageManager=composer+require&packageName=pearldrift%2Flaravel-authentication-log&pattern=hideout&style=style_1&description=Log+user+authentication+details+and+send+new+device+notifications.&md=1&showWatermark=0&fontSize=100px&images=lock-closed)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pearldrift/laravel-authentication-log.svg?style=flat-square)](https://packagist.org/packages/pearldrift/laravel-authentication-log)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/pearldrift/laravel-authentication-log/run-tests?label=tests)](https://github.com/pearldrift/laravel-authentication-log/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/pearldrift/laravel-authentication-log/Check%20&%20fix%20styling?label=code%20style)](https://github.com/pearldrift/laravel-authentication-log/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/pearldrift/laravel-authentication-log.svg?style=flat-square)](https://packagist.org/packages/pearldrift/laravel-authentication-log)

Laravel Authentication Log is a package which tracks your user's authentication information such as login/logout time, IP, Browser, Location, etc. as well as sends out notifications via mail, slack, or sms for new devices and failed logins.

## Documentation, Installation, and Usage Instructions


## Installation

> Laravel Authentication Log requires Laravel 5.5 or higher, and PHP 7.0+.

You may use Composer to install Laravel Authentication Log into your Laravel project:

   composer require Pearldrift/laravel-authentication-log
   composer require torann/geoip

### Configuration

After installing the Laravel Authentication Log, publish its config, migration and view, using the `vendor:publish` Artisan command:

      php artisan vendor:publish --provider="Pearldrift\LaravelAuthenticationLog\LaravelAuthenticationLogServiceProvider" --tag="authentication-log-migrations"


Next, you need to migrate your database. The Laravel Authentication Log migration will create the table your application needs to store authentication logs:

       php artisan migrate
    
You can publish the view/email files with:
    
      php artisan vendor:publish --provider="Pearldrift\LaravelAuthenticationLog\LaravelAuthenticationLogServiceProvider" --tag="authentication-log-views"

Finally, add the `AuthenticationLogable` and `Notifiable` traits to your authenticatable model (by default, `App\User` model). These traits provides various methods to allow you to get common authentication log data, such as last login time, last login IP address, and set the channels to notify the user when login from a new device:

You can publish the config file with:
  
         php artisan vendor:publish --provider="Pearldrift\LaravelAuthenticationLog\LaravelAuthenticationLogServiceProvider" --tag="authentication-log-config"
  
This is the contents of the published config file:
  
        return [
          // The database table name
          // You can change this if the database keys get too long for your driver
          'table_name' => 'authentication_log',

          // The database connection where the authentication_log table resides. Leave empty to use the default
          'db_connection' => null,

          // The events the package listens for to log (as of v1.3)
          'events' => [
              'login' => \Illuminate\Auth\Events\Login::class,
              'failed' => \Illuminate\Auth\Events\Failed::class,
              'logout' => \Illuminate\Auth\Events\Logout::class,
              'logout-other-devices' => \Illuminate\Auth\Events\OtherDeviceLogout::class,
          ],

          'notifications' => [
              'new-device' => [
                  // Send the NewDevice notification
                  'enabled' => env('NEW_DEVICE_NOTIFICATION', true),

                  // Use torann/geoip to attempt to get a location
                  'location' => true,

                  // The Notification class to send
                  'template' => \Pearldrift\LaravelAuthenticationLog\Notifications\NewDevice::class,
              ],
              'failed-login' => [
                  // Send the FailedLogin notification
                  'enabled' => env('FAILED_LOGIN_NOTIFICATION', false),

                  // Use torann/geoip to attempt to get a location
                  'location' => true,

                  // The Notification class to send
                  'template' => \Pearldrift\LaravelAuthenticationLog\Notifications\FailedLogin::class,
              ],
          ],

          // When the clean-up command is run, delete old logs greater than `purge` days
          // Don't schedule the clean-up command if you want to keep logs forever.
          'purge' => 365,
      ];

If you installed torann/geoip you should also publish that config file to set your defaults:

    php artisan vendor:publish --provider="Torann\GeoIP\GeoIPServiceProvider" --tag=config
  
## Setting up your model

You must add the **AuthenticationLoggable** and **Notifiable** traits to the models you want to track.
    
    use Illuminate\Notifications\Notifiable;
    use Pearldrift\LaravelAuthenticationLog\Traits\AuthenticationLoggable;
    use Illuminate\Foundation\Auth\User as Authenticatable;

    class User extends Authenticatable
    {
        use Notifiable, AuthenticationLoggable;
    }

   
The package will listen for Laravel's Login, Logout, Failed, and OtherDeviceLogout events.

## Overriding default Laravel events
If you would like to listen to your own events you may override them in the package config (as of v1.3).


## Example event override

You may notice that Laravel - [fires a Login event when the session renews](https://github.com/laravel/framework/blob/master/src/Illuminate/Auth/SessionGuard.php#L149)  if the user clicked 'remember me' when logging in. This will produce empty login rows each time which is not what we want. The way around this is to fire your own Login event instead of listening for Laravels.

You can create a Login event that takes the user:
    
    <?php

    namespace App\Domains\Auth\Events;

    use Illuminate\Queue\SerializesModels;

    class Login
    {
        use SerializesModels;

        public $user;

        public function __construct($user)
        {
            $this->user = $user;
        }
    }

Then override it in the package config:
    
    // The events the package listens for to log
    'events' => [
        'login' => \App\Domains\Auth\Events\Login::class,
        ...
    ],
    
Then call it where you login your user:
  
    event(new Login($user));

Now the package will only register actual login events, and not session re-authentications.

## Overriding in Fortify

If you are working with Fortify and would like to register your own Login event, you can append a class to the authentication stack:

In FortifyServiceProvider:

        Fortify::authenticateThrough(function () {
        return array_filter([
            ...
            FireLoginEvent::class,
        ]);
    });

**FireLoginEvent** is just a class that fires the event:

    <?php

      namespace App\Domains\Auth\Actions;

      use App\Domains\Auth\Events\Login;

      class FireLoginEvent
      {
          public function handle($request, $next)
          {
              if ($request->user()) {
                  event(new Login($request->user()));
              }

              return $next($request);
          }
      }
      

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Anthony Rappa](https://github.com/pearldrift)
- [yadahan/laravel-authentication-log](https://github.com/yadahan/laravel-authentication-log)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
