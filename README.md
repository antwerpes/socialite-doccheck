# Socialite DocCheck

[![Latest Version on Packagist](https://img.shields.io/packagist/v/antwerpes/socialite-doccheck.svg?style=flat-square)](https://packagist.org/packages/antwerpes/socialite-doccheck)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/antwerpes/socialite-doccheck/lint.yml?branch=master)](https://github.com/antwerpes/socialite-doccheck/actions?query=workflow%3Alint+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/antwerpes/socialite-doccheck.svg?style=flat-square)](https://packagist.org/packages/antwerpes/socialite-doccheck)

[Laravel Socialite](https://laravel.com/docs/10.x/socialite) provider for the DocCheck Login. Compatible with both 
economy and business licenses.

## Installation

You can install the package via composer:

```bash
composer require antwerpes/socialite-doccheck
```

Update your services configuration (`config/services.php`) with the following entry:

```php
'doccheck' => [
    'client_id' => env('DOCCHECK_CLIENT_KEY'),
    'client_secret' => env('DOCCHECK_CLIENT_SECRET'),
    'redirect' => env('DOCCHECK_REDIRECT_URI'),
    'language' => env('DOCCHECK_LANGUAGE', 'de'),
    'template' => env('DOCCHECK_TEMPLATE', 'fullscreen_dc'),
    'license' => env('DOCCHECK_LICENSE', 'economy'),
],
```

## Usage

After setting up the environment variables (see configuration above), you may use this provider as any
other Socialite provider, see also [Socialite documentation](https://laravel.com/docs/10.x/socialite).
The user object returned by the provider differs by license. For the `economy` license, only an ID 
will be present. For the `business` license all other fields will also be present.

Example code:

```php
<?php

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    public function handleProviderCallback(Request $request)
    {
        $details = Socialite::driver('doccheck')->user();
         
        $user = User::query()
            ->firstOrNew([
                'id' => $details->getId(),
            ])
            // Only available with the `business` license
            ->fill([
                'email' => $details->getEmail(),
                'first_name' => $details->first_name,
                'last_name' => $details->last_name,
                'title' => $details->title,
                'street' => $details->street,
                'postal_code' => $details->street,
                'city' => $details->city,
                'country' => $details->country,
                'language' => $details->language,
                'gender' => $details->gender,
                'profession_id' => $details->profession_id,
                'discipline_id' => $details->discipline_id,
            ]);
        $user->save();
        auth()->login($user);
        
        return redirect()->intended('/');
    }
}
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Contributions are welcome! Leave an issue on GitHub, or create a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
