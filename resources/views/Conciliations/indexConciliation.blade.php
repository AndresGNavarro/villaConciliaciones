@extends('layouts.app')

@section('styles')
    <link href="{{ asset('assets') }}/vendor/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="{{ asset('assets') }}/vendor/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet">
    <link href="{{ asset('assets') }}/vendor/datatables.net-select-bs4/css/select.bootstrap4.min.css" rel="stylesheet">
    <link href="{{ asset('assets') }}/vendor/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="{{ asset('assets') }}/vendor/dropzone/dist/min/dropzone.min.css" rel="stylesheet">
@endsection

@section('title', 'IATAS')
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
            <div class="card-header border-0">
                <div class="row align-items-end">
                        <i class="fas fa-university fa-2x"></i>
                        <h3 class="mb-0 ml-2">Historial de conciliaciones</h3>
                </div>
                
            </div>
            <div class="nav-wrapper">
                <ul class="nav nav-pills nav-fill flex-column flex-md-row" id="tabs-icons-text" role="tablist">
                    @foreach ($objUserSubsidiary as $index => $subsidiary)
                    <li class="nav-item">
                        <a class="nav-link mr-2 ml-2 mb-sm-3 mb-md-0 {{$index == 0 ? 'active' : '' }}" id="tabs-icons-text-{{$index}}-tab" data-toggle="tab" href="#tabs-icons-text-{{$index}}" role="tab" aria-controls="tabs-icons-text-{{$index}}" aria-selected="true"><i class="fas fa-plane mr-2"></i>{{$subsidiary->description}}</a>
                    </li> 
                    @endforeach
                    
                </ul>
            </div>
            <hr>
            <div class="tab-content" id="myTabContent">
                @foreach ($objUserSubsidiary as $indexTab => $subsidiary)
                <div class="tab-pane fade {{$indexTab == 0 ? 'show active' : '' }}" id="tabs-icons-text-{{$indexTab}}" role="tabpanel" aria-labelledby="tabs-icons-text-{{$indexTab}}-tab">
                    <!-- Projects table -->
                    <table class="table datatable-main align-items-center table-flush table-hover" id="datatable-main">
                        <thead class="thead-light">
                            <tr>
                                <th scope="col">Id</th>
                                <th scope="col">Referencia</th>
                                <th scope="col">Descripción</th>
                                <th class="text-center" scope="col">Opciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($objConciliation as $row)
                                @if ($subsidiary->iata == $row->iata)
                                <tr>
                                    <td scope="row" style="white-space:nowrap">
                                        {{ $row->pkConciliation }}
                                    </td>
                                    <td scope="row" style="white-space:nowrap">
                                        <b>{{ $row->iata }}</b>
                                    </td>
                                    <td scope="row" style="white-space:nowrap">
                                        {{ $row->period->description }}
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ url('/conciliation/'.$row->pkConciliation) }}" method="post">
                                            @csrf
                                            @method('DELETE')
                                        <a href="{{ url('/conciliation/' . $row->pkConciliation . '/show') }}"
                                            class="btn btn-primary btn-icon-only">
                                            <span class="btn-inner--icon"><i class="ni ni-folder-17"></i></span>
                                        </a>
                                        <button class="btn btn-danger btn-icon-only" onclick="return deleteItem(this.form)" data-toggle="tooltip" data-placement="left" title="Eliminar conciliación">
                                            <span class="btn-inner--icon"> <i class="ni ni-fat-remove"></i></span>
                                        </button>
                                        </form>
                                    </td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endforeach
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
    <script src="{{ asset('assets') }}/vendor/dropzone/dist/min/dropzone.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('.datatable-main').DataTable({
                scrollY: '60vh',
                pagingType: "full_numbers",
                language: {
                    search: '<i class="fa fa-filter" aria-hidden="true"></i>',
                    searchPlaceholder: 'Filtrar',
                    lengthMenu: 'Mostrando _MENU_ por página',
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                    infoFiltered: '(Filtrado de _MAX_ registros)',
                    zeroRecords: 'Sin registros',
                    infoEmpty: 'Sin registros',
                    loadingRecords: 'Cargando...',
                },
                columnDefs:[
                { "width": "20%", "targets": 0 },
                { "width": "30%", "targets": 1 },
                { "width": "35%", "targets": 2 },
                { "width": "15%", "targets": 3, "orderable": false },
                ]
            });
        });
        
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            $.fn.dataTable
                .tables( { visible: true, api: true } )
                .columns.adjust();
        });

        deleteItem = (form) =>{

        Swal.fire({
            title: "¿Estás seguro?",
            text: "Los cambios no podrán ser revertidos",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, eliminar registro",
            cancelButtonText: "Cancelar!",
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
        }).then(function(willDelete) {
            if (willDelete.value) {
                form.submit();
                
                return true;
            } else if (willDelete.dismiss === "cancel") {
                
                return false;
            }
        });

        return false;
        }
 
    </script>
@endpush
