@extends('layouts.app')

@section('styles')
    <link href="{{ asset('assets') }}/vendor/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="{{ asset('assets') }}/vendor/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet">
    <link href="{{ asset('assets') }}/vendor/datatables.net-select-bs4/css/select.bootstrap4.min.css" rel="stylesheet">
    <link href="{{ asset('assets') }}/vendor/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="{{ asset('assets') }}/vendor/dropzone/dist/min/dropzone.min.css" rel="stylesheet">
@endsection

@section('title', 'CONCILIACIONES')
@section('content')
    <div class="header bg-gradient-primary pb-7 pt-5 pt-md-8">
    </div>
    <div class="container-fluid mt--7">
        @if (session('notification'))
            <div class="alert alert-success alert-dismissible fade show">
                <span class="alert-inner--icon"><i class="ni ni-like-2"></i></span>
                {{ session('notification') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif
        @if (session('notificationDanger'))
            <div class="alert alert-danger alert-dismissible fade show">
                <span class="alert-inner--icon"><i class="fa fa-info-circle"></i></span>
                {{ session('notificationDanger') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif
        <div class="card shadow">
            <div class="card-header border-0 ">
                <div class="row align-items-end" >
                        <i class="fas fa-university fa-2x"></i>
                        <h3 class="mb-0 ml-2">Detalle de conciliación folio: {{$conciliation->pkConciliation}} </h3>
                </div>
                <hr>
            </div>
            <div class="card-body">
            <div class="header-body" id="header-body" >
            <!-- Card stats -->
            <div class='row'>
                <div class='col-xl-4 col-lg-6'>
                    <div class='card card-stats mb-4 mb-xl-0 bg-default'>
                        <div class='card-body'>
                            <div class='row'>
                                <div class='col'>
                                    <h5 class='card-title text-uppercase text-muted text-white mb-0'>Valor factura BSP</h5>
                                    <span class='h2 font-weight-bold text-white mb-0'>{{$conciliation->valueInvoiceBsp}}</span>
                                </div>
                                <div class='col-auto'>
                                    <div class='icon icon-shape bg-primary text-white rounded-circle shadow'>
                                        <i class='fas fa-file-excel'></i>
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
                                    <span class='h2 font-weight-bold text-white mb-0'>{{$conciliation->valueDiferences}}</span>
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
                                    <span class='h2 font-weight-bold text-white mb-0'>{{$variabilidad}}%</span>
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
            </div>
            </div>
                <form id="form-documents" action="#" method="post">
                @csrf
                    <div class="table-responsive mt-5"" id="table-documentsToAdd" >
                        <h3 class="ml-2">Documentos relacionados</h3>
                        <!-- Projects table -->
                        <table class="table align-items-center table-flush table-hover" id="datatable-new-document">

                            <thead class="thead-light">
                                <tr>
                                    <th scope="col">Nombre documento</th>
                                    <th scope="col">Iata</th>
                                    <th scope="col">Referencia periodo</th>
                                    <th class="text-center" scope="col">Opciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach ($objDocumentConciliation as $row)
                            <tr>

                            <td scope="row" style="white-space: nowrap;">{{$row->originalName}}</td>
                            <td scope="row" style="white-space: nowrap; "><b>{{$row->period->reference}}</b>
                            </td>
                            <td scope="row" style="white-space: nowrap; ">{{$row->period->description}}
                            </td>
                            <td class="text-center">
                                <a href="{{ url('/iata/' . $row->diskName . '/downloadFromPeriod') }}"
                                    class="btn btn-primary btn-icon-only">
                                    <span class="btn-inner--icon"><i class="ni ni-folder-17"></i></span>
                                </a>
                            </td>
                    </td></tr>
                        @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer" id="footer-documents" >
                        <a href="{{ route('conciliation.index') }}" class="btn btn-sm btn-default">Volver</a>
                    </div>
                </form>
            </div>
           
        </div>

        @include('layouts.footers.auth')
    </div>
@endsection

@push('js')
    <script src="{{ asset('assets') }}/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('assets') }}/vendor/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="{{ asset('assets') }}/vendor/datatables.net-select/js/dataTables.select.min.js"></script>
    <script src="{{ asset('assets') }}/vendor/sweetalert2/dist/sweetalert2.min.js"></script>
   
@endpush
