@props(['title', 'breadcrumbs' => []])

<div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
    <div>
        <h1 class="page-title mb-1">{{ $title }}</h1>
        @if(count($breadcrumbs))
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    @foreach($breadcrumbs as $label => $url)
                        @if($url)
                            <li class="breadcrumb-item"><a href="{{ $url }}">{{ $label }}</a></li>
                        @else
                            <li class="breadcrumb-item active">{{ $label }}</li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        @endif
    </div>
    @if(isset($actions))
        <div class="d-flex gap-2">{{ $actions }}</div>
    @endif
</div>
