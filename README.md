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

## Setting Up Authentication Using Sanctum

So now we are working on Authentication

1. Add `HasApiTokens` trait in **User.php** (model) .
1. Now make another controller **AuthController** in (app/Http/Controllers/Api/AuthController.php).  
   Now Add following code in **AuthController**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.']
            ]);
        }

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.']
            ]);
        }
        $token = $user->createToken('api-token')->plainTextToken;
        return response()->json([
            'token' => $token
        ]);
    }
}

```

Now add a route for login

```php
Route::post('/login', [AuthController::class, 'login']);
```

Now we can see how the tokens are sent in **Postman**.  
[**_Click here to check Postman_**](https://www.postman.com/martian-star-647792/events-management/request/9r6r5qe/login)

## Protecting Routes

So now we are protecting the Routes (to run the routes only if the _user_ is authenticated) using Sanctum. here is how we do it in this project.

```php
Route::middleware('auth:sanctum')->group(function (){
    Route::apiResource('events', EventController::class)->except(['index','show']);
});
```

so now **tokens** will be checked for Validation. All other(except index and show here) actions require a valid token for EventController. This _auth_ can also be run on the EventController **\_\_construct** using like this

```php
public function __contruct(){
    $this->middleware('auth:sanctum')->except(['index','show']);
}
```

## Revoking Tokens and Signing Out

Now we are signing out the tokens.

```php
    public function logout(Request $request) {
        $request->user()->tokens()->delete();

        return response()->json([
            "message" => "Logged out successfully"
        ]);

    }
```

What happens here is the all the tokens are revoked for a specific user.

[Go through **Revoking Tokens**](https://laravel.com/docs/12.x/sanctum#revoking-tokens)

[Go through **Token Expiration**](https://laravel.com/docs/12.x/sanctum#token-expiration)

## Authorization with Gate

🚀. First in _app/Providers/AppServiceProvider.php_ add the following code in **public function boot()** (the code is for defining the **Gate**)

```php
      // Authentication using Gates
        Gate::define('update-event', function ($user, Event $event) {
            return $user->id === $event->user_id;
        });

        Gate::define('delete-attendee', function ($user, Event $event, Attendee $attendee) {
            return $user->id === $attendee->user_id || $user->id === $event->user_id;
        });
```

The code is defining the authentication for **update-event** and **delete-attendee**.

🚀. Now we need to add these gate in the controller actions to do this we need to add the **middleware auth:sanctum** in the routes for those actions. So therefore in _Routes/api.php_

```php
Route::middleware('auth:sanctum')->group(function (){
    Route::apiResource('events', EventController::class)->except(['index','show']);

    Route::apiResource('events.attendees', AttendeeController::class)->scoped()->except(['index','show']);
});
```

🚀. Then finally in the **Controllers** _EventController_

```php

    public function update(Request $request, Event $event)
    {
        if(Gate::denies('update-event', $event)){
            abort(403,'You are not authorized to update this event.');
        }

        $event->update(
            $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'start_time' => 'sometimes|date',
                'end_time' => 'sometimes|date|after:start_time'
            ])
        );
        return new EventResource($this->loadRelationships($event));
    }
```

Similarly in _AttendeeController_

```php
    public function destroy(Event $event, Attendee $attendee)
    {
        if (Gate::denies('delete-attendee', [$event, $attendee])) {
            abort(403, 'You are not authorized to delete this event');
        }

        $attendee->delete();

        return response()->json([
            'message' => 'Attendee deleted successfully'
        ], 204);
    }
```

it means if **'update-event'** **Gate** is denied as token isn't authorized then abort the action with the status 403 and message 'You are not authorized to update this event'.

We provide a middleware for the Routes. **_So now the authorization is done via tokens_**.
[To know more about **Authorization with Gates**](https://laravel.com/docs/12.x/authorization#gates)

## Authorization with Policies

🚀. **_How to make a Policy for a model_**
```php artisan make:policy EventPolicy --model=Event````

now we have made policy next thing to do is simply difine the rules of policies in the **EventPolicy.php**

