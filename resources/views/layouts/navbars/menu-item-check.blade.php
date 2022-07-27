{{-- Validamos si existe el objeto ($role) para saber si el componente estaría funcionando dentro de la vista de edición, 
 de no existir el componente funcionaria sin este --}}

@php $valorItemMenu = 0; @endphp
@if ($item['submenu'] == [])

    @if (isset($role))
        @foreach ($role->menus as $menu)
            @if ($menu->pkMenu == $item['pkMenu'])
                @php $valorItemMenu = 1; @endphp
            @endif
        @endforeach
    @endif
    <li class="checklist-entry list-group-item flex-column align-items-start  bg-secondary">
        <div class="checklist-item ">
            <div class="cheklist-info">
                <div class="row">
                    <a class="nav-link active">
                        <i class="{{ $item['icon'] }} text-primary"></i> {{ $item['name'] }}
                    </a>
                    <input type="checkbox" name="checkMenu[]" value="{{ $item['pkMenu'] }}"
                        {{ $valorItemMenu == 1 ? 'checked' : '' }}>
                </div>
            </div>
        </div>
    </li>
@else
    @if (isset($role))
        @foreach ($role->menus as $menu)
            @if ($menu->pkMenu == $item['pkMenu'])
                @php $valorItemMenu = 1; @endphp
            @endif
        @endforeach
    @endif
    <li class="checklist-entry list-group-item flex-column align-items-start bg-secondary">
        <div class="checklist-item">
            <div class="cheklist-info">
                <div class="row">
                    <a class="nav-link active" href="#navbar-check-{{ $item['pkMenu'] }}" data-toggle="collapse"
                        role="button" aria-expanded="true" aria-controls="navbar-check-{{ $item['pkMenu'] }}">
                        <i class="{{ $item['icon'] }} text-primary"></i> {{ $item['name'] }}
                    </a>
                    <input type="checkbox" name="checkMenu[]" class="itemMenu" value="{{ $item['pkMenu'] }}"
                        {{ $valorItemMenu == 1 ? 'checked' : '' }}>
                </div>
            </div>
        </div>
    </li>
    <div class="collapse show" id="navbar-check-{{ $item['pkMenu'] }}">
        <ul class="nav nav-sm flex-column">
            
            @foreach ($item['submenu'] as $submenu)
            @php $valorItemSubMenu = 0; @endphp
                @if ($submenu['submenu'] == [])
                    @if (isset($role))
                        @foreach ($role->menus as $menu)
                            @if ($menu->pkMenu == $submenu['pkMenu'])
                                @php $valorItemSubMenu = 1; @endphp
                            @endif
                        @endforeach
                    @endif
                    <li class="checklist-entry list-group-item flex-column align-items-start">
                        <div class="checklist-item">
                            <div class="cheklist-info">
                                <div class="row">
                                    <a class="nav-link">
                                        {{ $submenu['name'] }}
                                    </a>
                                    <input type="checkbox" name="checkMenu[]"
                                        class="itemMenuChild{{ $item['pkMenu'] }}" value="{{ $submenu['pkMenu'] }}"
                                        {{ $valorItemSubMenu == 1 ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                    </li>
                @else
                    @include('layouts.navbars.menu-item', ['item' => $submenu])
                @endif
            @endforeach
        </ul>
    </div>
@endif
