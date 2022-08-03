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
                <div class="row align-items-center">
                    <div class="col">
                        <i class="fas fa-file-excel fa-2x"></i>
                        <h3 class="mb-0">Asignación masiva IATAS a periodos</h3>
                    </div>
                </div>
                <hr>
            </div>
            <div class="card-body">
                <div class="row justify-content-md-center">
                    <div class="col-1"></div>
                    <div class="col-10">
                        <form method="post" action="{{ route('iata.validate.period') }}" enctype="multipart/form-data"
                            class="dropzone" id="dropzoneIata">
                            @csrf
                            <div class="dz-details">
                                <div class="dz-filename"><span data-dz-name></span></div>
                                <div class="dz-size " style="text-align: center" data-dz-size>
                                    <i class="fas fa-cloud-upload-alt fa-3x" id="#upload-icon"></i>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-1"></div>
                </div>

            </div>
            <hr>

            <div class="table-responsive" id="table-periodos">
                <h3 class="ml-2">Listado de periodos</h3>
                <!-- Projects table -->
                <table class="table align-items-center table-flush table-hover" id="datatable-main">

                    <thead class="thead-light">
                        <tr>
                            <th scope="col">Id</th>
                            <th scope="col">Referencia</th>
                            <th scope="col">Descripción</th>
                            <th class="text-center" scope="col">Opciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($objPeriod as $row)
                            <tr>
                                <td scope="row" style="white-space:nowrap">
                                    {{ $row->pkPeriod }}
                                </td>
                                <td scope="row" style="white-space:nowrap">
                                    <b>{{ $row->reference }}</b>
                                </td>
                                <td scope="row" style="white-space:nowrap">
                                    {{ $row->description }}
                                </td>
                                @if ($row->url == null)
                                    <td scope="row" style="white-space:nowrap">
                                        No existe documento relacionado
                                    </td>
                                @else
                                    <td class="text-center">
                                        <a href="{{ url('/iata/' . $row->url . '/downloadFromPeriod') }}"
                                            class="btn btn-primary btn-icon-only">
                                            <span class="btn-inner--icon"><i class="ni ni-folder-17"></i></span>
                                        </a>
                                    </td>
                                @endif

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <form id="form-documents" action="{{ route('iata.store.period') }}" method="post">
                @csrf
                <div class="table-responsive" id="table-documentsToAdd" style="display: none">
                    <h3 class="ml-2">Documentos nuevos</h3>
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
                        </tbody>
                    </table>
                </div>

                <div class="table-responsive" id="table-documentsToUpdate" style="display: none">
                    <h3 class="ml-2">Documentos para actualizar</h3>
                    <!-- Projects table -->
                    <table class="table align-items-center table-flush table-hover" id="datatable-update-document">

                        <thead class="thead-light">
                            <tr>
                                <th scope="col">Nombre documento</th>
                                <th scope="col">Iata</th>
                                <th scope="col">Referencia periodo</th>
                                <th class="text-center" scope="col">Opciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer" id="footer-documents" style="display: none">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-check"></i>Guardar
                    </button>
                </div>
            </form>
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
            $('#datatable-main').DataTable({
                scrollY: '60vh',
                paging: false,
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

            });
        });

        myDropzoneObject = Dropzone.options.dropzoneIata = {
            maxFilesize: 2,
            dictDefaultMessage: "Agregue sus IATAS para asignarlas a un período",
            success: function(file, response) {


                $(".dz-image img").attr("src",
                    "/assets/vendor/@fortawesome/fontawesome-free/svgs/regular/file-alt.svg");
                if (response.dataContentNewIata != "") {

                    $("#table-periodos").css("display", "none");
                    $("#table-documentsToAdd").css("display", "block");
                    $("#footer-documents").css("display", "block");
                    $("#datatable-new-document tbody").append(response.dataContentNewIata);

                } else if (response.dataContentUpdateIata != "") {

                    $("#table-periodos").css("display", "none");
                    $("#table-documentsToUpdate").css("display", "block");
                    $("#footer-documents").css("display", "block");
                    $("#datatable-update-document tbody").append(response.dataContentUpdateIata);
                }


            },
            error: function(file, response) {
                return false;
            }
        };


        function deleteRow(that) {
            $(that).closest('tr').remove();

        }

        $("#form-documents").on("submit", (event) => {
            event.preventDefault();

            if ($(".inputForm").length == 0) {
                Swal.fire({
                    type: 'error',
                    title: 'Oops...',
                    text: 'Parece que no existen documentos por agregar o actualizar!'
                });
                return false;
            } else if ($(".inputForm").length > 0) {
                event.currentTarget.submit();
            }
        });
    </script>
@endpush
