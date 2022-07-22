<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
     //Agregamos middleware Auth en constructor para que todas las rutas que resuelvan en este controlador requieran estar loggeado
     public function __construct()
     {
         $this->middleware('auth');
     }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $objRole = Role::All();
        return view('Roles.indexRole', compact('objRole'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('Roles.formRole');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|max:255|unique:roles,description',
        ]);

        $objRole = new Role();
        $objRole->description = $request->description;
        $objRole->save();

        return redirect()->route('role.index')->with('notification', 'Registro exitoso!');

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Role $role)
    {
        return view('Roles.formEditRole', compact('role'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Role $role)
    {
        $pkRole = $role->pkRole;
        $validated = $request->validate([
            'description' => 'required|max:255|unique:roles,description,'.$pkRole.',pkRole',
        ]);
        
        $updatedRole = $role->description;
        $role->description = $request->input('description');
    	$role->save(); //UPDATE

        return redirect()->route('role.index')->with('notification', 'El registro '.$updatedRole.' ha sido actualizado correctamente');
    
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Role $role)
    {
        $deletedRole = $role->description;
         try {
             $role->delete();
             }
             catch (exception $e) {
                 return redirect()->route('role.index')->with('notificationDanger', 'Ha ocurrido un error al intentar eliminar el registro '.$deletedRole.' : Revisar registros relacionados!!');
                
             }
             return redirect()->route('role.index')->with('notification', 'El registro '.$deletedRole.' se ha eliminado correctamente');
    }
}
