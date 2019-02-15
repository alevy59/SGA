<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Auth;

class UsersController extends Controller
{
	public function __construct() {
		$this->middleware('admin');
	}

    public function show()
    {
    	//return "UsersController@show";
    	$users = User::all();

    	//dd($users);
    	return view('admin.usuarios.show', compact('users'));
    }

    public function create()
    {
    	return view('admin.usuarios.create');
    }

    public function store()
    {
        $attributes = request()
            ->validate([
                'name' => ['required', 'min:3'],
                'email' => 'required',
                'role' => 'required',
                'matricula' => 'required',
                'password' => 'required'
            ]);
        $attributes['password'] = bcrypt(request('password'));

        //dd($attributes);

        User::create($attributes);

        return redirect('/admin/usuarios');
    }
}
