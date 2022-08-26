@extends('layouts.app')

@section('title', 'SUCURSAL')
@section('content')
    <div class="header bg-gradient-primary pb-7 pt-5 pt-md-8">
    </div>
    <div class="container-fluid mt--7">
    
        <div class="card shadow col-md-12">
                    <div class="card-header border-0">
                        <div class="row align-items-center">
                            <div class="col">
                                <h3 class="mb-0">Nueva Sucursal</h3>
                            </div>
                            <div class="col text-right">
                                <a href="{{ route('subsidiary.index') }}" class="btn btn-sm btn-default">Volver</a>
                            </div>
                        </div>
                        <hr>
                    </div>
                    
                    <div class="card-body">
                        <form action="/subsidiary/create" method="post">
                            @csrf
                            <div class="row">
                                <div class="form-group col-xs-12 col-sm-6 col-md-6">
                                    <label for="description"> Descripción:</label>
                                    <input type="text" name="description" class="form-control @error('description') is-invalid @enderror" value="{{old('description')}}" required>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group col-xs-12 col-sm-6 col-md-6">
                                    <label for="iata"> Iata ID:</label>
                                    <input type="text" name="iata" class="form-control @error('iata') is-invalid @enderror" value="{{old('iata')}}">
                                    @error('iata')
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

