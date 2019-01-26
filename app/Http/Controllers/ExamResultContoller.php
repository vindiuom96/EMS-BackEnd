<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\ExamResult;
use App\User;
use App\Guardian;

class ExamResultContoller extends Controller
{
    public function mobile(Request $request){
        if(!Auth::user()->hasPermissionTo('View Results')){
            return response()->json("User do not have permission", 401);
        }

        if(Auth::user()->hasRole('Student')){
            $student_id = User::find(Auth::user()->id)->student->id;
        }
        else if(Auth::user()->hasRole('Parent')){
            if(!$request->get('student_id'))
                return response()->json("error! no student_id found", 401);
            $student_id = $request->get('student_id');
            if(Guardian::find(User::find(Auth::user()->id)->parent->id)->whereHas('student', function($q) use ($student_id){
                $q->where('id', $student_id);
            })->count()==0)
                return response()->json("no permission", 401);
        }

        //$class_id = Date("Y");
        if($request->get('class_id')){
            $class_id = $request->get('class_id');
            if($request->get('term'))
                return response()->json(ExamResult::whereHas('class', function ($query) use ($class_id) {
                    $query->where('id','=',$class_id);
                })->with('subject', 'class')->where(['student_id' => $student_id, 'term' => $request->get('term')])->get());

                return response()->json(ExamResult::whereHas('class', function ($query) use ($class_id) {
                    $query->where('id','=',$class_id);
                })->with('subject', 'class')->where(['student_id' => $student_id])->get());
            } else {
                if($request->get('term'))
                    return response()->json(ExamResult::with('subject', 'class')->where(['student_id' => $student_id, 'term' => $request->get('term')])->get());

                return response()->json(ExamResult::with('subject', 'class')->where(['student_id' => $student_id])->get());
            }


    }
}