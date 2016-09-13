<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Invite;

class InviteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      $invites = Invite::all();
      return view('admin.invite.index', ['invites' => $invites]);
    }

    public function create()
    {
        $code = str_random(40);
        return view('admin.invite.create', ['code' => $code]);
    }

    public function store(Request $request)
    {
      $this->validate($request, [
         'invite' => 'required',
         'email' => 'required|email'
      ]);

       $input = $request->all();

       Invite::create($input);

       \Mail::send('emails.invite',
           array(
               'invite' => $request->get('invite'),
               'register' => url('/auth/register'),
               'home' => url('/')
           ), function($message) {
             $message->to(\Input::get('email'))
             ->subject('Покана за участие в проекта Green Sheet');
       });

       \Session::flash('flash_message', 'Поканата беше успешно изпратена!');
       return redirect()->route('admin.invite.index');
    }

    public function destroy($id)
    {
        $invite = Invite::findOrFail($id);

        $invite->delete();


        \Session::flash('flash_message', 'Поканата беше успешно изтрита!');
        return redirect()->route('admin.invite.index');
    }

    public function redirect(Request $request)
    {
      $this->validate($request, [
        'invite' => 'required'
      ],[
        'invite.required' => 'Полето с поканата е задължително.'
      ]);

      $invite = $request->get('invite');

      return redirect(url('/auth/register/'.$invite));
    }

    public function newUser(Request $request)
    {
      $validator = \Validator::make($request->all(), [
        'invite-email' => 'required|unique:invite,email|email'
      ],[
        'invite-email.required' => 'Полето с E-mail Ви, е задължително',
        'invite-email.unique' => 'E-mail Ви е добавен към списъка за проверка, в скоро време ще получите поканата си.'
      ]);

      if ($validator->fails()) {
        \Session::flash('error_invite_mail', '');
        return redirect()->back()->withErrors($validator)->withInput();
      }

      $input['email'] = $request->get('invite-email');

      Invite::create($input);

      \Session::flash('invite_email', 'След проверка на вашият E-mail, ще получите поканата си.');
      return redirect(url('/'));
    }

    public function sendInvite($email)
    {
      $code = str_random(40);
      return view('admin.invite.create', ['code' => $code, 'email' => $email]);
    }
}