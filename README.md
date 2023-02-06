# Installing

## Add this tool to NovaServiceProvider
```
public function tools()
    {
        return [
            Nova2fa::make()
        ];
    }
```

## Add middleware to Nova config (config/nova.php)
```
'middleware' => [
    'web',
    HandleInertiaRequests::class,
    DispatchServingNovaEvent::class,
    BootTools::class,
    \Partybussen\Nova2fa\Http\Middleware\Google2fa::class
],
```

## Publish config and migration
```
artisan vendor:publish --tag=nova-2fa-config
artisan vendor:publish --tag=nova-2fa-migrations
```

## Run migration
```
php artisan migrate
```

## Change config file
Fill `requires_2fa_attribute` in config.

## Relationship User class
Define a relationship between User and User2fa
```
public function user2fa(): \Illuminate\Database\Eloquent\Relations\HasOne
{
    return $this->hasOne(User2fa::class);
}
```
