<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Partition;
use App\Subject;
use App\Question;
use App\TestRoom;
use App\TestRoomStudents;
use App\Answer;
use App\MailStore;

class APIController extends Controller
{
  public function Partitions(Request $request){
    $this->validate($request, [
       'subject' => 'required'
    ]);
    $subject = $request->get('subject');
    $partitions = Partition::where('subject_id', '=', $subject)->where('trash', '=', false)->get();

    $partitions[0]['token'] = $request->get("_token");

    $partitions->toJSON();

    return $partitions;
  }

  public function Subjects(Request $request){
    $this->validate($request, [
       'class' => 'required',
    ]);
    $class = $request->get('class');
    $subjects = Subject::where('class', '=', $class)->where('trash', '=', false)->get();

    $subjects[0]['token'] = $request->get("_token");

    $subjects->toJSON();

    return $subjects;
  }

  public function CodeGenerate(Request $request){
    $code['code'] = app('App\Http\Controllers\TestRoomController')->generateCode();

    $code['token'] = $request->get("_token");

    return $code;
  }

  public function QuestionGenerate(Request $request){

    $class = $request->get('class');
    $subject = $request->get('subject');
    $partition = $request->get('partition');

    $questions = Question::where('class', '=', $class)->where('subject_id', '=', $subject)->where('partition_id', '=', $partition)->get();

    $questions[0]['token'] = $request->get("_token");

    return $questions;
  }

  public function getAllMessages(Request $request)
  {
    \Carbon\Carbon::setLocale('bg');

    $allMessages = MailStore::orderBy('created_at', 'desc')->where('trash', '=', false)->limit(5)->get();
    $unreadMessagesCount = MailStore::where('read', '=', false)->where('trash', '=', false)->count();

    foreach ($allMessages as $key => $message) {
      $messages[$key]['id'] = $message->id;
      $messages[$key]['name'] = $message->name;
      $messages[$key]['time'] = $message->created_at->diffForHumans();
    }

    $messages[0]['count'] = $unreadMessagesCount;
    $messages[0]['token'] = $request->get("_token");

    return $messages;
  }

  public function getMessage(Request $request)
  {
    $message = MailStore::orderBy('created_at', 'desc')->where('trash', '=', false)->first();

    $message->message = str_limit($message->message, 55);
    $message->created_at = $message->created_at->diffForHumans();
    $message->token = $request->get("_token");

    $message->toJSON();

    return $message;
  }
}