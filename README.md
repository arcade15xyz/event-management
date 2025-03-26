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

So now we are seeding data for the dummy entries in the tables. for that we make the **factories** (_database>factories_) there we are only have 2 factories **UserFactory.php** and **EventFactory.php** there is no need to make a factory for **Attendee** because it only has columns related to **User** and **Event** not any column of its own. Creating a Factory

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

now lets make **_Seeders_** for the **Event** and **Attendee**

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

now we are storing data for that we need to define _fillable_ in model and then in **EventController** in **store**

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

the `'user_id' => 1` indicates the value of foreign key _user_id_.

## Updating and Deleting data

In the **EventController** in **update** and **delete** are used for updating and deleting the events .  
For _Updating_

```php
    public function update(Request $request, Event $event)
    {
        $event->update(
            $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'start_time' => 'sometimes|date',
                'end_time' => 'sometimes|date|after:start_time'
            ])
        );
        return $event;
    }
```

For _deleting_

```php
    public function destroy(Event $event)
    {
        $event->delete();
        return response()->json([
            'message' => 'Event deleted successfully'
        ]);
    }
```

## API Resources - Controlling JSON Response

API Resources are used to transform models and collections
into well-Structured JSON responses. They allows us to controll exactly how the JSON data looks, making it ideal for building clean, consistent API's.

### How to create a API Resources

```
php artisan make:resource EventResource
```

Generates a file in `App\Http\Resources\EventResource.php`

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name'=> $this->name,
            'description' => $this->description,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'user' => new UserResource($this->whenLoaded('user')),
            'attendees' => AttendeeResource::collection($this->whenLoaded('attendees')),
        ];
    }
}
```

we make API Resource for **User**(UserResource) and **Attendee**(AttendeeResources) . Then we use them in controller.
Check the **EventController.php**

[More details on API Resources](https://laravel.com/docs/12.x/eloquent-resources)

## Attendees and Pagination

Here we worked for routing for Attendee. The Route for attendee can be like following ⤵️

```php
Route::apiResource('events.attendees', AttendeeController::class)
    ->scoped()->except(['update']);
```

The scope is automatically declared by the laravel so no need to do it manually.

Now, Check the **AttendeeController.php**

## Optional Relation Loading

We optionally load the relations according to us for any action i.e. ⬇️

```
Localhost/events?include=user,attendees,attendees.user
```

_This is a URL_  
Here, _include_ helps us define relations to be loaded .  
❓Why is there _attendees_ and _attendees.user_ both?  
➡️ We have to define relations for its data to be loaded and we need to add both _attendees_ as well as _attendees.user_ if we want to bind thier data.

### The code in EventController.php

```php
 public function index()
    {
        $query = Event::query();
        $relations = ['user', 'attendees', 'attendees.user'];
        foreach($relations as $relation){

            $query->when(
                $this->shouldIncludeRelation($relation),
                fn($q)=> $q->with($relation)
            );

        }

        return EventResource::collection($query->latest()->paginate());
    }

    protected function shouldIncludeRelation(string $relation){

        $include = request()->query('include');

        if(!$include){
            return false;
        }

        $relations = array_map('trim',explode(',', $include));

        return in_array($relation, $relations);
    }

```

**Another Important thing here is for these relation to work we need to define these data in EventResources**

```php
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'user' => new UserResource($this->whenLoaded('user')),
            'attendees' => AttendeeResource::collection($this->whenLoaded('attendees')),
        ];
    }
```

so _whenLoaded_ is doing, is that if the relation is loaded only then the data is returned else it is not returned.

## Universal Relation Loading Trait

Used for reusability of code. i.e. The code for _Optional Relation Loading_. So to enable the reusability we need to build **traits** (app/HTTP/Traits/CanLoadRelationships.php) this folder needed to made manually.

```php
<?php

namespace App\Http\Traits;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Model;

trait CanLoadRelationships
{
    public function loadRelationships(
        Model|QueryBuilder|EloquentBuilder $for, ?array $relations = null
    ) : Model|QueryBuilder|EloquentBuilder {
        $relations = $relations?? $this->relations?? [];
        foreach($relations as $relation){

            $for->when(
                $this->shouldIncludeRelation($relation),
                fn($q)=>
                $for instanceof Model ? $for->load($relation): $q->with($relation)
            );

        }
        return $for;
    }

    protected function shouldIncludeRelation(string $relation){

        $include = request()->query('include');

        if(!$include){
            return false;
        }

        $relations = array_map('trim',explode(',', $include));

        return in_array($relation, $relations);
    }
}
```

Above is the code used in trait **CanLoadRelationships** check this in code too.  
Now to use **CanLoadRelations** we use `use CanLoadRelationships` in **EventController** and then use these in the controller. See **EventController** and the actions.

## Loading Attendee Relations

now here what we are doing is we are using **CanLoadRelations** in **AttendeeController** so now what is happening here is same as before but the parameter can be _HasMany_ too as `attendee()` also has the type of _HasMany_ (check in **Event model**);  
**_Check the AttendeeController_**

**_Summary of When Each Type is Passed_**

| Type            | When It's Sent                            | Eager Loading Method          |
| --------------- | ----------------------------------------- | ----------------------------- |
| Model           | Single event or record.                   | $for->load($relation)         |
| QueryBuilder    | Raw DB query (not common for relations).  | Eager loading not applicable. |
| EloquentBuilder | Query before calling get() or paginate(). | $q->with($relation)           |
| HasMany         | When working with a related collection.   | $q->with($relation)           |

**_Check this all_**
