<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Document;
use App\Models\Period;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Imports\DocumentIataImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\TryCatch;

class DocumentController extends Controller
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
    public function indexIata()
    {
        $objPeriod = Period::leftjoin('documents', 'periods.pkPeriod', '=', 'documents.pkPeriod')
        ->where('pkDocumentType', 1)
        ->orWhere('pkDocumentType', null)
        ->select([
            'documents.pkDocument',
            'documents.originalName',
            'documents.diskName',
            'documents.iata',
            'documents.id',
            'documents.pkDocumentType',
            'periods.pkPeriod',
            'periods.reference',
            'periods.description',
        ])->get();
        return view('Documents.formIata', compact('objPeriod'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function validatePeriodIata(Request $request)
    {
        
        $data = array();

        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xls,xlsx|max:2048'
        ]);

        if ($validator->fails()) {

            $data['success'] = 0;
            $data['error'] = $validator->errors()->first('file'); // Error response

        } else {
            if ($request->file('file')) {

                $dataContentNewIata = "";
                $dataContentUpdateIata = "";
                $file = $request->file('file');
                $filename = $file->getClientOriginalName();
                
                // Upload file
                $path = Storage::disk('documents')->put('/', $file);

                $dataImport = Excel::toArray(new DocumentIataImport, $file);

                //Realizamos búsqueda para obtener el renglón de los datos con periodo y IATA
                $arrayReferenceIata = searchThroughArray('Billing Period', $dataImport[0]);

                $stringArrayReferenceIata = $arrayReferenceIata[1][2];

                $stringArrayReferenceIataP1 = explode(':', $stringArrayReferenceIata);
                $stringArrayReferenceIataP2 = explode('-', $stringArrayReferenceIataP1[1]);

                $referenceIata = $stringArrayReferenceIataP2[0];
                $referenceIata = preg_replace('/\s+/', '', $referenceIata);
                $referencePeriod = $stringArrayReferenceIataP2[1];
                $referencePeriod = preg_replace('/\s+/', '', $referencePeriod);

                //Realizamos consulta de los documentos IATA existentes de este periodo
                $objDocumentIataAsignedToPeriod = Document::join('periods', 'documents.pkPeriod', '=', 'periods.pkPeriod')
                    ->join('document_types', 'documents.pkDocumentType', '=', 'document_types.pkDocumentType')
                    ->where('document_types.description', 'IATA')
                    ->where('periods.reference', $referencePeriod)
                    ->select([
                        'documents.pkDocument',
                        'documents.originalName',
                        'documents.diskName',
                        'document_types.pkDocumentType',
                        'document_types.description',
                        'periods.description',
                    ])
                    ->get()
                    ->toArray();

                if ($objDocumentIataAsignedToPeriod) {
                    //Va a reemplazar un documento IATA relacionado al periodo
                    $dataContentUpdateIata .=
                        "<tr>
                    <input type='hidden' name='originalName[]'  value='{$filename}' class='inputForm'>
                    <input type='hidden' name='diskName[]'  value='{$path}'>
                    <input type='hidden' name='iata[]'  value='{$referenceIata}'>
                    <input type='hidden' name='referencePeriod[]'  value='{$referencePeriod}'>

                    <td scope='row' white-space: nowrap;'>{$filename}</td>
                    <td scope='row' white-space: nowrap; '>{$referenceIata}
                    </td>
                    <td scope='row' white-space: nowrap; '>{$referencePeriod}
                    </td>
                    <td scope='row' class='text-center' white-space: nowrap; '>
                    <button class='btn btn-sm btn-danger' onclick='return deleteRow(this)'>
                    Eliminar <span class='btn-inner--icon'><i class='ni ni-fat-remove'></i></span>
                    </button>
                    </td></tr> ";
                } else {
                    //Se crea una nueva relación entre documento IATA y periodo
                    $dataContentNewIata .=
                        "<tr>
                    <input type='hidden' name='originalName[]'  value='{$filename}' class='inputForm'>
                    <input type='hidden' name='diskName[]'  value='{$path}'>
                    <input type='hidden' name='iata[]'  value='{$referenceIata}'>
                    <input type='hidden' name='referencePeriod[]'  value='{$referencePeriod}'>

                    <td scope='row' white-space: nowrap;'>{$filename}</td>
                    <td scope='row' white-space: nowrap; '>{$referenceIata}
                    </td>
                    <td scope='row' white-space: nowrap; '>{$referencePeriod}
                    </td>
                    <td scope='row' class='text-center' white-space: nowrap; '>
                    <button class='btn btn-sm btn-danger' onclick='return deleteRow(this)'>
                    Eliminar <span class='btn-inner--icon'><i class='ni ni-fat-remove'></i></span>
                    </button>
                    </td></tr>";
                }


                // Response
                $data['dataContentNewIata'] = $dataContentNewIata;
                $data['dataContentUpdateIata'] = $dataContentUpdateIata;
                $data['success'] = 1;
                $data['message'] = 'Archivos cargados con éxito!';
            } else {
                // Response
                $data['success'] = 0;
                $data['message'] = 'Ha ocurrido un error durante la carga.';
            }
        }

        return response()->json($data);
    }

    public function storePeriodIata(Request $request)
    {
        /* dd($request); */

        $validated = $request->validate([
            'originalName' => 'required',
            'diskName' => 'required',
            'iata' => 'required',
            'referencePeriod' => 'required',
        ]);

        $arrayDocumentName = $request->originalName;
        $arrayDocumentUrl = $request->diskName;
        $arrayDocumentIata = $request->iata;
        $arrayDocumentReferencePeriod = $request->referencePeriod;

        try {
            DB::beginTransaction();
            foreach ($arrayDocumentReferencePeriod as $key => $referencePeriod) {

                $objDocumentIataAsignedToPeriod = Document::join('periods', 'documents.pkPeriod', '=', 'periods.pkPeriod')
                    ->join('document_types', 'documents.pkDocumentType', '=', 'document_types.pkDocumentType')
                    ->where('document_types.description', 'IATA')
                    ->where('periods.reference', $referencePeriod)
                    ->select([
                        'documents.pkDocument',
                        'documents.originalName',
                        'documents.diskName',
                        'documents.iata',
                        'documents.id',
                        'documents.pkPeriod',
                        'document_types.pkDocumentType',
                        'document_types.description',
                        'periods.description',
                    ])
                    ->get()
                    ->toArray();

                if ($objDocumentIataAsignedToPeriod) {

                    $objDocument = Document::findOrFail($objDocumentIataAsignedToPeriod[0]['pkDocument']);
                    $objDocument->originalName = $arrayDocumentName[$key];
                    $objDocument->diskName = $arrayDocumentUrl[$key];
                    $objDocument->id = auth()->id();
                    $objDocument->save();

                } else {

                    $pkPeriod = Period::where('reference',$referencePeriod)->pluck('pkPeriod')->all();
                    
                    $objDocument = new Document();
                    $objDocument->originalName = $arrayDocumentName[$key];
                    $objDocument->diskName = $arrayDocumentUrl[$key];
                    $objDocument->iata = $arrayDocumentIata[$key];
                    $objDocument->id = auth()->id();
                    $objDocument->pkDocumentType = 1;
                    $objDocument->pkPeriod = $pkPeriod[0];
                    $objDocument->save();
                }
            }
            DB::commit();
        } catch (exception $e) {
            DB::rollBack();
            return redirect()->route('iata.index')->with('notificationDanger', 'Ha ocurrido un error durante el proceso, notificar al Administrador!');
        }

        return redirect()->route('iata.index')->with('notification', 'Los registros han sido actualizados correctamente!');
    }

    function downloadIataFromPeriod($url){
        
        try {
        $documentName = Document::where('diskName', $url)->pluck('originalName')->all();
        $pathToFile = storage_path("app/documents/".$url);
        return response()->download($pathToFile,$documentName[0]);
        } catch (exception $e) {
            return redirect()->route('iata.index')->with('notificationDanger', 'Ha ocurrido un error durante el proceso, notificar al Administrador!'.$e);
        }
        
        
        
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Document  $document
     * @return \Illuminate\Http\Response
     */
    public function edit(Document $document)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Document  $document
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Document $document)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Document  $document
     * @return \Illuminate\Http\Response
     */
    public function destroy(Document $document)
    {
        //
    }
}
