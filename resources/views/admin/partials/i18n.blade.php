@php
    $jsKeys = json_decode((string) @file_get_contents(lang_path('js.json')), true) ?: [];
    $jsDict = [];
    foreach ($jsKeys as $k) { $jsDict[$k] = __($k); }
@endphp
<script type="application/json" id="mcms-i18n">{!! json_encode($jsDict, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
<script src="{{ asset_v('assets/js/i18n.js') }}" nonce="{{ csp_nonce() }}" defer></script>
