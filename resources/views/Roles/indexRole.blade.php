@extends('layouts.app')

@section('styles')
<link href="{{ asset('assets') }}/vendor/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet">
<link href="{{ asset('assets') }}/vendor/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet">
<link href="{{ asset('assets') }}/vendor/datatables.net-select-bs4/css/select.bootstrap4.min.css" rel="stylesheet">
<link href="{{ asset('assets') }}/vendor/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">
@endsection

@section('title', 'ROLES DE USUARIO')
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
        <div class="card shadow ">
                    <div class="card-header border-0">
                        <div class="row align-items-center">
                            <div class="col">
                                <h3 class="mb-0">Roles de usuario</h3>
                            </div>
                            <div class="col text-right">
                                <a href="{{ url('/role/create')}}" class="btn btn-sm btn-success">Agregar</a>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <!-- Projects table -->
                        <table class="table align-items-center table-flush table-hover" id="datatable-main">
                            <thead class="thead-light">
                                <tr>
                                    <th scope="col">Id</th>
                                    <th scope="col">Rol</th>
                                    <th scope="col">Fecha creación</th>
                                    <th class="text-center" scope="col" >Opciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($objRole as $row)
                                <tr>
                                    <td scope="row" style="white-space:nowrap">
                                    {{$row->pkRole}}
                                    </td>
                                    <td scope="row" style="white-space:nowrap">
                                    {{$row->description}}
                                    </td>
                                    <td scope="row" style="white-space:nowrap">
                                    {{$row->created_at}}
                                    </td>

                                    <td class="text-center">
                                        <form action="{{ url('/role/'.$row->pkRole) }}" method="post">
                                        @csrf
                                        @method('DELETE')

                                        <a href="{{ url('/role/'.$row->pkRole.'/edit') }}" class="btn btn-sm btn-primary">Editar</a>
                                        <button class="btn btn-sm btn-danger" onclick="return deleteItem(this.form)">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach  
                            </tbody>
                        </table>
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

    <script type="text/javascript">
        $(document).ready( function () {
            $('#datatable-main').DataTable({
                scrollY: '60vh',
                paging: false,
                language: {
                    search: '<i class="fa fa-filter" aria-hidden="true"></i>',
                    searchPlaceholder: 'Filtrar',
                    lengthMenu:  'Mostrando _MENU_ por página',
                    info:  'Mostrando _START_ a _END_ de _TOTAL_ registros',
                    infoFiltered: '(Filtrado de _MAX_ registros)',
                    zeroRecords: 'Sin registros',
                    infoEmpty: 'Sin registros',
                    loadingRecords: 'Cargando...',
                },
                
            });
        } );

        deleteItem = (form) =>{

            Flag = true;

            Swal.fire({
                title: "Estás seguro?",
                text: "Esta acción no podrá ser revertida",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, eliminar registro",
                cancelButtonText: "Cancelar!",
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
            }).then(function(result) {
                if (result.value) {
                    form.submit();
                    Flag = false;
                    return true;
                } else if (result.dismiss === "cancel") {
                    Flag = true;
                    return false;
                }
            });

            if(Flag){
                return false;
            }
        }
    </script>
@endpush