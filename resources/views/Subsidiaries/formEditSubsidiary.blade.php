@extends('layouts.app')

@section('title', 'SUCURSALES')
@section('content')
    <div class="header bg-gradient-primary pb-7 pt-5 pt-md-8">
    </div>
    <div class="container-fluid mt--7">
    
        <div class="card shadow col-md-12">
                    <div class="card-header border-0">
                        <div class="row align-items-center">
                            <div class="col">
                                <h3 class="mb-0">Editar sucursal</h3>
                            </div>
                            <div class="col text-right">
                                <a href="{{ route('subsidiary.index') }}" class="btn btn-sm btn-default">Volver</a>
                            </div>
                        </div>
                        <hr>
                    </div>
                    <div class="card-body">
                        <form action="{{ url('/subsidiary/edit/'.$subsidiary->pkSubsidiary) }}" method="post">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="form-group col-xs-12 col-sm-6 col-md-6">
                                    <label for="description"> Descripción:</label>
                                    <input type="text" name="description" class="form-control @error('description') is-invalid @enderror" value="{{old('description', $subsidiary->description)}}" required>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group col-xs-12 col-sm-6 col-md-6">
                                    <label for="iata"> IATA ID:</label>
                                    <input type="text" name="iata" class="form-control @error('iata') is-invalid @enderror" value="{{old('iata', $subsidiary->iata)}}" required>
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

@push('js')
    <script src="{{ asset('argon') }}/vendor/chart.js/dist/Chart.min.js"></script>
    <script src="{{ asset('argon') }}/vendor/chart.js/dist/Chart.extension.js"></script>
@endpush