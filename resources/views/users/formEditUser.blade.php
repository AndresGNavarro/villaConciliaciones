@extends('layouts.app')

@section('styles')
<link href="{{ asset('assets') }}/vendor/select2/dist/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css">
@endsection

@section('title', 'USUARIOS')
@section('content')
    <div class="header bg-gradient-primary pb-7 pt-5 pt-md-8">
    </div>
    <div class="container-fluid mt--7">
    
        <div class="card shadow">
                    <div class="card-header border-0">
                        <div class="row align-items-center">
                            <div class="col">
                                <i class="fas fa-user-edit fa-2x"></i><h3 class="mb-0">Editar usuario</h3>
                            </div>
                            <div class="col text-right">
                                <a href="{{ route('user.index') }}" class="btn btn-sm btn-default">Volver</a>
                            </div>
                        </div>
                        <hr>
                    </div>
                    <div class="card-body">
                        <form action="{{ url('/user/edit/'.$user->id)}}" method="post">
                            @csrf
                            @method('PUT')
                            
                            <div class="row">
                                <div class="form-group col-xs-12 col-sm-6 col-md-6">
                                    <label for="name"> Nombre:</label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{old('name', $user->name)}}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group col-xs-12 col-sm-6 col-md-6">
                                    <label for="email">Email:</label>
                                    <input type="text" name="email" class="form-control @error('email') is-invalid @enderror" value="{{old('email', $user->email)}}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-xs-12 col-sm-4 col-md-4">
                                    <label for="role">Rol:</label>
                                    <select name="role" id="role" class="form-control select2  @error('role') is-invalid @enderror" required>
                                    <option value=""> Selecciona una opción: </option>
                                    @foreach ($objRole as $role)
                                    <option {{ $user->pkRole==$role->pkRole?'selected':''}}  {{ old('role')==$role->pkRole?'selected':'' }} value="{{$role->pkRole}}"> 
                                        {{$role->description}} </option>
                                    @endforeach
                                    </select>
                                    @error('role')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
    
                                <div class="form-group col-xs-12 col-sm-4 col-md-4">
                                    <label for="sucursal"><b> Sucursal Origen:</b></label>
                                    <select name="sucursal" id="sucursal" class="form-control select2  @error('sucursal') is-invalid @enderror" required>
                                    <option value=""> Selecciona una opción: </option>
                                    @foreach($objSubsidiary as $subsidiary)
                                    <option {{ $user->pkSubsidiary==$subsidiary->pkSubsidiary?'selected':''}}  {{ old('sucursal')==$subsidiary->pkSubsidiary?'selected':'' }} value="{{$subsidiary->pkSubsidiary}}"> 
                                        {{$subsidiary->description}} </option>
                                    @endforeach
                                    </select>
                                    @error('sucursal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group col-xs-12 col-sm-4 col-md-4">
                                    <label for="sucursalAdministra"> Sucursales a cargo:</label>
                                    <select name="sucursalAdministra[]" id="sucursalAdministra" multiple="multiple" class="form-control  @error('sucursalAdministra') is-invalid @enderror" required>
                                    @foreach ($objSubsidiary as $subsidiary)
                                    <option {{ ( in_array($subsidiary->pkSubsidiary, $arrayUserSubsidiary)) ?'selected':'' }} {{ ( in_array($subsidiary->pkSubsidiary, old('sucursalAdministra')?: [])) ?'selected':'' }} value="{{$subsidiary->pkSubsidiary}}"> {{$subsidiary->description}} </option>
                                    @endforeach
                                    </select>
                                    @error('sucursalAdministra')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </form>
                    </div>
                </div>
            
        

        @include('layouts.footers.auth')
    </div>
@endsection

@push('js')
    <script src="{{ asset('assets') }}/vendor/chart.js/dist/Chart.min.js"></script>
    <script src="{{ asset('assets') }}/vendor/chart.js/dist/Chart.extension.js"></script>
    <script src="{{ asset('assets') }}/vendor/select2/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap4',
            });
            $('#sucursalAdministra').select2({
                theme: 'bootstrap4',
            });
        });
    </script>
@endpush