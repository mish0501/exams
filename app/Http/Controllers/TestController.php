<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Controllers\TestController;
use App\Partition;
use App\Subject;
use App\Question;
use App\Answer;
use App\TestRoom;
use Session;
use View;

class TestController extends Controller
{
    public function index()
    {
      return view('test.choose', ['class' => true]);
    }
    public function chooseSubject(Request $request)
    {
      $subjects = Subject::where('class', '=', $request->get('class'))->get();
      return view('test.choose', ['subjects' => $subjects, 'selectedClass' => $request->get('class')]);
    }

    public function selectPartition(Request $request)
    {

      $this->validate($request, [
         'subject_id' => 'required',
         'class' => 'required'
      ]);

      $input['class'] = $request->class;
      $input['subject_id'] = $request->subject_id;

      $partitions = Partition::where($input)->get();
      return view('test.choose', ['partitions' => $partitions, 'selectedClass' => $input['class'], 'subject' => $input['subject_id']]);
    }

    public function selectQuestionPost(Request $request)
    {
      $this->validate($request, [
         'subject_id' => 'required',
         'partition_id' => 'required',
         'class' => 'required'
      ]);

      $class = $request->class;
      $partition = $request->partition_id;
      $subject = $request->subject_id;

      return $this->selectQuestion($subject, $partition, $class, 20, false);
    }

    public function selectQuestion($subject, $partition, $class, $questionCount, $api)
    {
      if($questionCount < 5){
        $questionCount = 5;
      }
      $questions = Question::where('class', '=', $class)
                            ->where('partition_id', '=', $partition)
                            ->where('subject_id', '=', $subject)
                            ->orderByRaw("RAND()")
                            ->limit($questionCount)
                            ->get();


      if($api){
        foreach ($questions as $key => $value) {
          $questions[$key]['answers'] = Answer::where('question_id', '=', $value->id)->orderByRaw("RAND()")->get();
        }

        $questions->toJSON();

        return $questions;
      }else{
        Session::put('questions', $questions);

        foreach ($questions as $key => $value) {
          $answers[$key] = Answer::where('question_id', '=', $value->id)->orderByRaw("RAND()")->get();
        }

        Session::put('answers', $answers);

        return view('test.test', ['question' => $questions['0'], 'answers' => $answers['0'], 'type' => $questions[0]->type, 'key' => '0']);
      }
    }

    public function nextQuestion(Request $request)
    {
      $this->validate($request, [
       'key' => 'required',
       'correct' => 'required'
      ]);

      $key = $request->key;
      $correct = $request->correct;
      $newKey = $key + 1;


      if($request->get('code') !== NULL){
        $code = $request->get('code');
      }else{
        $code = '';
      }

      $questions = Session::get('questions');
      $answers = Session::get('answers');

      $request->session()->put('checked.'.$key, $correct);

      if(isset($questions[$newKey])){
        return view('test.test', ['question' => $questions[$newKey], 'answers' => $answers[$newKey], 'type' => $questions[$newKey]->type, 'key' => $newKey, 'testroomcode' => $code]);
      }
      else{
        if($code !== ''){
          return $this->checkTest(true, $code);
        }
        return $this->checkTest(false, NULL);
      }
    }

    public function checkTest($testroom, $code)
    {
      $checked = [];

      $questions = Session::get('questions');
      $answers = Session::get('answers');

      $userAnswers = '';

      foreach ($questions as $k => $value) {
        $session = Session::get('checked.'.$k);

        if(is_array($session)){
          $check = 0;
          $userAnswers[$value->id] = $session;
          foreach ($session as $key => $v) {
            $checkAnswers[$k][$key] = Answer::where('question_id', '=', $value->id)->where('id', '=', $v)->get()[0];

            foreach ($answers[$k] as $answer) {
              if($answer->id == $v){
                $answer->checked = true;
              }
            }

            if($checkAnswers[$k][$key]->correct == true){
              $check ++;
            }else{
              $check --;
            }

            if($check >= 1){
              $questions[$k]['correct'] = '1';
            }elseif($check < 0){
              $questions[$k]['correct'] = '0';
            }
          }
        }else{
          $userAnswers[$value->id] = $session;

          $checkAnswers[$k] = Answer::where('question_id', '=', $value->id)->where('id', '=', $session)->get()[0];

          foreach ($answers[$k] as $answer) {
            if($answer->id == $session){
              $answer->checked = true;
            }
          }

          if($checkAnswers[$k]->correct == true){
            $questions[$k]['correct'] = '1';
          }else{
            $questions[$k]['correct'] = '0';
          }
        }
      }

      if($testroom && $code !== NULL){
        $correctAnswers = '';
        $userAnswers = json_encode($userAnswers);

        foreach ($questions as $key => $value) {
          if($value->correct == 1){
            $correctAnswers ++;
          }
        }

        $name = Session::get('name');
        $lastname = Session::get('lastname');

        return app('App\Http\Controllers\TestRoomController')->finishTest($correctAnswers, $userAnswers, $code, $name, $lastname);
      }

      return view('test.check', ['questions' => $questions, 'answers' => $answers, 'checked' => $checked]);
    }

    public function endTest()
    {
      Session::forget('questions');
      Session::forget('answers');
      Session::forget('checked');

      return redirect(url('/'));
    }

    public function startTestRoomTest($code)
    {
      $testroom = TestRoom::where('code', '=', $code)->get()[0];

      $questions_id = explode(', ', $testroom->questions_id);

      foreach ($questions_id as $key => $value) {
        $questions[$key] = Question::find($value);
      }

      shuffle($questions);

      Session::put('questions', $questions);

      foreach ($questions as $key => $value) {
        $answers[$key] = Answer::where('question_id', '=', $value->id)->orderByRaw("RAND()")->get();
      }

      Session::put('answers', $answers);

      return view('test.test', ['question' => $questions['0'], 'answers' => $answers['0'], 'type' => $questions[0]->type, 'key' => '0', 'testroomcode' => $code]);
    }
}
