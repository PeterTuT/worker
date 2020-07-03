<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GatewayClient\Gateway;
use Auth;
use App\Message;


class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');

        // 设置GatewayWorker服务的Register服务ip和端口
        Gateway::$registerAddress = '127.0.0.1:1238';
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('home');
    }

    public function init(Request $request)
    {
        //绑定用户
        $this->bind($request);
        
         //历史记录
        $this->history();
        //进入聊天室了
        $this->login();
    }

        public function say(Request $request)
    {
        $data = [
            'type' => 'say',
            'data' => [
                'avatar' => Auth::user()->avatar(),
                'name' => Auth::user()->name,
                'content' => $request->input('content'),
                'time' => date("Y-m-d H:i:s", time())
            ]
        ];

        Gateway::sendToAll(json_encode($data));

        //存入数据库，以后可以查询聊天记录
        Message::create([
            'user_id' => Auth::id(),
            'content' => $request->input('content')
        ]);
    }

    /**
     * 绑定client_id 与 user id
    */
    private function bind($request)
    {
        $id = Auth::id();
        $client_id = $request->client_id;
        Gateway::bindUid($client_id, $id);
    }

        /**
     * 最新的5条聊天历史信息
     */
    private function history()
    {
        $data = ['type' => 'history'];

        $messages = Message::with('user')->orderBy('id', 'desc')->limit(3)->get();
        $data['data'] = $messages->map(function ($item, $key) {
            return [
                'avatar' => $item->user->avatar(),
                'name' => $item->user->name,
                'content' => $item->content,
                'time' => $item->created_at->format("Y-m-d H:i:s")
            ];
        });

        Gateway::sendToUid(Auth::id(), json_encode($data));
    }

        
    /**
     * 提示进入聊天室
    */
    private function login()
    {
        $data = [
            'type' => 'say',
            'data' => [
                'avatar' => Auth::user()->avatar(),
                'name' => Auth::user()->name,
                'content' => '进入了聊天室',
                'time' => date("Y-m-d H:i:s", time())
            ]
        ];

        Gateway::sendToAll(json_encode($data));
    }
}