```php
<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EventPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Event $event): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Event $event): bool
    {
        return $user->id === $event->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Event $event): bool
    {
        return $user->id === $event->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Event $event): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Event $event): bool
    {
        return false;
    }
}

```

So, let's see why we use `?` in `viewAny(?User $user)` function, this `?` is if there is `User` or not the `viewAny()` will work.

Now to declare that the authorization works in a action

```php
Gate::authorize('viewAny', Event::class);
//OR
if(Gate::denies('viewAny')){
    abort(403,"You are not authorized so do it mmaann");
}
//OR
if ($request->user()->cannot('update', $post)) {
    abort(403);
}
//OR
if($request->user()->can('update',$post)){
    ...
}
```

File

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Http\Traits\CanLoadRelationships;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EventController extends Controller
{
    use AuthorizesRequests;
    use CanLoadRelationships;

    public function __construct(){


    }

    private array $relations = ['user', 'attendees', 'attendees.user'];


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //used when custom message and status needs to be sent
        // if(Gate::denies('viewAny')){
        //     abort(403,"You are not authorized so do it mmaann");
        // }
        //OR
        Gate::authorize('viewAny', Event::class);

        $query = $this->loadRelationships(Event::query());
        // $query = Event::query();
        // foreach($relations as $relation){

        //     $query->when(
        //         $this->shouldIncludeRelation($relation),
        //         fn($q)=> $q->with($relation)
        //     );

        // }

        return EventResource::collection($query->latest()->paginate());
    }

    // protected function shouldIncludeRelation(string $relation)
    // {

    //     $include = request()->query('include');

    //     if (!$include) {
    //         return false;
    //     }

    //     $relations = array_map('trim', explode(',', $include));

    //     return in_array($relation, $relations);
    // }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {


        $event = Event::create([
            ...$request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_time' => 'required|date',
                'end_time' => 'required|date|after:start_time',
            ]),
            'user_id' => $request->user()->id

        ]);
        return new EventResource($this->loadRelationships($event));
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event)
    {

        return new EventResource($this->loadRelationships($event));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event)
    {
        if (Gate::denies('update', $event)) {
            abort(403, 'You are not authorized to update this event.');
        }

        $event->update(
            $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'start_time' => 'sometimes|date',
                'end_time' => 'sometimes|date|after:start_time'
            ])
        );
        return new EventResource($this->loadRelationships($event));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event)
    {

        $event->delete();
        return response()->json([
            'message' => 'Event deleted successfully'
        ]);
    }
}

```

[kindly go through this docs to properly understand and all below it](https://laravel.com/docs/12.x/authorization#creating-policies)

## Custom Artisan Command - Reminding about Events

🚀. **_Command_**
`php artisan make:command SendEventReminders`
This file is built in `app/Console/Command/SendEventReminder.php`  
🚀. **_Code_**

```php
<?php

namespace App\Console\Commands;

use App\Models\Event;
use Illuminate\Support\Str;
use Illuminate\Console\Command;


class SendEventReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-event-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends notifications to all event attendees that event starts soon.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // $event = all the events with attendees and users of those attendees in a given time frame.
        $event = Event::with('attendees.user')
            ->whereBetween('start_time', [now(), now()->addDay()])->get();
        // $eventCount count of event
        $eventCount = $event->count();
        // $eventLabel: Just a label.
        $eventLabel = Str::plural('event', $eventCount);

        $this->info("Found {$eventCount} {$eventLabel}.");
        //running for each event's each attendeer run the $this->info command.
        $event->each(
            fn($event)=> $event->attendees->each(
                fn($attendee)=>$this->info("Notifying the user {$attendee->user->id}")));

        $this->info('Reminder notifications send successfully!');
    }
}
```

The function `handle()` basically does the main task of functionality see the code .  
[To know more **Click**](https://laravel.com/docs/12.x/artisan#writing-commands)

## Task Scheduling - Reminding About Events

🚀. **Task Scheduling** is the concept that Schedules and runs Commands in Server. Laravel's command scheduler offers a fresh approach to managing scheduled tasks on your server. The scheduler allows you to fluently and expressively define your command schedule within your Laravel application itself. When using the scheduler, only a single cron entry is needed on your server. Your task schedule is typically defined in your application's `routes/console.php` file.  
this can also be done in `bootstrap/app.php`.

🚀. _In `routes/console.php`_

```php
use App\Console\Commands\SendEmailsCommand;
use Illuminate\Support\Facades\Schedule;

