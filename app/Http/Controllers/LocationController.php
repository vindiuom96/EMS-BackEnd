<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\User;
use App\Location;

class LocationController extends Controller
{

    public function add(Request $request){
        if(Auth::user()->hasPermissionTo("Student")){
            $request->validate([
                'timestamp' => 'required',
                'latitude' => 'required',
                'longitude' => 'required'
            ]);

            if(Auth::user()->hasRole('Student')){
                $user_id = Auth::user()->id;
            }
            else if(Auth::user()->hasRole('Parent')){
                if(!$request->get('student_id'))
                    return response()->json("error no student_id found", 401);
                $user_id = $request->get('student_id');
                if(User::find(Auth::user()->id)->whereHas('student', function($q) use ($user_id){
                    $q->where('id', $user_id);
                })->count()==0)
                    return response()->json("no permission", 401);
                $user_roles = User::find($user_id)->roles;
            }

            if(Location::where('user_id', $user_id)->exists())
                $location = Location::where('user_id', $user_id)->first();
            else{
                $location = new Location;
                $location->user_id = Auth::user()->id;
            }

            $location->timestamp = Carbon::createFromTimestamp($request->timestamp)->toDateTimeString();
            $location->latitude = $request->latitude;
            $location->longitude = $request->longitude;
            if($request->accuracy)
                $location->accuracy = $request->accuracy;
            if($request->altitude)
                $location->altitude = $request->altitude;
            if($request->altitudeAccuracy)
                $location->altitudeAccuracy = $request->altitudeAccuracy;
            if($request->heading)
                $location->heading = $request->heading;
            if($request->speed)
                $location->speed = $request->speed;
            $location->save();

            return response()->json(['data' => $location]);
        } else {
            return response()->json(["error" => ['message' => "You don't have permission"]], 401);
        }
    }

    public function get($id){
        if(Auth::user()->hasPermissionTo("Parent")){
            return response()->json(Location::where("user_id", $id)->first());
        } else {
            return response()->json(["error" => ['message' => "You don't have permission"]], 401);
        }
    }

    public function getCurrent(){
        if(Auth::user()->hasPermissionTo("Student")){
            return response()->json(Location::where('user_id', Auth::user()->id)->first());
        } else {
            return response()->json(["error" => ['message' => "You don't have permission"]], 401);
        }
    }

    public function index(Request $request){
        if(($request->get('sort')!='null' && $request->get('sort')!='') && $request->get('search')){
            $user = User::with('location')->where("name", "LIKE", "%{$request->get('search')}%")->orWhere("email", "LIKE", "%{$request->get('search')}%")->orderby($request->get('sort'), $request->get('order'))->role("Student")->paginate(10);
        } else if($request->get('sort')!='null' && $request->get('sort')!=''){
            $user = User::with('location')->orderby($request->get('sort'), $request->get('order'))->role("Student")->paginate(10);
        }

        else if($request->get('search'))
            $user = User::with('location')->where("name", "LIKE", "%{$request->get('search')}%")->orWhere("email", "LIKE", "%{$request->get('search')}%")->role("Student")->paginate(10);
        else
            $user = User::with('location')->role("Student")->paginate(10);
        return response()->json($user, 200);
    }
}
