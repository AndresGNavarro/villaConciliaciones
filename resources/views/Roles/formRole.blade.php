@extends('layouts.app')

@section('title', 'ROLES')
@section('content')
    <div class="header bg-gradient-primary pb-7 pt-5 pt-md-8">
    </div>
    <div class="container-fluid mt--7">

        <div class="card shadow col-md-12">
            <div class="card-header border-0">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="mb-0">Nuevo Rol</h3>
                    </div>
                    <div class="col text-right">
                        <a href="{{ route('role.index') }}" class="btn btn-sm btn-default">Volver</a>
                    </div>
                </div>
                <hr>
            </div>

            <div class="card-body">
                <form action="{{ route('role.store') }}" method="post">
                    @csrf
                    <div class="row">
                        <div class="form-group col-xs-12 col-sm-6 col-md-6">
                            @foreach ($menus as $key => $item)
                                @if ($item['parent'] != 0)
                                @break
                            @endif
                            @include('layouts.navbars.menu-item-check', ['item' => $item])
                        @endforeach
                    </div>
                    <div class="form-group col-xs-12 col-sm-6 col-md-6">
                        <label for="description"> Descripción:</label>
                        <input type="text" name="description"
                            class="form-control @error('description') is-invalid @enderror"
                            value="{{ old('description') }}" required>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <br>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>

                </div>


            </form>
        </div>
    </div>



    @include('layouts.footers.auth')
</div>
@endsection

@push('js')
<script src="{{ asset('argon') }}/vendor/chart.js/dist/Chart.min.js"></script>
<script src="{{ asset('argon') }}/vendor/chart.js/dist/Chart.extension.js"></script>
<script>
    //Detectar los click en las opciones de menú Padre, a partir de ahí cambiar las propiedades de los hijos a checked-unchecked

    checkChildItem = $(".itemMenu").on("click", function() {

        let id = $(this).val();
        if ($(this).is(":Checked")) {
            $(`.itemMenuChild${id}`).prop("checked", true);

        } else {
            $(`.itemMenuChild${id}`).prop("checked", false);

        }

    });
</script>
@endpush
