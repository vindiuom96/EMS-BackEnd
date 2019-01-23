<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\User;
use App\Notice;
use App\NoticeReadStatus;

class NoticeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(parent::checkPermission('View Notice'))
            return response()->json("User do not have permission", 401);
        $user_id = Auth::user()->id;
        $user_roles = Auth::user()->roles;
        $res = DB::select('
            SELECT n.id, n.title, n.content, n.created_at, n.notice_from, IF(ISNULL(s.user_id), "false", "true") as status
            FROM notices n, notice_user ns LEFT JOIN notice_read_statuses s ON (s.user_id=ns.user_id and s.notice_id=ns.notice_id)
            WHERE ns.user_id = ?
        ', [$user_id,$user_id]);

        for($i=0; $i<count($user_roles); $i++){
            array_merge($res, DB::select('
            SELECT n.id, n.title, n.content, n.created_at, n.notice_from, IF(ISNULL(s.user_id), "false", "true") as status
            FROM notices n, notice_user ns LEFT JOIN notice_read_statuses s ON (s.user_id=ns.user_id and s.notice_id=ns.notice_id)
            WHERE ns.role_id = ?
        ', [$user_roles[$i]->id]));
        };

        //$res = Notice::join('notice_user', 'notices.id', '=', 'notice_user.notice_id')->join('users', 'users.id', '=', 'notice_user.user_id')->with('notice_read_statuses')->orWhere('notice_read_statuses.user_id', '=', 'notice_user.user_id')->orWhere('notice_read_statuses.notice_id', '=', 'notices.id')->select("notices.*")->where('users.id', 1)->get();
        //return $user_roles;
        return json_encode($res);
        //return response()->json(User::find($user)->notices(), 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(parent::checkPermission('Add Notice'))
            return response()->json("User do not have permission", 401);
        $request->validate([
            'title' => 'required|integer',
            'content' => 'required|integer'
        ]);

        $request['notice_from'] = Auth::user()->id;
        $attendance = Attendance::create($request->all());
        return json_encode($attendance);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if(parent::checkPermission('View Notice'))
            return response()->json("User do not have permission", 401);
        return json_encode(Notice::with('user')->findOrFail($id));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if(parent::checkPermission('Edit Notice'))
            return response()->json("User do not have permission", 401);

        $notice = Notice::findOrFail($id);

        if($request->title)
            $notice->title = $request->title;
        if($request->content)
            $notice->content = $request->content;
        $notice->save();
        return response()->json(['data' => $notice], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(parent::checkPermission('Delete Notice'))
            return response()->json("User do not have permission", 401);
        $notice = Notice::findOrFail($id);
        $notice->delete();
        return response()->json(['data' => $notice], 200);
    }

    public function read(Request $request)
    {
        if(parent::checkPermission('View Notice'))
            return response()->json("User do not have permission", 401);
        if($request->get('notice_id')) {
            $notice = NoticeReadStatus::create(['user_id' => Auth::user()->id, 'notice_id' => $request->get('notice_id')]);
            return response()->json($notice, 200);
        }
        return response()->json("Invalid Parameters", 401);
    }
}
