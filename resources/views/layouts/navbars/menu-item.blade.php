@if ($item['submenu'] == [])
    <li class="nav-item">
        <a class="nav-link active" href="{{ url($item['slug']) }}">
            <i class="{{ $item['icon'] }} text-primary"></i> {{ $item['name'] }} 
        </a>
    </li>
@else
    <li class="nav-item">
        <a class="nav-link active" href="#navbar-{{ $item['pkMenu'] }}" data-toggle="collapse" role="button"  aria-expanded="true" aria-controls="navbar-{{ $item['pkMenu'] }}">
            <i class="{{ $item['icon'] }} text-primary"></i> {{ $item['name'] }} 
        </a>
        <div class="collapse" id="navbar-{{ $item['pkMenu'] }}">
            <ul class="nav nav-sm flex-column">
                @foreach ($item['submenu'] as $submenu)
                    @if ($submenu['submenu'] == [])
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url($submenu['slug']) }}">
                                 {{ $submenu['name'] }} 
                            </a>
                        </li>
                    @else
                        @include('layouts.navbars.menu-item', [ 'item' => $submenu ])
                    @endif
                @endforeach
            </ul>
        </div>
    </li>
@endif