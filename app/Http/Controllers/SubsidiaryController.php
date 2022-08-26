<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Subsidiary;
use Illuminate\Http\Request;

class SubsidiaryController extends Controller
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
       $objSubsidiary = Subsidiary::All();
       return view('Subsidiaries.indexSubsidiary', compact('objSubsidiary'));
   }

   /**
    * Show the form for creating a new resource.
    *
    * @return \Illuminate\Http\Response
    */
   public function create()
   {
       return view('Subsidiaries.formSubsidiary');
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
           'description' => 'required|max:255|unique:subsidiaries,description',
           'iata' => 'required|max:255|unique:subsidiaries,iata',
       ]);

       $objSubsidiary = new Subsidiary();
       $objSubsidiary->description = $request->description;
       $objSubsidiary->iata = $request->iata;
       $objSubsidiary->save();

       return redirect()->route('subsidiary.index')->with('notification', 'Registro exitoso!');

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
   public function edit(Subsidiary $subsidiary)
   {
       return view('Subsidiaries.formEditSubsidiary', compact('subsidiary'));
   }

   /**
    * Update the specified resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
   public function update(Request $request, Subsidiary $subsidiary)
   {
       $pkSubsidiary = $subsidiary->pkSubsidiary;
       $validated = $request->validate([
           'description' => 'required|max:255|unique:subsidiaries,description,'.$pkSubsidiary.',pkSubsidiary',
           'iata' => 'required|max:255|unique:subsidiaries,iata,'.$pkSubsidiary.',pkSubsidiary',
       ]);
       
       $updatedSubsidiary = $subsidiary->description;
       $subsidiary->description = $request->input('description');
       $subsidiary->iata = $request->input('iata');
       $subsidiary->save(); //UPDATE

       return redirect()->route('subsidiary.index')->with('notification', 'El registro '.$updatedSubsidiary.' ha sido actualizado correctamente');
   
   }

   /**
    * Remove the specified resource from storage.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
   public function destroy(Subsidiary $subsidiary)
   {
       $deletedSubsidiary = $subsidiary->description;
        try {
            $subsidiary->delete();
            }
            catch (exception $e) {
                return redirect()->route('subsidiary.index')->with('notificationDanger', 'Ha ocurrido un error al intentar eliminar el registro '.$deletedSubsidiary.' : Revisar registros relacionados!');
               
            }
            return redirect()->route('subsidiary.index')->with('notification', 'El registro '.$deletedSubsidiary.' se ha eliminado correctamente');
   }
}