<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
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
    public function index()
    {
        $objMenu = Menu::All();
        return view('Menus.indexMenu', compact('objMenu'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $objMenu = Menu::all();
        return view('Menus.formMenu', compact('objMenu'));
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
            'name' => 'required|max:255|unique:menus,name',
            'slug' => 'required|max:255|unique:menus,slug',
            'parent' => 'numeric',
            'order' => 'numeric',
        ]);

        //Registramos una opción de menú sin padre
        if ($request->parent == 0) {

            //Elementos actuales sin padre
            $withoutParentOptions = Menu::where('parent', $request->parent)->orderby('order')->get()->toArray();

            if (empty($withoutParentOptions)) {

                $objMenu = new Menu();
                $objMenu->name = $request->name;
                $objMenu->slug = $request->slug;
                $objMenu->icon = $request->icon;
                $objMenu->parent = 0;
                $objMenu->order = 0;
                $objMenu->enabled = $request->enabled != "" ? 1 : 0;
                $objMenu->save();

                return redirect()->route('menu.index')->with('notification', 'Registro exitoso!');
            } else {
                $flagUpdated = false;
                foreach ($withoutParentOptions as $key => $value) {

                    if ($value['order'] >= $request->order) {
                        $orderPosition = $value['order'];
                        $objMenuToUpdate = Menu::find($value['pkMenu']);
                        $objMenuToUpdate->order = $orderPosition + 1;
                        $objMenuToUpdate->save();

                        $flagUpdated = true;
                    }
                }

                if ($flagUpdated) {

                    $objMenu = new Menu();
                    $objMenu->name = $request->name;
                    $objMenu->slug = $request->slug;
                    $objMenu->icon = $request->icon;
                    $objMenu->parent = $request->parent;
                    $objMenu->order = $request->order;
                    $objMenu->enabled = $request->enabled != "" ? 1 : 0;
                    $objMenu->save();

                    return redirect()->route('menu.index')->with('notification', 'Registro exitoso!');
                } else {

                    $lastOrderPosition = array_pop($withoutParentOptions);
                    $newPosition = $lastOrderPosition['order'] + 1;
                    $objMenu = new Menu();
                    $objMenu->name = $request->name;
                    $objMenu->slug = $request->slug;
                    $objMenu->icon = $request->icon;
                    $objMenu->parent = $request->parent;
                    $objMenu->order = $newPosition;
                    $objMenu->enabled = $request->enabled != "" ? 1 : 0;
                    $objMenu->save();

                    return redirect()->route('menu.index')->with('notification', 'Registro exitoso!');
                }
            }
        } else {
            //Elementos hijos actuales de la opción padre
            $childrenOptions = Menu::where('parent', $request->parent)->orderby('order')->get()->toArray();

            /* dd($childrenOptions ); */
            if (empty($childrenOptions)) {

                //No tiene hijos, se agrega por default la posición 0 en orden de elementos
                $objMenu = new Menu();
                $objMenu->name = $request->name;
                $objMenu->slug = $request->slug;
                $objMenu->icon = $request->icon;
                $objMenu->parent = $request->parent;
                $objMenu->order = 0;
                $objMenu->enabled = $request->enabled != "" ? 1 : 0;
                $objMenu->save();

                return redirect()->route('menu.index')->with('notification', 'Registro exitoso!');
            } else {

                //Como sí existen hijos reemplazamos como posición al que tenga el mismo número de orden de elemento y los que esten por encima suben una posición
                $flagUpdated = false;
                foreach ($childrenOptions as $key => $value) {

                    if ($value['order'] >= $request->order) {
                        $orderPosition = $value['order'];
                        $objMenuToUpdate = Menu::find($value['pkMenu']);
                        $objMenuToUpdate->order = $orderPosition + 1;
                        $objMenuToUpdate->save();

                        $flagUpdated = true;
                    }
                }

                if ($flagUpdated) {

                    $objMenu = new Menu();
                    $objMenu->name = $request->name;
                    $objMenu->slug = $request->slug;
                    $objMenu->icon = $request->icon;
                    $objMenu->parent = $request->parent;
                    $objMenu->order = $request->order;
                    $objMenu->enabled = $request->enabled != "" ? 1 : 0;
                    $objMenu->save();

                    return redirect()->route('menu.index')->with('notification', 'Registro exitoso!');
                } else {

                    $lastOrderPosition = array_pop($childrenOptions);
                    $newPosition = $lastOrderPosition['order'] + 1;
                    $objMenu = new Menu();
                    $objMenu->name = $request->name;
                    $objMenu->slug = $request->slug;
                    $objMenu->icon = $request->icon;
                    $objMenu->parent = $request->parent;
                    $objMenu->order = $newPosition;
                    $objMenu->enabled = $request->enabled != "" ? 1 : 0;
                    $objMenu->save();

                    return redirect()->route('menu.index')->with('notification', 'Registro exitoso!');
                }
            }
        }
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
    public function edit(Menu $menu)
    {
        $objMenu = Menu::all();
        return view('Menus.formEditMenu', compact('menu', 'objMenu'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Menu $menu)
    {

        
        $pkMenu = $menu->pkMenu;
        $menuUpdated = $menu->name;
        
        $validated = $request->validate([
            'name' => 'required|max:255|unique:menus,name,'.$pkMenu.',pkMenu',
            'slug' => 'required|max:255|unique:menus,slug,'.$pkMenu.',pkMenu',
            'parent' => 'numeric',
            'order' => 'numeric',
        ]);
        
        $menu->name = $request->name;
        $menu->slug = $request->slug;
        $menu->icon = $request->icon;
        $menu->parent = $request->parent;
        $menu->order = $request->order;
        $menu->enabled = $request->enabled != "" ? 1 : 0;
        $menu->save();

        return redirect()->route('menu.index')->with('notification', 'El registro '.$menuUpdated.' ha sido actualizado correctamente');
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Menu $menu)
    {
        $disabledMenu = $menu->name;

        $menu->enabled = 0;
        $menu->save(); //UPDATE

        return redirect()->route('menu.index')->with('notification', 'El registro ' . $disabledMenu . ' ha sido deshabilitado correctamente');
    }
}
