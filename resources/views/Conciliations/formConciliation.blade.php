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
                        <h3 class="mb-0 ml-2">Proceso de conciliación de documentos</h3>
                </div>
                <hr>
            </div>
            <div class="card-body">
            <div class="header-body" id="header-body" style="display: none;">
            <!-- Card stats -->
            
            </div>
                <div id="spinner-loader" style="display: none;">
                    <div style="text-align: center">
                    <img alt="loader" src="{{ asset('assets') }}/img/theme/loader.gif"  >
                    </div>
                    <div style="text-align: center">
                    Procesando documentos, por favor espere...
                    </div>
                </div>
                <form method="post" enctype="multipart/form-data" id="formDropzone">
                @csrf
                    <div class="row" id="dropzoneArea" style="padding-bottom: 4%">
                        <div class="col-1"></div>
                        <div class="col-5">
                            <div class="dropzone" id="dropzonePrevio">
                                
                                <div class="dz-details">
                                    <div class="dz-filename"><span data-dz-name></span></div>
                                    <div class="dz-size " style="text-align: center" data-dz-size>
                                        <i class="fas fa-file-export fa-3x" id="#upload-icon"></i>
                                    </div>
                                    <div class="dz-default dz-message"><span>Documento PREVIO</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-5">
                            <div class="dropzone" id="dropzoneIata">
                                
                                <div class="dz-details">
                                    <div class="dz-filename"><span data-dz-name></span></div>
                                    <div class="dz-size " style="text-align: center" data-dz-size>
                                        <i class="fas fa-plane-departure fa-3x" id="#upload-icon"></i>
                                    </div>
                                    <div class="dz-default dz-message"><span>Documento IATA</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-1"></div>
                    </div>
                    
                    <div class="card-footer" id="footer-documents-dropzone" >
                        <button type="submit" id="submitButton" class="btn btn-sm btn-primary">
                            <i class="ni ni-button-power"></i> Procesar
                        </button>
                    </div>
                    
                </form>

                <form id="form-documents" action="#" method="post">
                @csrf
                    <div class="table-responsive mt-5"" id="table-documentsToAdd" style="display: none">
                        <h3 class="ml-2">Documentos nuevos han sido cargados al sistema</h3>
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
                    <div class="card-footer" id="footer-documents" style="display: none">
                        <a href="{{ route('conciliation.create') }}" class="btn btn-sm btn-default">Nueva conciliación</a>
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
    <script src="{{ asset('assets') }}/vendor/dropzone/dist/min/dropzone.min.js"></script>
    <script type="text/javascript">
    Dropzone.autoDiscover = false;
        $(document).ready(function() {
            const dropzones = [];
            var count = 0;
            $('.dropzone').each(function(i, el){
                const name = 'file' + count;
                var myDropzone = new Dropzone(el, {
                    url: window.location.pathname,
                    autoProcessQueue: false,
                    autoDiscover: false,
                    uploadMultiple: false,
                    parallelUploads: 1,
                    maxFiles: 1,
                    paramName: name,
                    chunking: true,
                    retryChunks: true,
                    parallelChunkUploads: true,
                });
                dropzones.push(myDropzone)
                count++;
            });

            $("#submitButton").on("click", (event) => {
                 // Make sure that the form isn't actually being sent.
                event.preventDefault();
                event.stopPropagation();
                let form = new FormData($('#formDropzone')[0])

                dropzones.forEach(dropzone => {
                let  { paramName }  = dropzone.options
                dropzone.files.forEach((file, i) => {
                    form.append(paramName + '[' + i + ']', file)
                })
                })
                $.ajax({
                    method: 'POST',
                    url: '{{route("conciliation.analysis")}}',
                    data: form,
                    processData: false,
                    contentType: false,
                    beforeSend: function(){
                        $("#dropzoneArea").css('display','none');
                        $("#spinner-loader").show();
                        $('#submitButton').attr('disabled', true);
                        
                    },
                    success: function(response) {
                        $("#spinner-loader").css('display','none');

                        if (response.error) {
                            $("#dropzoneArea").show();
                            $("#spinner-loader").css('display','none');
                            $('#submitButton').attr('disabled', false);
                            Dropzone.forElement('#dropzoneIata').removeAllFiles(true);
                            Dropzone.forElement('#dropzonePrevio').removeAllFiles(true);
                            Swal.fire({
                            type: 'warning',
                            title: 'Oops...',
                            text: response.error,
                            footer: '<a href="">Es necesario validar la información de la IATA sus documentos</a>'
                            })
                        }
                        if (response.dataContentTable) {

                        $("#table-documentsToAdd").show();
                        $("#footer-documents").show();
                        $("#footer-documents-dropzone").css("display", "none");
                        $("#datatable-new-document tbody").append(response.dataContentTable);
                        }

                        if (response.dataContentHeader) {

                        $("#header-body").show();
                        $("#header-body").append(response.dataContentHeader);

                        Swal.fire({
                        position: 'top-end',
                        type: 'success',
                        title: 'Sus documentos han sido procesados',
                        showConfirmButton: false,
                        timer: 2500
                        })
                        }
                    }
                });
            });


        });

        
    </script>
@endpush
