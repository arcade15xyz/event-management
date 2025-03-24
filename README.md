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
```php

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendee extends Model
{
    use HasFactory;

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function event(){
        return $this->belongsTo(Event::class);
    }
}
```   
```
php artisan make:model Attendee -m
```  
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendee()
    {
        return $this->hasMany(Attendee::class);
    }
}

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

So the API creates API Controller Optimized for JSON response. i.e. the data is in JSON format.


## Seeding Data for the REST API

So now we are seeding data for the dummy entries in the tables. for that we make the **factories** (*database>factories*) there we are only have 2 factories **UserFactory.php** and **EventFactory.php** there is no need to make a factory for **Attendee** because it only has columns related to **User** and **Event** not any column of its own. Creating a Factory   
```
php artisan make:factory EventFactory --model=Event
```
```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->sentence(3),
            'description' => fake()->text,
            'start_time' => fake()->dateTimeBetween('name','+1 month'),
            'end_time' => fake()->dateTimeBetween('+1 month','+2 months'),
        ];
    }
}
```
now lets make ***Seeders*** for the **Event** and **Attendee**
```
php artisan make:seeder EventSeeder
```
```
php artisan make:seeder AttendeeSeeder
```
Now in Seeders   
EventSeeder
```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        for($i = 0; $i<200; $i++){
            $user = $users->random();
            \App\Models\Event::factory()->create([
                'user_id' => $user->id
            ]);
        }
    }
}

```
And in AttendeeSeeder   
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AttendeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = \App\Models\User::all();

        $events = \App\Models\Event::all();
        foreach($users as $user) {
            $eventsToAttend = $events->random(rand(1,3));
            foreach($eventsToAttend as $event) {
                \App\Models\Attendee::create([
                    'user_id' => $user->id,
                    'event_id' => $event->id
                ]);
            }
        }
    }
}

```
And in the DatabaseSeeder
```php
<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(1000)->create();

        $this->call(EventSeeder::class);
        $this->call(AttendeeSeeder::class);
    }
}

```
As databaseSeeder is from were seed will work.   
    
And then finally
```
php artisan migrate:refresh --seed
```


## Storing Data and Validation
now we are storing data for that we need to define *fillable* in model and then in **EventController** in **store** 
```php
    public function store(Request $request)
    {


        $event = Event::create([
            ...$request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_time' => 'required|date',
                'end_time' => 'required|date|after:start_time',
            ]),
            'user_id' => 1

        ]);
        return $event;
    }

```
the `'user_id' => 1` indicates the value of foreign key *user_id*.   
