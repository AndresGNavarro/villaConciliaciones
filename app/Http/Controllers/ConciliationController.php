<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Conciliation;
use App\Models\Period;
use App\Models\Document;
use App\Models\User;
use App\Exports\ConciliationExport;
use App\Exports\ResumenConciliationExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Imports\DocumentIataImport;
use App\Imports\DocumentPrevioImport;
use App\Imports\DocumentPrevioObsImport;


class ConciliationController extends Controller
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
        $idUser = auth()->id();
        $objUserSubsidiary = User::join('user_subsidiary', 'users.id', 'user_subsidiary.id')
            ->join('subsidiaries', 'user_subsidiary.pkSubsidiary', 'subsidiaries.pkSubsidiary')
            ->where('user_subsidiary.id', '=', $idUser)
            ->get();
        $objConciliation = Conciliation::join('documents', 'conciliations.pkConciliation', '=', 'documents.pkConciliation')
        ->where('conciliations.id', $idUser)
        ->where('documents.pkDocumentType', 4)
        ->get();
        return view('Conciliations.indexConciliation', compact('objConciliation', 'objUserSubsidiary'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        
        return view('Conciliations.formConciliation');
    }

    public function show(Conciliation $conciliation)
    {
        $objDocumentConciliation = Document::where('pkConciliation', $conciliation->pkConciliation)->get();
        $baseMargen = $conciliation->valuePreviousReport+$conciliation->valueDiferences;
        $variabilidad = ROUND(($baseMargen/$conciliation->valueInvoiceBsp)*100,2);
        return view('Conciliations.viewConciliation', compact('conciliation', 'objDocumentConciliation','variabilidad'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //Recibir y validar archivos
        $validator = Validator::make($request->all(), [
            'file0' => 'required|max:2048',
            'file1' => 'required|max:2048'
        ]);

        if ($validator->fails()) {

            $data['success'] = 0;
            $data['errorFile1'] = $validator->errors()->first('file0'); // Error response
            $data['errorFile2'] = $validator->errors()->first('file1'); // Error response

            return response()->json($data);

        } else {
            try {
                //TO ARRAY FILES
                $filePrevio = $request->file('file0');
                $fileNamePrevio = $filePrevio[0]->getClientOriginalName();
                $importPrevio = new DocumentPrevioImport;
                Excel::import($importPrevio, $filePrevio[0]);
                $dataArrayPrevio = $importPrevio->getArray();

                $importPrevioObservaciones = new DocumentPrevioObsImport;
                Excel::import($importPrevioObservaciones, $filePrevio[0]);
                $dataArrayObsPrevio = $importPrevioObservaciones->getArray();
            
                $fileIata = $request->file('file1');
                $fileNameIata = $fileIata[0]->getClientOriginalName();
                $dataArrayIata = Excel::toArray(new DocumentIataImport, $fileIata[0]);
                $dataArrayIata = $dataArrayIata[0];
                
                //GET INFO LIKE PERIOD, DESCRIPTION PERIOD AND IATA REFERENCE
                $arrayHeadingInfoIata = $this->getHeadingInfoDocIata($dataArrayIata);
               
                //VALIDATION IATA ALLOWED TO USER
                $idUser = auth()->id();
                $objUserSubsidiary = User::join('user_subsidiary', 'users.id', 'user_subsidiary.id')
                ->join('subsidiaries', 'user_subsidiary.pkSubsidiary', 'subsidiaries.pkSubsidiary')
                ->where('user_subsidiary.id', '=', $idUser)
                ->where('subsidiaries.iata', '=', $arrayHeadingInfoIata['referenceIata'])
                ->get()
                ->toArray();
                
                if (count($objUserSubsidiary) == 0) {
                    $data['success'] = 0;
                    $data['error'] = 'Su usuario no tiene relación con la IATA ('.$arrayHeadingInfoIata['referenceIata'].') agregada en el documento';
                    return response()->json($data);
                }

                //VALIDATION RELATION IATAS BETWEEN DOCUMENTS
                $resultValidationRelationIataDoc = $this->validateRelationDocuments($dataArrayPrevio, $arrayHeadingInfoIata['referenceIata']);
                if ($resultValidationRelationIataDoc == '') {
                    $data['success'] = 0;
                    $data['error'] = 'Documento previo no tiene relación con la IATA ('.$arrayHeadingInfoIata['referenceIata'].')';
                    return response()->json($data);
                }

                //STAR OF DOMESTIC AND INTERNATIONAL TICKETING (IN THE DOCUMENT WE CAN IDENTIFY THE HEADERS WITH "SCOPE" SINCE IT IS ONLY DECLARED IN THOSE TITLES)
                $arrayTipoBoleto = searchThroughArray('SCOPE', $dataArrayIata);

                $indicesTipoBoleto = array_keys($arrayTipoBoleto);
                //THE CURRENT DOCUMENT SHOWS INTERNATIONAL TICKETS FIRST SO THE FOLLOWING VARIABLE INDICATES THE START OF THAT SECTION
                $inicioBoletajeInternacional = $indicesTipoBoleto[0];
                //AND THE FOLLOWING VARIABLE INDICATES THE END AND THE BEGINNING OF DOMESTIC TICKETING
                $inicioBoletajeDomestico = $indicesTipoBoleto[1];
                //GET THE LAST ROW FROM THE DOCUMENT IATA
                $renglonFinalDocumento = endKey($dataArrayIata);

                //GET CELL GRAND TOTAL IATA
                $arrayGrandTotalIata = searchThroughArray('GRAND TOTAL (MXN)', $dataArrayIata);
                $indiceGrandTotal = array_keys($arrayGrandTotalIata);
                $lastElement = endKey($arrayGrandTotalIata[$indiceGrandTotal[0]]);

                $indiceSeg = $lastElement;
                $grandTotal = NULL;

                while ($grandTotal == NULL) {
                    $grandTotal = $arrayGrandTotalIata[$indiceGrandTotal[0]][$indiceSeg];
                    $indiceSeg--;
                }

                //ARRAY WITH ALL TICKETS IATA
                $arrayAllTickets = [];
                $totalRowsAllTickets = 0;

                //ARRAY WITH RESULTS ANALYSIS
                //INTERNACIONAL
                $resultadosInternacional = [];
                $totalBoletosInternacional = 0;
                $totalBoletosInternacionalTKTT = 0;
                $totalBoletosInternacionalEMDS = 0;
                $totalBoletosInternacionalEMDA = 0;
                $totalBoletosInternacionalCANX = 0;
                $totalBoletosInternacionalCANN = 0;
                $totalBoletosInternacionalRFND = 0;
                $totalBoletosInternacionalADMA = 0;
                $totalBoletosInternacionalACMA = 0;
                $totalBoletosInternacionalSPDR = 0;
                //DOMESTIC
                $resultadosDomestico = [];
                $totalBoletosDomestico = 0;
                $totalBoletosDomesticoTKTT = 0;
                $totalBoletosDomesticoEMDS = 0;
                $totalBoletosDomesticoEMDA = 0;
                $totalBoletosDomesticoCANX = 0;
                $totalBoletosDomesticoCANN = 0;
                $totalBoletosDomesticoRFND = 0;
                $totalBoletosDomesticoADMA = 0;
                $totalBoletosDomesticoACMA = 0;
                //ARRAY WITH TKTT, EMDA Y EMDS
                $arrayAllTicketsBsp = [];

                for ($i = $inicioBoletajeInternacional; $i < $inicioBoletajeDomestico; $i++) {
                    //IDENTIFY TICKET TYPE
                    if ($dataArrayIata[$i][1] == 'TKTT' || $dataArrayIata[$i][1] == 'EMDA' || $dataArrayIata[$i][1] == 'EMDS') {

                        $totalRowsAllTickets++;
                        $arrayAllTickets[$totalRowsAllTickets]['Tipo Boleto'] =  $dataArrayIata[$i][1];
                        $arrayAllTickets[$totalRowsAllTickets]['Boleto'] = $dataArrayIata[$i][2];

                        if ($dataArrayIata[$i][1] == 'TKTT') {
                            $totalBoletosInternacionalTKTT++;
                        }
                        if ($dataArrayIata[$i][1] == 'EMDA') {
                            $totalBoletosInternacionalEMDA++;
                        }
                        if ($dataArrayIata[$i][1] == 'EMDS') {
                            $totalBoletosInternacionalEMDS++;
                        }
                        $totalBoletosInternacional++;

                        //PROCESS VALIDATION
                        $resultadosInternacional = $this->analysisTktt($dataArrayIata[$i], $resultadosInternacional, $dataArrayPrevio);
                        //PROCESS GET ARRAY ALL TICKETS
                        $arrayAllTicketsBsp = $this->getAllTicketsBsp($dataArrayIata[$i],$arrayAllTicketsBsp, $type = 'I');
                    } elseif ($dataArrayIata[$i][1] == 'CANX') {
                        $totalBoletosInternacional++;
                        $totalBoletosInternacionalCANX++;

                        $totalRowsAllTickets++;
                        $arrayAllTickets[$totalRowsAllTickets]['Tipo Boleto'] =  $dataArrayIata[$i][1];
                        $arrayAllTickets[$totalRowsAllTickets]['Boleto'] = $dataArrayIata[$i][2];

                        //PROCESS VALIDATION
                        $resultadosInternacional = $this->analysisCanx($dataArrayIata[$i], $resultadosInternacional, $dataArrayPrevio);
                    } elseif ($dataArrayIata[$i][1] == 'CANN') {
                        $totalBoletosInternacional++;
                        $totalBoletosInternacionalCANN++;

                        $totalRowsAllTickets++;
                        $arrayAllTickets[$totalRowsAllTickets]['Tipo Boleto'] =  $dataArrayIata[$i][1];
                        $arrayAllTickets[$totalRowsAllTickets]['Boleto'] = $dataArrayIata[$i][2];
                    } elseif ($dataArrayIata[$i][1] == 'RFND') {
                        $totalBoletosInternacional++;
                        $totalBoletosInternacionalRFND++;

                        $totalRowsAllTickets++;
                        $arrayAllTickets[$totalRowsAllTickets]['Tipo Boleto'] =  $dataArrayIata[$i][1];
                        $arrayAllTickets[$totalRowsAllTickets]['Boleto'] = $dataArrayIata[$i][2];

                        //PROCESS VALIDATION
                        $resultadosInternacional = $this->analysisRfnd($dataArrayIata[$i], $resultadosInternacional, $dataArrayPrevio);
                        $arrayAllTicketsBsp = $this->getAllTicketsBsp($dataArrayIata[$i],$arrayAllTicketsBsp, $type = 'I');
                    } elseif ($dataArrayIata[$i][1] == 'ADMA') {
                        $totalBoletosInternacional++;
                        $totalBoletosInternacionalADMA++;

                        $totalRowsAllTickets++;
                        $arrayAllTickets[$totalRowsAllTickets]['Tipo Boleto'] =  $dataArrayIata[$i][1];
                        $arrayAllTickets[$totalRowsAllTickets]['Boleto'] = $dataArrayIata[$i][2];

                        //PROCESS VALIDATION
                        $resultadosInternacional = $this->analysisAdma($dataArrayIata[$i], $resultadosInternacional, $dataArrayPrevio);
                        $arrayAllTicketsBsp = $this->getAllTicketsBsp($dataArrayIata[$i],$arrayAllTicketsBsp, $type = 'I');
                    } elseif ($dataArrayIata[$i][1] == 'ACMA' OR $dataArrayIata[$i][1] == 'SPCR') {
                        $totalBoletosInternacional++;
                        $totalBoletosInternacionalACMA++;

                        $totalRowsAllTickets++;
                        $arrayAllTickets[$totalRowsAllTickets]['Tipo Boleto'] =  $dataArrayIata[$i][1];
                        $arrayAllTickets[$totalRowsAllTickets]['Boleto'] = $dataArrayIata[$i][2];

                        //PROCESS VALIDATION
                        $resultadosInternacional = $this->analysisAcma($dataArrayIata[$i], $resultadosInternacional, $dataArrayPrevio);
                        $arrayAllTicketsBsp = $this->getAllTicketsBsp($dataArrayIata[$i],$arrayAllTicketsBsp, $type = 'I');
                       
                    } elseif ($dataArrayIata[$i][1] == 'SPDR') {
                        $totalBoletosInternacional++;
                        $totalBoletosInternacionalSPDR++;

                        $totalRowsAllTickets++;
                        $arrayAllTickets[$totalRowsAllTickets]['Tipo Boleto'] =  $dataArrayIata[$i][1];
                        $arrayAllTickets[$totalRowsAllTickets]['Boleto'] = $dataArrayIata[$i][2];

                        //PROCESS VALIDATION
                        $resultadosInternacional = $this->analysisSpdr($dataArrayIata[$i], $resultadosInternacional, $dataArrayPrevio);
                    }
                }

                for ($i = $inicioBoletajeDomestico; $i < $renglonFinalDocumento; $i++) {
                    //IDENTIFY TICKET TYPE
                    if ($dataArrayIata[$i][1] == 'TKTT' || $dataArrayIata[$i][1] == 'EMDA' || $dataArrayIata[$i][1] == 'EMDS') {

                        $totalRowsAllTickets++;
                        $arrayAllTickets[$totalRowsAllTickets]['Tipo Boleto'] =  $dataArrayIata[$i][1];
                        $arrayAllTickets[$totalRowsAllTickets]['Boleto'] = $dataArrayIata[$i][2];

                        if ($dataArrayIata[$i][1] == 'TKTT') {
                            $totalBoletosDomesticoTKTT++;
                        }
                        if ($dataArrayIata[$i][1] == 'EMDA') {
                            $totalBoletosDomesticoEMDA++;
                        }
                        if ($dataArrayIata[$i][1] == 'EMDS') {
                            $totalBoletosDomesticoEMDS++;
                        }
                        $totalBoletosDomestico++;

                        $resultadosDomestico = $this->analysisTktt($dataArrayIata[$i], $resultadosDomestico, $dataArrayPrevio);
                        $arrayAllTicketsBsp = $this->getAllTicketsBsp($dataArrayIata[$i], $arrayAllTicketsBsp,$type = 'D');
                    } elseif ($dataArrayIata[$i][1] == 'CANX') {
                        $totalBoletosDomestico++;
                        $totalBoletosDomesticoCANX++;

                        $totalRowsAllTickets++;
                        $arrayAllTickets[$totalRowsAllTickets]['Tipo Boleto'] =  $dataArrayIata[$i][1];
                        $arrayAllTickets[$totalRowsAllTickets]['Boleto'] = $dataArrayIata[$i][2];

                        //PROCESS VALIDATION
                        $resultadosDomestico = $this->analysisCanx($dataArrayIata[$i], $resultadosDomestico, $dataArrayPrevio);
                    } elseif ($dataArrayIata[$i][1] == 'CANN') {
                        $totalBoletosDomestico++;
                        $totalBoletosDomesticoCANN++;

                        $totalRowsAllTickets++;
                        $arrayAllTickets[$totalRowsAllTickets]['Tipo Boleto'] =  $dataArrayIata[$i][1];
                        $arrayAllTickets[$totalRowsAllTickets]['Boleto'] = $dataArrayIata[$i][2];
                    } elseif ($dataArrayIata[$i][1] == 'RFND') {
                        $totalBoletosDomestico++;
                        $totalBoletosDomesticoRFND++;

                        $totalRowsAllTickets++;
                        $arrayAllTickets[$totalRowsAllTickets]['Tipo Boleto'] =  $dataArrayIata[$i][1];
                        $arrayAllTickets[$totalRowsAllTickets]['Boleto'] = $dataArrayIata[$i][2];

                        //PROCESS VALIDATION
                        $resultadosDomestico = $this->analysisRfnd($dataArrayIata[$i], $resultadosDomestico, $dataArrayPrevio);
                        $arrayAllTicketsBsp = $this->getAllTicketsBsp($dataArrayIata[$i], $arrayAllTicketsBsp,$type = 'D');
                    } elseif ($dataArrayIata[$i][1] == 'ADMA') {
                        $totalBoletosDomestico++;
                        $totalBoletosDomesticoADMA++;

                        $totalRowsAllTickets++;
                        $arrayAllTickets[$totalRowsAllTickets]['Tipo Boleto'] =  $dataArrayIata[$i][1];
                        $arrayAllTickets[$totalRowsAllTickets]['Boleto'] = $dataArrayIata[$i][2];

                        //PROCESS VALIDATION
                        $resultadosDomestico = $this->analysisAdma($dataArrayIata[$i], $resultadosDomestico, $dataArrayPrevio);
                        $arrayAllTicketsBsp = $this->getAllTicketsBsp($dataArrayIata[$i], $arrayAllTicketsBsp,$type = 'D');
                    } elseif ($dataArrayIata[$i][1] == 'ACMA' OR $dataArrayIata[$i][1] == 'SPCR') {
                        $totalBoletosDomestico++;
                        $totalBoletosDomesticoACMA++;

                        $totalRowsAllTickets++;
                        $arrayAllTickets[$totalRowsAllTickets]['Tipo Boleto'] =  $dataArrayIata[$i][1];
                        $arrayAllTickets[$totalRowsAllTickets]['Boleto'] = $dataArrayIata[$i][2];

                        //PROCESS VALIDATION
                        $resultadosDomestico = $this->analysisAcma($dataArrayIata[$i], $resultadosDomestico, $dataArrayPrevio);
                        $arrayAllTicketsBsp = $this->getAllTicketsBsp($dataArrayIata[$i], $arrayAllTicketsBsp,$type = 'D');
                    }
                }

                //GET TICKETS OUT OF PERIOD FROM DUCUMENT PREVIOUS
                $resultadosReportePrevioFueraPeriodo = [];
                $arrayTipoVenta = searchThroughArray('Contado:', $dataArrayPrevio);
                $indicesTipoVenta = array_keys($arrayTipoVenta);
                $renglonFinalVentaContado = $indicesTipoVenta[0];
                $valorReportePrevio = 0;
                /* dd($dataArrayObsPrevio); */
                for ($h = 7; $h < $renglonFinalVentaContado ; $h++) {

                    //INICIO Final del calculo de total de reporte previo (sumando las columnas del precio de contado y restando las comisiones).
                    $idBoleto = $dataArrayPrevio[$h]['numeroBoleto'];
                    $valorReportePrevio += $dataArrayPrevio[$h]['netoPagar'];

                    #Buscamos boleto en  archivo de todos los tickets y en caso de no encontrarlo lo identificamos como fuera de periodo
                    $arrayBoletoBsp = searchThroughArray($idBoleto, $arrayAllTickets);
                    if ($arrayBoletoBsp == NULL) {
                        //Si el boleto no es encontrado antes de definirlo como fuera de periodo validamos que no tenga conjunto
                        
                        if (strpos($dataArrayObsPrevio[$h]['observaciones'], 'CONJUNTO') !== false) {
                            //INICIO Se llena array con resultados
                            $arrayBoletoFueraPeriodoResult = [];
                            //Se llena array con resultados
                            $arrayBoletoFueraPeriodoResult['L.A.'] = $dataArrayPrevio[$h]['lineaAerea'];
                            $arrayBoletoFueraPeriodoResult['Fecha'] = $dataArrayPrevio[$h]['fechaEmision'];
                            $arrayBoletoFueraPeriodoResult['Concepto'] = $dataArrayObsPrevio[$h]['observaciones'];
                            $arrayBoletoFueraPeriodoResult['Boleto'] = $idBoleto;
                            $arrayBoletoFueraPeriodoResult['Total'] = ROUND(-$dataArrayPrevio[$h]['netoPagar'], 2);

                            array_push($resultadosReportePrevioFueraPeriodo, $arrayBoletoFueraPeriodoResult);
                            //FIN Se llena array con resultados
                        } else {
                            //INICIO Se llena array con resultados
                            $arrayBoletoFueraPeriodoResult = [];
                            //Se llena array con resultados
                            $arrayBoletoFueraPeriodoResult['L.A.'] = $dataArrayPrevio[$h]['lineaAerea'];
                            $arrayBoletoFueraPeriodoResult['Fecha'] = $dataArrayPrevio[$h]['fechaEmision'];
                            $arrayBoletoFueraPeriodoResult['Concepto'] = 'BOLETO FUERA DE PERIODO';
                            $arrayBoletoFueraPeriodoResult['Boleto'] = $idBoleto;
                            $arrayBoletoFueraPeriodoResult['Total'] = ROUND(-$dataArrayPrevio[$h]['netoPagar'], 2);

                            array_push($resultadosReportePrevioFueraPeriodo, $arrayBoletoFueraPeriodoResult);
                            //FIN Se llena array con resultados
                        }
                         
                    }
                    //FINAL del calculo de total de reporte previo

                }

                //Conteo de totales
                $valorIataDomestico = 0;
                foreach ($resultadosDomestico as $value) {
                    $valorIataDomestico += $value['Total'];
                }
                $valorIataInternacional = 0;
                foreach ($resultadosInternacional as $value) {
                    $valorIataInternacional += $value['Total'];
                }
                $valorPrevioFueraPeriodo = 0;
                foreach ($resultadosReportePrevioFueraPeriodo as $value) {
                    $valorPrevioFueraPeriodo += $value['Total'];
                }
                $valorDiferencias = ROUND($valorIataDomestico + $valorIataInternacional + $valorPrevioFueraPeriodo,2);

                DB::beginTransaction();
                $objConciliation = new Conciliation();
                $objConciliation->id = auth()->id();
                $objConciliation->pkPeriod = $arrayHeadingInfoIata['referencePeriod'];
                $objConciliation->valueInvoiceBsp = $grandTotal;
                $objConciliation->valuePreviousReport = $valorReportePrevio;
                $objConciliation->valueDiferences = $valorDiferencias;
                $objConciliation->status = 2;
                $objConciliation->save();

                $lastPkInsertedConciliation = $objConciliation->pkConciliation;
                /* dd($arrayAllTicketsBsp); */
                $newArrayResultTickets = array_merge($resultadosReportePrevioFueraPeriodo,$resultadosDomestico, $resultadosInternacional);
                array_multisort(array_column($newArrayResultTickets, 'Boleto'), SORT_ASC, $newArrayResultTickets);

                $uuid = Str::uuid()->toString();
                //Devuelve True or false
                $pathPrevio = Excel::store(new ConciliationExport([$newArrayResultTickets, $valorDiferencias, $valorReportePrevio, $grandTotal,$arrayHeadingInfoIata['referencePeriod'], $arrayHeadingInfoIata['referenceIata']]), 'Conciliacion_'.$uuid.'.xlsx', 'documents');
                $pathResumen = Excel::store(new ResumenConciliationExport([$arrayAllTicketsBsp, $grandTotal, $arrayHeadingInfoIata['referencePeriod'], $arrayHeadingInfoIata['referenceIata']]), 'Resumen_'.$uuid.'.xlsx', 'documents');
                
                // Upload files
                $pathStoragePrevio = Storage::disk('documents')->put('/', $filePrevio[0]);
                $pathStorageIata = Storage::disk('documents')->put('/', $fileIata[0]);

                $objDocumentIata = new Document();
                $objDocumentIata->originalName = $fileNameIata;
                $objDocumentIata->diskName = $pathStorageIata;
                $objDocumentIata->iata = $arrayHeadingInfoIata['referenceIata'];
                $objDocumentIata->id = auth()->id();
                $objDocumentIata->pkDocumentType = 1;
                $objDocumentIata->pkPeriod = $arrayHeadingInfoIata['referencePeriod'];
                $objDocumentIata->pkConciliation = $lastPkInsertedConciliation;
                $objDocumentIata->save();

                $objDocumentPrevio = new Document();
                $objDocumentPrevio->originalName = $fileNamePrevio;
                $objDocumentPrevio->diskName = $pathStoragePrevio;
                $objDocumentPrevio->iata = $arrayHeadingInfoIata['referenceIata'];
                $objDocumentPrevio->id = auth()->id();
                $objDocumentPrevio->pkDocumentType = 2;
                $objDocumentPrevio->pkPeriod = $arrayHeadingInfoIata['referencePeriod'];
                $objDocumentPrevio->pkConciliation = $lastPkInsertedConciliation;
                $objDocumentPrevio->save();

                $objDocumentAnalisis = new Document();
                $objDocumentAnalisis->originalName = 'Conciliacion_'.$arrayHeadingInfoIata['referenceIata'].'.xlsx';
                $objDocumentAnalisis->diskName = 'Conciliacion_'.$uuid.'.xlsx';
                $objDocumentAnalisis->iata = $arrayHeadingInfoIata['referenceIata'];
                $objDocumentAnalisis->id = auth()->id();
                $objDocumentAnalisis->pkDocumentType = 4;
                $objDocumentAnalisis->pkPeriod = $arrayHeadingInfoIata['referencePeriod'];
                $objDocumentAnalisis->pkConciliation = $lastPkInsertedConciliation;
                $objDocumentAnalisis->save();

                $objDocumentResumen = new Document();
                $objDocumentResumen->originalName = 'Resumen_'.$arrayHeadingInfoIata['referenceIata'].'.xlsx';
                $objDocumentResumen->diskName = 'Resumen_'.$uuid.'.xlsx';
                $objDocumentResumen->iata = $arrayHeadingInfoIata['referenceIata'];
                $objDocumentResumen->id = auth()->id();
                $objDocumentResumen->pkDocumentType = 3;
                $objDocumentResumen->pkPeriod = $arrayHeadingInfoIata['referencePeriod'];
                $objDocumentResumen->pkConciliation = $lastPkInsertedConciliation;
                $objDocumentResumen->save();

                DB::commit();
            } catch (exception $e) {
                DB::rollBack();
                $data['success'] = 0;
                $data['error'] = $e->getMessage();
                return response()->json($data);
            }
            
            $dataContentTable = '';
            $dataContentTable = $this->addRowDataContentTable($dataContentTable,$objDocumentIata);
            $dataContentTable = $this->addRowDataContentTable($dataContentTable,$objDocumentPrevio);
            $dataContentTable = $this->addRowDataContentTable($dataContentTable,$objDocumentAnalisis);
            $dataContentTable = $this->addRowDataContentTable($dataContentTable,$objDocumentResumen);

            $dataContentHeader = '';
            $dataContentHeader = $this->addContentHeader($dataContentHeader,$resultadosReportePrevioFueraPeriodo, $resultadosDomestico, $resultadosInternacional, $valorDiferencias, $valorReportePrevio, $grandTotal);
            
            $data['dataContentHeader'] = $dataContentHeader;
            $data['dataContentTable'] = $dataContentTable;
            $data['success'] = 1;
        }

        return response()->json($data);
    }

    private function analysisTktt($arrayTicketIata, $arrayResults, $dataArrayPrevio)
    {
        
        $keyPosition = 0;
        $idBoletoIata = $arrayTicketIata[2];
        foreach ($arrayTicketIata as $key => $value) {
            if ($value == 'CC' || $value == 'CA') {

                $valorTransaccion = $arrayTicketIata[$keyPosition + 1];
                $valorTarifa = $arrayTicketIata[$keyPosition + 2];
                $impuesto = $arrayTicketIata[$keyPosition + 3];
                $tasasCargos = $arrayTicketIata[$keyPosition + 4];
                $cobl = $arrayTicketIata[$keyPosition + 6];
                $porcentajeComisionStd = $arrayTicketIata[$keyPosition + 7];
                $valorComisionStd = $arrayTicketIata[$keyPosition + 8];
                $porcentajeComisionSupp = $arrayTicketIata[$keyPosition + 9];
                if ($porcentajeComisionSupp === NULL) {
                    $keyPosition++;
                }
                $valorComisionSupp = $arrayTicketIata[$keyPosition + 10];
                $impSobreComision = $arrayTicketIata[$keyPosition + 11];

                $lastElement = endKey($arrayTicketIata);
                $indiceSeg = $lastElement;
                $netoPagar = NULL;

                while ($netoPagar === NULL) {
                    $netoPagar = $arrayTicketIata[$indiceSeg];
                    $indiceSeg--;
                }
                #Buscamos boleto en  archivo BSP y nos posicionamos sobre ese renglón
                $arrayBoletoPrevio = searchThroughArray($idBoletoIata, $dataArrayPrevio);

                /*********** START VALIDACION FUERA DE PERIODO ***********/
                
                if ($arrayBoletoPrevio == NULL) {

                    $arrayFueraPeriodoResult = [];
                    //Se llena array con resultados
                    $arrayFueraPeriodoResult['L.A.'] = $arrayTicketIata[0];
                    $arrayTicketIata[3] == NULL ? $arrayFueraPeriodoResult['Fecha'] = $arrayTicketIata[4] : $arrayFueraPeriodoResult['Fecha'] = $arrayTicketIata[3];
                    $arrayFueraPeriodoResult['Concepto'] = 'BOLETO FUERA DE PERIODO';
                    $arrayFueraPeriodoResult['Boleto'] = $idBoletoIata;
                    $arrayFueraPeriodoResult['Total'] = ROUND($netoPagar, 2);

                    array_push($arrayResults, $arrayFueraPeriodoResult);
                    /*********** END VALIDACION FUERA DE PERIODO ***********/
                } else {
                    //Obtiene el renglón sobre el que se encuentra cada boleto y se cuenta la cantidad de estos
                    $indiceBoletoPrevio = array_keys($arrayBoletoPrevio);
                    $cantidadDeBoletosPrevio = sizeof($arrayBoletoPrevio);

                    $tarifaNetoPagarPrevio = ROUND($arrayBoletoPrevio[$indiceBoletoPrevio[0]]['netoPagar'],2);
                    $tarifaContadoPrevio = $arrayBoletoPrevio[$indiceBoletoPrevio[0]]['contado'];
                    $tarifaCreditoPrevio = $arrayBoletoPrevio[$indiceBoletoPrevio[0]]['credito'];
                    $ivaBsp = $arrayBoletoPrevio[$indiceBoletoPrevio[0]]['iva'];
                    $tuaBsp = $arrayBoletoPrevio[$indiceBoletoPrevio[0]]['tua'];
                    $porcentajeComisionPrevio = $arrayBoletoPrevio[$indiceBoletoPrevio[0]]['porcentajeComision'];
                    $comisionPrevio = $arrayBoletoPrevio[$indiceBoletoPrevio[0]]['comision'];
                    /*********** START VALIDACION BOLETO REPETIDO ***********/
                    if ($cantidadDeBoletosPrevio > 1) {
                        $contadorBoletoRep = 0;
                        for ($j = 0; $j < $cantidadDeBoletosPrevio; $j++) {
                        
                            $tarifaNetoPagarPrevio = $arrayBoletoPrevio[$indiceBoletoPrevio[$j]]['netoPagar'];
                            $tarifaContadoPrevio = $arrayBoletoPrevio[$indiceBoletoPrevio[$j]]['contado'];
                            $tarifaCreditoPrevio = $arrayBoletoPrevio[$indiceBoletoPrevio[$j]]['credito'];
                            $porcentajeComisionPrevio = $arrayBoletoPrevio[$indiceBoletoPrevio[$j]]['porcentajeComision'];
                            $comisionPrevio = $arrayBoletoPrevio[$indiceBoletoPrevio[$j]]['comision'];
                            $ivaBsp = $arrayBoletoPrevio[$indiceBoletoPrevio[$j]]['iva'];
                            $tuaBsp = $arrayBoletoPrevio[$indiceBoletoPrevio[$j]]['tua'];

                            $description = 'BOLETO REPETIDO EN BACK OFFICE';
                            if ($tarifaContadoPrevio != 0 || $tarifaCreditoPrevio != 0 ) {
                                if ($value == 'CC' && $tarifaContadoPrevio != 0 && $tarifaCreditoPrevio == 0) {
                                    $description = 'BOLETO PAGADO DE CREDITO';
                                } else if($value == 'CA' && $tarifaContadoPrevio == 0 && $tarifaCreditoPrevio != 0) {
                                    $description = 'BOLETO PAGADO DE CONTADO';
                                }else if ((($valorComisionStd != NULL && $valorComisionStd > 0)  || ($valorComisionSupp != NULL && $valorComisionSupp > 0))
                                && ($porcentajeComisionPrevio == 0 && $comisionPrevio == 0)) {
                                    $description = 'COMISION NO REGISTRADA POR LA AGENCIA';
                                }else if (($valorComisionStd == 0  &&  $valorComisionSupp == 0) && ($porcentajeComisionPrevio > 0 && $comisionPrevio > 0)) {
                                    $description = 'COMISION REGISTRADA DE MAS POR LA AGENCIA';
                                }else if ($tarifaNetoPagarPrevio == 0 && $tarifaContadoPrevio == 0 && $tarifaCreditoPrevio == 0 && $ivaBsp == 0 && $tuaBsp == 0) {
                                    $description = 'FACTURADO COMO VIVO';
                                }
                            }
                            
                            //Evaluamos diferencia en el primer registro de los repetidos, el resto los restamos directamente ya que nunca debieron ser considerados.
                            if ($contadorBoletoRep == 0) {

                                $diferenciaFinal =  $netoPagar - $tarifaNetoPagarPrevio;
                                //INICIO Se llena array con resultados
                                $arrayBoletoRepetidoResult = [];
                                //Se llena array con resultados
                                $arrayBoletoRepetidoResult['L.A.'] = $arrayTicketIata[0];
                                $arrayTicketIata[3] == NULL ? $arrayBoletoRepetidoResult['Fecha'] = $arrayTicketIata[4] : $arrayBoletoRepetidoResult['Fecha'] = $arrayTicketIata[3];
                                $arrayBoletoRepetidoResult['Concepto'] = $description;
                                $arrayBoletoRepetidoResult['Boleto'] = $idBoletoIata;
                                $arrayBoletoRepetidoResult['Total'] = ROUND($diferenciaFinal, 2);

                                array_push($arrayResults, $arrayBoletoRepetidoResult);
                                //FIN Se llena array con resultados
                                $contadorBoletoRep++;
                            } else {

                                //INICIO Se llena array con resultados
                                $arrayBoletoRepetidoResult = [];
                                //Se llena array con resultados
                                $arrayBoletoRepetidoResult['L.A.'] = $arrayTicketIata[0];
                                $arrayTicketIata[3] == NULL ? $arrayBoletoRepetidoResult['Fecha'] = $arrayTicketIata[4] : $arrayBoletoRepetidoResult['Fecha'] = $arrayTicketIata[3];
                                $arrayBoletoRepetidoResult['Concepto'] = $description;
                                $arrayBoletoRepetidoResult['Boleto'] = $idBoletoIata;
                                $arrayBoletoRepetidoResult['Total'] = ROUND(-$tarifaNetoPagarPrevio, 2);

                                array_push($arrayResults, $arrayBoletoRepetidoResult);
                                //FIN Se llena array con resultados
                                $contadorBoletoRep++;
                            }
                        }
                        /*********** END VALIDACION BOLETO REPETIDO ***********/
                    } else {

                        /*********** START BOLETO PAGADO A CRÉDITO ***********/
                        if ($value == 'CC' && $tarifaContadoPrevio != 0 && $tarifaCreditoPrevio == 0) {

                            $diferenciaFinal =  $netoPagar - $tarifaNetoPagarPrevio;
                            //INICIO Se llena array con resultados
                            $arrayBoletoPagadoCreditoResult = [];
                            //Se llena array con resultados
                            $arrayBoletoPagadoCreditoResult['L.A.'] = $arrayTicketIata[0];
                            $arrayTicketIata[3] == NULL ? $arrayBoletoPagadoCreditoResult['Fecha'] = $arrayTicketIata[4] : $arrayBoletoPagadoCreditoResult['Fecha'] = $arrayTicketIata[3];
                            $arrayBoletoPagadoCreditoResult['Concepto'] = 'BOLETO PAGADO DE CREDITO';
                            $arrayBoletoPagadoCreditoResult['Boleto'] = $idBoletoIata;
                            $arrayBoletoPagadoCreditoResult['Total'] = ROUND($diferenciaFinal, 2);

                            array_push($arrayResults, $arrayBoletoPagadoCreditoResult);
                            //FIN Se llena array con resultados

                            /*********** END BOLETO PAGADO A CRÉDITO ***********/

                            /*********** START BOLETO PAGADO DE CONTADO ***********/
                        } else if ($value == 'CA' && $tarifaContadoPrevio == 0 && $tarifaCreditoPrevio != 0) {

                            $diferenciaFinal =  $netoPagar - $tarifaNetoPagarPrevio;
                            //INICIO Se llena array con resultados
                            $arrayBoletoPagadoContadoResult = [];
                            //Se llena array con resultados
                            $arrayBoletoPagadoContadoResult['L.A.'] = $arrayTicketIata[0];
                            $arrayTicketIata[3] == NULL ? $arrayBoletoPagadoContadoResult['Fecha'] = $arrayTicketIata[4] : $arrayBoletoPagadoContadoResult['Fecha'] = $arrayTicketIata[3];
                            $arrayBoletoPagadoContadoResult['Concepto'] = 'BOLETO PAGADO DE CONTADO';
                            $arrayBoletoPagadoContadoResult['Boleto'] = $idBoletoIata;
                            $arrayBoletoPagadoContadoResult['Total'] = ROUND($diferenciaFinal, 2);

                            array_push($arrayResults, $arrayBoletoPagadoContadoResult);
                            //FIN Se llena array con resultados

                            /*********** END BOLETO PAGADO DE CONTADO ***********/

                            /*********** START VALIDACION COMISION NO REGISTRADA POR LA AG ***********/
                        } else if ((($valorComisionStd != NULL && $valorComisionStd > 0)  || ($valorComisionSupp != NULL && $valorComisionSupp > 0))
                            && ($porcentajeComisionPrevio == 0 && $comisionPrevio == 0)
                        ) {

                            $diferenciaFinal =  $netoPagar - $tarifaNetoPagarPrevio;
                            //INICIO Se llena array con resultados
                            $arrayBoletoComisionNoRegistradaResult = [];
                            //Se llena array con resultados
                            $arrayBoletoComisionNoRegistradaResult['L.A.'] = $arrayTicketIata[0];
                            $arrayTicketIata[3] == NULL ? $arrayBoletoComisionNoRegistradaResult['Fecha'] = $arrayTicketIata[4] : $arrayBoletoComisionNoRegistradaResult['Fecha'] = $arrayTicketIata[3];
                            $arrayBoletoComisionNoRegistradaResult['Concepto'] = 'COMISION NO REGISTRADA POR LA AGENCIA';
                            $arrayBoletoComisionNoRegistradaResult['Boleto'] = $idBoletoIata;
                            $arrayBoletoComisionNoRegistradaResult['Total'] = ROUND($diferenciaFinal, 2);

                            array_push($arrayResults, $arrayBoletoComisionNoRegistradaResult);
                            //FIN Se llena array con resultados
                            /*********** END VALIDACION COMISION NO REGISTRADA POR LA AG ***********/

                            /*********** START VALIDACION COMISION REGISTRADA DE MAS ***********/
                        } else if (($valorComisionStd == 0  &&  $valorComisionSupp == 0) && ($porcentajeComisionPrevio > 0 && $comisionPrevio > 0)) {

                            $diferenciaFinal =  $netoPagar - $tarifaNetoPagarPrevio;
                            //INICIO Se llena array con resultados
                            $arrayBoletoComisionNoRegistradaResult = [];
                            //Se llena array con resultados
                            $arrayBoletoComisionNoRegistradaResult['L.A.'] = $arrayTicketIata[0];
                            $arrayTicketIata[3] == NULL ? $arrayBoletoComisionNoRegistradaResult['Fecha'] = $arrayTicketIata[4] : $arrayBoletoComisionNoRegistradaResult['Fecha'] = $arrayTicketIata[3];
                            $arrayBoletoComisionNoRegistradaResult['Concepto'] = 'COMISION REGISTRADA DE MAS POR LA AGENCIA';
                            $arrayBoletoComisionNoRegistradaResult['Boleto'] = $idBoletoIata;
                            $arrayBoletoComisionNoRegistradaResult['Total'] = ROUND($diferenciaFinal, 2);

                            array_push($arrayResults, $arrayBoletoComisionNoRegistradaResult);
                            //FIN Se llena array con resultados

                            /*********** END VALIDACION COMISION REGISTRADA DE MAS ***********/

                            /*********** START VALIDACION FACTURADO COMO VIVO ***********/
                        } else if ($tarifaNetoPagarPrevio == 0 && $tarifaContadoPrevio == 0 && $tarifaCreditoPrevio == 0 && $ivaBsp == 0 && $tuaBsp == 0) {

                            $diferenciaFinal =  $netoPagar - $tarifaNetoPagarPrevio;
                            //INICIO Se llena array con resultados
                            $arrayBoletoFacturadoVivoResult = [];
                            //Se llena array con resultados
                            $arrayBoletoFacturadoVivoResult['L.A.'] = $arrayTicketIata[0];
                            $arrayTicketIata[3] == NULL ? $arrayBoletoFacturadoVivoResult['Fecha'] = $arrayTicketIata[4] : $arrayBoletoFacturadoVivoResult['Fecha'] = $arrayTicketIata[3];
                            $arrayBoletoFacturadoVivoResult['Concepto'] = 'FACTURADO COMO VIVO';
                            $arrayBoletoFacturadoVivoResult['Boleto'] = $idBoletoIata;
                            $arrayBoletoFacturadoVivoResult['Total'] = ROUND($diferenciaFinal, 2);

                            array_push($arrayResults, $arrayBoletoFacturadoVivoResult);
                            //FIN Se llena array con resultados

                            /*********** END VALIDACION FACTURADO COMO VIVO ***********/

                            /*********** STAR VALIDACION NO CLASCIFICADA  ***********/
                        } else if ($tarifaNetoPagarPrevio != $netoPagar) {
                            
                            $diferenciaFinal =  $netoPagar - $tarifaNetoPagarPrevio;
                            //INICIO Se llena array con resultados
                            $arrayDiferenciaNoClascificadaResult = [];
                            //Se llena array con resultados
                            $arrayDiferenciaNoClascificadaResult['L.A.'] = $arrayTicketIata[0];
                            $arrayTicketIata[3] == NULL ? $arrayDiferenciaNoClascificadaResult['Fecha'] = $arrayTicketIata[4] : $arrayDiferenciaNoClascificadaResult['Fecha'] = $arrayTicketIata[3];
                            $arrayDiferenciaNoClascificadaResult['Concepto'] = 'DIFERENCIA NO CLASCIFICADA';
                            $arrayDiferenciaNoClascificadaResult['Boleto'] = $idBoletoIata;
                            $arrayDiferenciaNoClascificadaResult['Total'] = ROUND($diferenciaFinal, 2);

                            array_push($arrayResults, $arrayDiferenciaNoClascificadaResult);
                            //FIN Se llena array con resultados

                        }
                        /*********** END VALIDACION NO CLASCIFICADA  ***********/
                    }
                }
            } else {
                $keyPosition++;
            }
        }

        return $arrayResults;
    }

    private function analysisCanx($arrayTicketIata, $arrayResults, $dataArrayPrevio)
    {
        $idBoletoIata = $arrayTicketIata[2];
        #Buscamos boleto en  archivo BSP y nos posicionamos sobre ese renglón
        $arrayBoletoPrevio = searchThroughArray($idBoletoIata, $dataArrayPrevio);
        $lastElement = endKey($arrayTicketIata);
        $indiceSeg = $lastElement;
        $netoPagar = NULL;

        while ($netoPagar === NULL) {
            $netoPagar = $arrayTicketIata[$indiceSeg];
            $indiceSeg--;
        }
        if ($arrayBoletoPrevio != NULL) {
            //Obtiene el renglón sobre el que se encuentra el boleto
            $indiceBoletoPrevio = array_keys($arrayBoletoPrevio);
            $tarifaNetoPagarPrevio = $arrayBoletoPrevio[$indiceBoletoPrevio[0]]['netoPagar'];
            $tarifaContadoPrevio = $arrayBoletoPrevio[$indiceBoletoPrevio[0]]['contado'];
            if ($arrayBoletoPrevio != NULL && ($tarifaNetoPagarPrevio != 0 && $tarifaContadoPrevio != 0)) {

                $diferenciaFinal =  $netoPagar - $tarifaNetoPagarPrevio;
                //INICIO Se llena array con resultados
                $arrayBoletoFacturadoCanceladoResult = [];
                //Se llena array con resultados
                $arrayBoletoFacturadoCanceladoResult['L.A.'] = $arrayTicketIata[0];
                $arrayTicketIata[3] == NULL ? $arrayBoletoFacturadoCanceladoResult['Fecha'] = $arrayTicketIata[4] : $arrayBoletoFacturadoCanceladoResult['Fecha'] = $arrayTicketIata[3];
                $arrayBoletoFacturadoCanceladoResult['Concepto'] = 'FACTURADO COMO CANCELADO';
                $arrayBoletoFacturadoCanceladoResult['Boleto'] = $idBoletoIata;
                $arrayBoletoFacturadoCanceladoResult['Total'] = ROUND($diferenciaFinal, 2);
    
                array_push($arrayResults, $arrayBoletoFacturadoCanceladoResult);
                //FIN Se llena array con resultados
            }
        }

        

        return $arrayResults;
    }

    private function analysisRfnd($arrayTicketIata, $arrayResults, $dataArrayPrevio)
    {
        $idBoletoIata = $arrayTicketIata[2];
        $lastElement = endKey($arrayTicketIata);
        $indiceSeg = $lastElement;
        $netoPagar = NULL;

        while ($netoPagar === NULL) {
            $netoPagar = $arrayTicketIata[$indiceSeg];
            $indiceSeg--;
        }

        //INICIO Se llena array con resultados
        $arrayBoletoReembolsoResult = [];
        //Se llena array con resultados
        $arrayBoletoReembolsoResult['L.A.'] = $arrayTicketIata[0];
        $arrayTicketIata[3] == NULL ? $arrayBoletoReembolsoResult['Fecha'] = $arrayTicketIata[4] : $arrayBoletoReembolsoResult['Fecha'] = $arrayTicketIata[3];
        $arrayBoletoReembolsoResult['Concepto'] = 'REEMBOLSO';
        $arrayBoletoReembolsoResult['Boleto'] = $idBoletoIata;
        $arrayBoletoReembolsoResult['Total'] = ROUND($netoPagar, 2);

        array_push($arrayResults, $arrayBoletoReembolsoResult);
        //FIN Se llena array con resultados

        return $arrayResults;
    }

    private function analysisAdma($arrayTicketIata, $arrayResults, $dataArrayPrevio)
    {
        $idBoletoIata = $arrayTicketIata[2];
        $lastElement = endKey($arrayTicketIata);
        $indiceSeg = $lastElement;
        $netoPagar = NULL;

        while ($netoPagar === NULL) {
            $netoPagar = $arrayTicketIata[$indiceSeg];
            $indiceSeg--;
        }

        //INICIO Se llena array con resultados
        $arrayBoletoDebitoResult = [];
        //Se llena array con resultados
        $arrayBoletoDebitoResult['L.A.'] = $arrayTicketIata[0];
        $arrayTicketIata[3] == NULL ? $arrayBoletoDebitoResult['Fecha'] = $arrayTicketIata[4] : $arrayBoletoDebitoResult['Fecha'] = $arrayTicketIata[3];
        $arrayBoletoDebitoResult['Concepto'] = 'ADM';
        $arrayBoletoDebitoResult['Boleto'] = $idBoletoIata;
        $arrayBoletoDebitoResult['Total'] = ROUND($netoPagar, 2);

        array_push($arrayResults, $arrayBoletoDebitoResult);
        //FIN Se llena array con resultados

        return $arrayResults;
    }

    private function analysisAcma($arrayTicketIata, $arrayResults, $dataArrayPrevio)
    {
        $idBoletoIata = $arrayTicketIata[2];
        $lastElement = endKey($arrayTicketIata);
        $indiceSeg = $lastElement;
        $netoPagar = NULL;

        while ($netoPagar === NULL) {
            $netoPagar = $arrayTicketIata[$indiceSeg];
            $indiceSeg--;
        }

        //INICIO Se llena array con resultados
        $arrayBoletoCreditoResult = [];
        //Se llena array con resultados
        $arrayBoletoCreditoResult['L.A.'] = $arrayTicketIata[0];
        $arrayTicketIata[3] == NULL ? $arrayBoletoCreditoResult['Fecha'] = $arrayTicketIata[4] : $arrayBoletoCreditoResult['Fecha'] = $arrayTicketIata[3];
        $arrayBoletoCreditoResult['Concepto'] = 'ACM';
        $arrayBoletoCreditoResult['Boleto'] = $idBoletoIata;
        $arrayBoletoCreditoResult['Total'] = ROUND($netoPagar, 2);

        array_push($arrayResults, $arrayBoletoCreditoResult);
        //FIN Se llena array con resultados

        return $arrayResults;
    }

    private function analysisSpdr($arrayTicketIata, $arrayResults, $dataArrayPrevio)
    {
        $idBoletoIata = $arrayTicketIata[2];
        $lastElement = endKey($arrayTicketIata);
        $indiceSeg = $lastElement;
        $netoPagar = NULL;

        while ($netoPagar === NULL) {
            $netoPagar = $arrayTicketIata[$indiceSeg];
            $indiceSeg--;
        }

        //INICIO Se llena array con resultados
        $arrayCuotaMensualResult = [];
        //Se llena array con resultados
        $arrayCuotaMensualResult['L.A.'] = $arrayTicketIata[0];
        $arrayTicketIata[3] == NULL ? $arrayCuotaMensualResult['Fecha'] = $arrayTicketIata[4] : $arrayCuotaMensualResult['Fecha'] = $arrayTicketIata[3];
        $arrayCuotaMensualResult['Concepto'] = 'CUOTA MENSUAL BSP';
        $arrayCuotaMensualResult['Boleto'] = $idBoletoIata;
        $arrayCuotaMensualResult['Total'] = ROUND($netoPagar, 2);

        array_push($arrayResults, $arrayCuotaMensualResult);
        //FIN Se llena array con resultados

        return $arrayResults;
    }

    public function getHeadingInfoDocIata($dataArrayIata)
    {
        $data = [];
        //Realizamos búsqueda para obtener el renglón de los datos con periodo y IATA
        $arrayReferenceIata = searchThroughArray('Billing Period', $dataArrayIata);

        $stringArrayReferenceIata = $arrayReferenceIata[1][2];
        $stringArrayDescription = $arrayReferenceIata[1][0];

        $stringArrayReferenceIataP1 = explode(':', $stringArrayReferenceIata);
        $stringArrayReferenceIataP2 = explode('-', $stringArrayReferenceIataP1[1]);
        $stringArrayDescriptionP1 = explode('(', $stringArrayDescription);
        $stringArrayDescriptionP2 = explode(')', $stringArrayDescriptionP1[1]);
        //DELETE SPACES
        $referenceIata = $stringArrayReferenceIataP2[0];
        $referenceIata = preg_replace('/\s+/', '', $referenceIata);
        $referencePeriod = $stringArrayReferenceIataP2[1];
        $referencePeriod = preg_replace('/\s+/', '', $referencePeriod);
        $descriptionPeriod = $stringArrayDescriptionP2[0];

        $pkPeriod = Period::where('reference', $referencePeriod)->pluck('pkPeriod')->all();
        if ($pkPeriod) {

            $pkPeriod = $pkPeriod[0];
        } else {

            $objPeriod = new Period();
            $objPeriod->reference = $referencePeriod;
            $objPeriod->description = $descriptionPeriod;
            $objPeriod->save();
            $pkPeriod = $objPeriod->pkPeriod;
        }
        $data['referenceIata'] = $referenceIata;
        $data['referencePeriod'] = $pkPeriod;
        $data['descriptionPeriod'] = $descriptionPeriod;

        return $data;
    }

    public function addRowDataContentTable($dataContentTable,$objDocument)
    {
        $url = url('/iata/' . $objDocument->diskName . '/downloadFromPeriod');
            $dataContentTable .=
                        "<tr>
                    <input type='hidden' name='originalName[]'  value='{$objDocument->originalName}' class='inputForm'>
                    <input type='hidden' name='diskName[]'  value='{$objDocument->diskName}'>
                    <input type='hidden' name='iata[]'  value='{$objDocument->iata}'>
                    <input type='hidden' name='referencePeriod[]'  value='{$objDocument->period->reference}'>

                    <td scope='row' style = 'white-space: nowrap;'>{$objDocument->originalName}</td>
                    <td scope='row' style = 'white-space: nowrap;'><b>{$objDocument->iata}</b>
                    </td>
                    <td scope='row' style = 'white-space: nowrap;'>{$objDocument->period->description}
                    </td>
                    <td class='text-center'>
                        <a href='{$url}'
                            class='btn btn-primary btn-icon-only'>
                            <span class='btn-inner--icon'><i class='ni ni-folder-17'></i></span>
                        </a>
                    </td>
                    </td></tr>";

             return $dataContentTable;        
    }

    public function addContentHeader($dataContentHeader,$resultadosReportePrevioFueraPeriodo, $resultadosDomestico, $resultadosInternacional, $valorDiferencias, $valorReportePrevio, $grandTotal)
    {
            $totalResultadosPrevio = count($resultadosReportePrevioFueraPeriodo);
            $totalResultadosDomestico = count($resultadosDomestico);
            $totalResultadosInternacional = count($resultadosInternacional);
            $totalRegistros = $totalResultadosPrevio+$totalResultadosDomestico+$totalResultadosInternacional; 
            $baseMargen = $valorReportePrevio+$valorDiferencias;
            $variabilidad = ROUND(($baseMargen/$grandTotal)*100,2);
            $dataContentHeader .=
                    "
                <div class='row'>
                    <div class='col-xl-4 col-lg-6'>
                        <div class='card card-stats mb-4 mb-xl-0 bg-default'>
                            <div class='card-body'>
                                <div class='row'>
                                    <div class='col'>
                                        <h5 class='card-title text-uppercase text-muted text-white mb-0'>Dif. Encontradas</h5>
                                        <span class='h2 font-weight-bold text-white mb-0'>".$totalRegistros."</span>
                                    </div>
                                    <div class='col-auto'>
                                        <div class='icon icon-shape bg-warning text-white rounded-circle shadow'>
                                            <i class='fas fa-tasks'></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='col-xl-4 col-lg-6'>
                        <div class='card card-stats mb-4 mb-xl-0 bg-default'>
                            <div class='card-body'>
                                <div class='row'>
                                    <div class='col'>
                                        <h5 class='card-title text-uppercase text-muted text-white mb-0'>Valor Diferencias</h5>
                                        <span class='h2 font-weight-bold text-white mb-0'>".$valorDiferencias."</span>
                                    </div>
                                    <div class='col-auto'>
                                        <div class='icon icon-shape bg-success text-white rounded-circle shadow'>
                                            <i class='fas fa-money-bill'></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='col-xl-4 col-lg-6'>
                        <div class='card card-stats mb-4 mb-xl-0 bg-default'>
                            <div class='card-body'>
                                <div class='row'>
                                    <div class='col'>
                                        <h5 class='card-title text-uppercase text-muted text-white mb-0'>Variabilidad</h5>
                                        <span class='h2 font-weight-bold text-white mb-0'>".$variabilidad."%</span>
                                    </div>
                                    <div class='col-auto'>
                                        <div class='icon icon-shape bg-info text-white rounded-circle shadow'>
                                            <i class='fas fa-percent'></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>";

             return $dataContentHeader;        
    }

    private function getAllTicketsBsp($arrayTicketIata, $arrayAllTicketsBsp, $type)
    {
        
        $keyPosition = 0;
        $idBoletoIata = $arrayTicketIata[2];
        $tipoBoleto = $arrayTicketIata[1];
        foreach ($arrayTicketIata as $key => $value) {
            if ($value == 'CC' || $value == 'CA') {

                $valorTransaccion = $arrayTicketIata[$keyPosition + 1];
                $valorTarifa = $arrayTicketIata[$keyPosition + 2];
                $impuesto = $arrayTicketIata[$keyPosition + 3];
                $tasasCargos = $arrayTicketIata[$keyPosition + 4];
                $cobl = $arrayTicketIata[$keyPosition + 6];
                $porcentajeComisionStd = $arrayTicketIata[$keyPosition + 7];
                $valorComisionStd = $arrayTicketIata[$keyPosition + 8];
                $porcentajeComisionSupp = $arrayTicketIata[$keyPosition + 9];
                if ($porcentajeComisionSupp === NULL) {
                    $keyPosition++;
                }
                $valorComisionSupp = $arrayTicketIata[$keyPosition + 10];
                $impSobreComision = $arrayTicketIata[$keyPosition + 11];

                $lastElement = endKey($arrayTicketIata);
                $indiceSeg = $lastElement;
                $netoPagar = NULL;

                while ($netoPagar === NULL) {
                    $netoPagar = $arrayTicketIata[$indiceSeg];
                    $indiceSeg--;
                }
                $arrayResult = [];
                //Se llena array con resultados
                $arrayResult['L.A.'] = $arrayTicketIata[0];
                $arrayTicketIata[3] == NULL ? $arrayResult['Fecha'] = $arrayTicketIata[4] : $arrayResult['Fecha'] = $arrayTicketIata[3];
                $arrayResult['Boleto'] = $idBoletoIata;
                $arrayResult['TipoAlcance'] = $type;
                $arrayResult['TipoBoleto'] = $tipoBoleto;
                $arrayResult['CACC'] = $value;
                $arrayResult['Tarifa'] = $valorTarifa;
                $arrayResult['Impuesto'] = $impuesto;
                $arrayResult['ValorComisionStd'] = $valorComisionStd;
                $arrayResult['ValorComisionSup'] = $valorComisionSupp;
                $arrayResult['ImpuestoComision'] = $impSobreComision;
                $arrayResult['TasasCargos'] = $tasasCargos;
                $arrayResult['Total'] = ROUND($netoPagar, 2);

                array_push($arrayAllTicketsBsp, $arrayResult);

                
            } else {
                $keyPosition++;
            }
        }

        return $arrayAllTicketsBsp;
    }

    public function validateRelationDocuments($dataArrayPrevio, $iataReference)
    {
        //Comparativos de claves según IATA
        $keyOption  = '';

        if ($iataReference == '86515984') {
            $arrayOptionKey = ['DEMEX','DMX','MEXFE','MXF'];
            
            foreach ($arrayOptionKey as $key => $value) {
                $arrayOptionKeyFound = searchThroughArray($value, $dataArrayPrevio);
                if (!empty($arrayOptionKeyFound)) {
                    $keyOption = $value;
                    break;
                }
            }
        }else if($iataReference == '86502194'){
            $arrayOptionKey = ['DEGDL','GDLFE'];
            
            foreach ($arrayOptionKey as $key => $value) {
                $arrayOptionKeyFound = searchThroughArray($value, $dataArrayPrevio);
                if (!empty($arrayOptionKeyFound)) {
                    $keyOption = $value;
                    break;
                }
            }
        }else if($iataReference == '86511574'){
            $arrayOptionKey = ['DEMTY','MTYFE','IMON','DIMO'];
            
            foreach ($arrayOptionKey as $key => $value) {
                $arrayOptionKeyFound = searchThroughArray($value, $dataArrayPrevio);
                if (!empty($arrayOptionKeyFound)) {
                    $keyOption = $value;
                    break;
                }
            }
        }else if($iataReference == '86515973'){
            $arrayOptionKey = ['DESAN','SANFE','DISA','ISAN'];
            
            foreach ($arrayOptionKey as $key => $value) {
                $arrayOptionKeyFound = searchThroughArray($value, $dataArrayPrevio);
                if (!empty($arrayOptionKeyFound)) {
                    $keyOption = $value;
                    break;
                }
            }
        }

        return $keyOption;

        
        
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Conciliation $conciliation)
    {
        $pkConciliation = $conciliation->pkConciliation;
    
        try {
            DB::beginTransaction();
            //GET ALL DOCUMENTS ASOCIATED WITH THE CONCILIATION
            $objDocumentsAsignedToConciliation = Document::join('conciliations', 'documents.pkConciliation', '=', 'conciliations.pkConciliation')
            ->where('documents.pkConciliation', $pkConciliation)
            ->select([
                'documents.pkDocument',
                'documents.originalName',
                'documents.diskName',
                'conciliations.pkConciliation',
            ])
            ->get()
            ->toArray();

            foreach ($objDocumentsAsignedToConciliation as $key => $document) {
                DB::table('documents')->where('pkConciliation', $pkConciliation)->delete();
                Storage::disk('documents')->delete('/', $document['diskName']);
            }   

            $conciliation->delete();
        
            DB::commit();
        } catch (exception $e) {
            DB::rollback();
            return redirect()->route('conciliation.index')->with('notificationDanger', 'Ha ocurrido un error al intentar eliminar el registro ' . $pkConciliation . '!');
        }
        return redirect()->route('conciliation.index')->with('notification', 'El registro ' . $pkConciliation . ' se ha eliminado correctamente');
    }
}
