<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Role;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $menus = Menu::menus(true);
        return view('Roles.formRole', compact('menus'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        /* dd($request); */
        $validated = $request->validate([
            'description' => 'required|max:255|unique:roles,description',
        ]);
        
        DB::beginTransaction();
        try {
            $objRole = new Role();
            $objRole->description = $request->description;
            $objRole->save();

            if ($request->checkMenu) {

                $arrayOptionMenu = $request->checkMenu;
                $objRole->menus()->attach($arrayOptionMenu);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->route('role.index')->with('notificationDanger', 'Ha ocurrido un error al intentar crear el registro, notificar al Administrador!');
        }
        

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
    public function edit(Request $request, Role $role)
    {

        $menus = Menu::menus(true);
        return view('Roles.formEditRole', compact('role', 'menus'));
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
        /* dd($request); */
        $pkRole = $role->pkRole;
        $validated = $request->validate([
            'description' => 'required|max:255|unique:roles,description,' . $pkRole . ',pkRole',
        ]);

        DB::beginTransaction();
        try {
            $updatedRole = $role->description;
            $role->description = $request->input('description');
            $role->save(); //UPDATE

            $role->menus()->detach();
            if ($request->checkMenu) {

                $arrayOptionMenu = $request->checkMenu;
                $role->menus()->attach($arrayOptionMenu);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollback();
            return redirect()->route('role.index')->with('notificationDanger', 'Ha ocurrido un error al intentar actualizar el registro ' . $updatedRole . ' : Revisar registros relacionados!!');
        }

        return redirect()->route('role.index')->with('notification', 'El registro ' . $updatedRole . ' ha sido actualizado correctamente');
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
        DB::beginTransaction();
        try {
            $role->menus()->detach();
            $role->delete();
            DB::commit();
        } catch (exception $e) {
            DB::rollback();
            return redirect()->route('role.index')->with('notificationDanger', 'Ha ocurrido un error al intentar eliminar el registro ' . $deletedRole . ' : Revisar registros relacionados!!');
        }
        return redirect()->route('role.index')->with('notification', 'El registro ' . $deletedRole . ' se ha eliminado correctamente');
    }
}
