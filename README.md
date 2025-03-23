# EVENT MANAGEMENT PROJECT  



## Introduction   
A project for Event Management .   


## Starting   
1. To start the project ⤵️   
```
composer create-project --prefer-dist laravel/laravel event-management
```
1. Now make the model ⤵️
```
php artisan make:model Event -m
```
```
php artisan make:model Attendee -m
```   
This makes a model with a migration
1. In .env file enable the database.   
1. Now let's migrate these models ⤵️   
```
php artisan migrate
```
1. Now let's make controller now ⤵️
```
php artisan make:controller Api/AttendeeController
 --api
```   
And   
```php artisan make:controller Api/EventController
 --api
```
1. We can make a Provider namely **RouteServiceProvider** if needed though all the features can be done in **AppServiceProvider**.
```php
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
```
1. In **routes/api.php** ⤵️   
```php
<?php

use App\Http\Controllers\Api\AttendeeController;
use App\Http\Controllers\Api\EventController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('events',EventController::class);

Route::apiResource('event.attendees', AttendeeController::class)
->scoped(['attendee' => 'event']);
```