Schedule::command('emails:send Taylor --force')->daily();

Schedule::command(SendEmailsCommand::class, ['Taylor', '--force'])->daily()
```

🚀. _In `bootstrap/app.php`_

```php
use Illuminate\Console\Scheduling\Schedule;

->withSchedule(function (Schedule $schedule) {
    $schedule->call(new DeleteRecentUsers)->daily();
})
```

🚀. To Initiate the task

```
php artisan schedule:work
```

Now the task will run according to **time interval**.  
If you would like to view an overview of your scheduled tasks and the next time they are scheduled to run, you may use the schedule:list Artisan command:

```
php artisan schedule:list
```

**_Kindly go through `routes/console.php`_**.  
[**_To know more about Task Scheduling_**](https://laravel.com/docs/12.x/scheduling#introduction)

## Notification and Email Sending

🚀. **How to build a Notifications**

```
php artisan make:notification EventReminderNotification
```

This is built in `app/Notifications/EventReminderNotification.php`.

🚀. Following is the file for it

```php
<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventReminderNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Event $event
    )
    {

    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('Reminder: You have an upcoming event!')
            ->action('View Event', route('events.show', $this->event->id))
            ->line(
                "The event {$this->event->name} starts at {$this->event->start_time}"
            );
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            "event_id" => $this->event->id,
            "event_name" => $this->event->name,
            "event_start_time" => $this->event->start_time
        ];
    }
}
```

And finally in the _SendEventReminder.php_ . See this in commit.

```php
<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Notifications\EventReminderNotification;
use Illuminate\Support\Str;
use Illuminate\Console\Command;


class SendEventReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-event-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends notifications to all event attendees that event starts soon.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $event = Event::with('attendees.user')
            ->whereBetween('start_time', [now(), now()->addDay()])->get();

            $eventCount = $event->count();

        $eventLabel = Str::plural('event', $eventCount);

        $this->info("Found {$eventCount} {$eventLabel}.");

        $event->each(
            fn($event)=> $event->attendees->each(
                fn($attendee)=>$attendee->user->notify(new EventReminderNotification(
                    $event
                ))));

        $this->info('Reminder notifications send successfully!');
    }
}

```

[Click to know more](https://laravel.com/docs/12.x/notifications#sending-notifications)

## Queues - Reminding About Events

Now **Queues** what these does is instead of scheduling the Task now it sent it to background to be done in such a way that it don't effect the smoothness of App. So all these _tasks_ can be seen in database table namely **jobs** and after these tasks are executed they are removed form the **jobs** table.  
Now how to apply **Queues** ➡️

```php
    'default' => env('QUEUE_CONNECTION', 'sync'),
```

This change is done in `config/queue.php`.  
And also a few changes in `app/Notifications/EventReminderNotification.php` ➡️

```php
<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

//this implements ShouldQueue is implemented to make notifications queueable.
class EventReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Event $event
    )
    {

    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('Reminder: You have an upcoming event!')
            ->action('View Event', route('events.show', $this->event->id))
            ->line(
                "The event {$this->event->name} starts at {$this->event->start_time}"
            );
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            "event_id" => $this->event->id,
            "event_name" => $this->event->name,
            "event_start_time" => $this->event->start_time
        ];
    }
}
```

## Throttling the API (Rate Limiting)

For this throttle rate limiter to work we need add `'limiter' => 'redis'`  
This is in file `config/cache.php`.

```php
'default' => env('CACHE_STORE', 'database'),

'limiter' => 'redis',
```

throttle helps us in rate limiting.  
example:

```php

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/events', [EventController::class, 'index']);
});
```

What happens here is rate limit is decided to be 60 in 1 minute. And if we have a custom **Rate Limiter** then we can put its name (here its _'api'_):

```php

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/events', [EventController::class, 'index']);
});
```

Else the rate limiter is same

[**Click and Read Importent** 🎯](https://laravel.com/docs/12.x/rate-limiting)
