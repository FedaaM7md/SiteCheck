@if ($paginator->hasPages())
    <nav class="mt-6 flex flex-wrap items-center justify-between gap-3" aria-label="{{ __('sitecheck.report.sections') }}">
        <p class="text-xs text-soft">
            {{ __('sitecheck.history.showing', ['first' => $paginator->firstItem(), 'last' => $paginator->lastItem(), 'total' => $paginator->total()]) }}
        </p>
        <div class="flex flex-wrap items-center gap-1">
            @foreach ($paginator->linkCollection() as $link)
                @php
                    $label = html_entity_decode((string) ($link['label'] ?? ''), ENT_QUOTES, 'UTF-8');
                @endphp
                @if (($link['url'] ?? null) === null)
                    <span class="pagination-link pagination-disabled" aria-disabled="true">{{ $label }}</span>
                @elseif (! empty($link['active']))
                    <span class="pagination-link pagination-active" aria-current="page">{{ $label }}</span>
                @else
                    <a href="{{ $link['url'] }}" class="pagination-link">{{ $label }}</a>
                @endif
            @endforeach
        </div>
    </nav>
@endif
