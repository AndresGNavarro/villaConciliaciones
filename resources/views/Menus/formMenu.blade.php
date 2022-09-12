@extends('layouts.app')

@section('styles')
<link href="{{ asset('assets') }}/vendor/select2/dist/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css">
@endsection

@section('title', 'MENU')
@section('content')
    <div class="header bg-gradient-primary pb-7 pt-5 pt-md-8">
    </div>
    <div class="container-fluid mt--7">
    
        <div class="card shadow col-md-12">
                    <div class="card-header border-0">
                        <div class="row align-items-center">
                            <div class="col">
                                <h3 class="mb-0">Nueva Opción de menú</h3>
                            </div>
                            <div class="col text-right">
                                <a href="{{ route('menu.index') }}" class="btn btn-sm btn-default">Volver</a>
                            </div>
                        </div>
                        <hr>
                    </div>
                    
                    <div class="card-body">
                        <form action="{{ route('menu.store') }}" method="post">
                            @csrf
                            <div class="row">
                                <div class="form-group col-xs-12 col-sm-6 col-md-3">
                                    <label for="name"> Nombre:</label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{old('name')}}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group col-xs-12 col-sm-6 col-md-3">
                                    <label for="slug"> Ruta:</label>
                                    <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{old('slug')}}" required>
                                    @error('slug')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group col-xs-12 col-sm-6 col-md-3">
                                    <label for="icon"> Icono:</label>
                                    <input type="text" name="icon" class="form-control @error('icon') is-invalid @enderror" value="{{old('icon')}}">
                                    @error('icon')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group col-xs-12 col-sm-6 col-md-3">
                                    <label for="parent">Opción de menú padre:</label>
                                    <select name="parent" id="parent" class="form-control select2  @error('parent') is-invalid @enderror">
                                    <option value="0"> Selecciona una opción: </option>
                                    @foreach ($objMenu as $menu)
                                    <option {{ old('parent')==$menu->pkMenu?'selected':'' }} value="{{$menu->pkMenu}}"> {{$menu->name}} </option>
                                    @endforeach
                                    </select>
                                    @error('parent')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                            </div>

                            <div class="row">
                                <div class="form-group col-xs-12 col-sm-6 col-md-3">
                                    <label for="order" >Orden:</label>
                                    <input class="form-control" type="number" value="{{old('order', '0')}}" name="order" id="order" min="0" max="100" required>
                                </div>
                                <div class="form-group col-xs-12 col-sm-6 col-md-3">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" name="enabled" id="enabled" checked>
                                        <label class="custom-control-label" for="enabled">Activo</label>
                                    </div>
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
    <script src="{{ asset('assets') }}/vendor/select2/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap4',
            });
        });
    </script>
@endpush