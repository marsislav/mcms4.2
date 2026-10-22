{{-- Extra <head> output: structured data (JSON-LD). Data blocks are not executed, so no CSP nonce is needed. --}}
@if(\App\Support\Options::on('jsonld'))
    @foreach (\App\Support\StructuredData::graph($seo ?? [], $content ?? null, $crumbs ?? []) as $node)
        <script type="application/ld+json">{!! json_encode($node, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endforeach
@endif
