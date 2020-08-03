# Snapchat

```bash
composer require socialiteproviders/snapchat
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`.

```php
'snapchat' => [    
  'client_id' => env('SNAPCHAT_CLIENT_ID'),  
  'client_secret' => env('SNAPCHAT_CLIENT_SECRET'),  
  'redirect' => env('SNAPCHAT_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to the listen for `SocialiteWasCalled` events. 

Add the event to your `listen[]` array  in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Snapchat\\SnapchatExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::with('snapchat')->redirect();
```

### Returned User fields

- ``id``
- ``name``
- ``avatar``