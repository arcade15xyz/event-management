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
