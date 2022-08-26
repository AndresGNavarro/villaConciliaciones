<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\Subsidiary;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\MailUserRegistered;


class UserController extends Controller
{
    //Agregamos middleware Auth en constructor para que todas las rutas que resuelvan en este controlador requieran estar loggeado
    public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * Display a listing of the users
     *
     * @param  \App\Models\User  $model
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {

        $objUser = User::All();
        return view('users.indexUser', compact('objUser'));
    }

    public function create()
    {

        $objRole = Role::All();
        $objSubsidiary = Subsidiary::All();
        return view('users.formUser', compact('objRole', 'objSubsidiary'));
    }

    public function store(Request $request)
    {
        /* dd($request); */
        $validated = $request->validate([
            'name' => 'required|max:255|unique:users,name',
            'email' => 'required|max:255|email|unique:users,email',
            'role' => 'required',
            'sucursal' => 'required',
            'sucursalAdministra' => 'required',
        ]);

        
        try {
            DB::beginTransaction();
            $PasswordDefault = Str::random(6);
            $HashDefault  = Hash::make($PasswordDefault);

            $objUser = new User();
            $objUser->name = $request->name;
            $objUser->email = $request->email;
            $objUser->pkRole = $request->role;
            $objUser->pkSubsidiary = $request->sucursal;
            $objUser->password = $HashDefault;
            $objUser->save();

            if ($request->sucursalAdministra) {

                $array = $request->sucursalAdministra;
                $objUser->userSubsidiary()->attach($array);
            }

            /* Aqui enviamos los parametros al mail a través de MailUserRegistered */
            DB::afterCommit(function () use ($objUser, $PasswordDefault) {
                Mail::to($objUser->email)->send(new MailUserRegistered($objUser->name, $objUser->email, $PasswordDefault));
            });

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->route('user.index')->with('notificationDanger', 'Ha ocurrido un error al intentar crear el registro, notificar al Administrador!');
        }


        return redirect()->route('user.index')->with('notification', 'Registro exitoso!');
    }

    public function edit(User $user)
    {
        $objRole = Role::All();
        $objSubsidiary = Subsidiary::All();
        $objUserSubsidiary = User::join('user_subsidiary', 'users.id', 'user_subsidiary.id')
        ->where('user_subsidiary.id', '=', $user->id)
        ->select(['user_subsidiary.pkSubsidiary'])
        ->get()
        ->toArray();
        $arrayUserSubsidiary = [];
        foreach ($objUserSubsidiary as $option) {
            array_push($arrayUserSubsidiary,$option['pkSubsidiary']);
        }
        return view('users.formEditUser', compact('user', 'objRole', 'objSubsidiary','arrayUserSubsidiary'));
    }

    public function update(Request $request, User $user)
    {

        $pkUser = $user->id;
        $validated = $request->validate([
            'name' => 'required|max:255|unique:users,name,' . $pkUser . ',id',
            'email' => 'required|max:255|email|unique:users,email,' . $pkUser . ',id',
            'role' => 'required',
            'sucursal' => 'required',
            'sucursalAdministra' => 'required',
        ]);

        try {
            DB::beginTransaction();
            $updatedUser = $user->name;
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $user->pkRole = $request->input('role');
            $user->pkSubsidiary = $request->input('sucursal');
            $user->save(); //UPDATE

            $user->userSubsidiary()->detach();
            if ($request->sucursalAdministra) {

                $array = $request->sucursalAdministra;
                $user->userSubsidiary()->attach($array);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->route('user.index')->with('notificationDanger', 'Ha ocurrido un error al intentar actualizar el registro, notificar al Administrador!');
        }


        return redirect()->route('user.index')->with('notification', 'El registro ' . $updatedUser . ' ha sido actualizado correctamente');
    }

    public function destroy(User $user)
    {
        /* dd($user); */
        $deletedUser = $user->name;
        try {
            DB::beginTransaction();
            $user->userSubsidiary()->detach();
            $user->delete();
            
            DB::commit();
        } catch (exception $e) {
            DB::rollback();
            return redirect()->route('user.index')->with('notificationDanger', 'Ha ocurrido un error al intentar eliminar el registro ' . $deletedUser . '!');
        }
        return redirect()->route('user.index')->with('notification', 'El registro ' . $deletedUser . ' se ha eliminado correctamente');
    }
}
