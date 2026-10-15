@extends('admin.layout')
@section('title', 'Plugins')

@section('content')
<p class="muted">Plugins are uploaded via FTP to the <code>plugins/&lt;name&gt;/</code> folder (with <code>plugin.json</code> and <code>plugin.php</code>) — they intentionally cannot be uploaded through the admin panel. Only enable code you trust: a plugin runs PHP with the full privileges of the site.</p>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>Plugin</th><th>Version</th><th>Description</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse ($plugins as $p)
        <tr>
            <td><strong>{{ $p['name'] }}</strong><br><small class="muted">{{ $p['slug'] }}{{ $p['author'] ? ' · '.$p['author'] : '' }}</small></td>
            <td>{{ $p['version'] }}</td>
            <td>{{ $p['description'] }}</td>
            <td>{!! $p['enabled'] ? '<span class="tag ok">active</span>' : '<span class="tag">disabled</span>' !!}</td>
            <td class="row-actions">
                @if($p['has_entry'])
                <form method="post" action="{{ route('admin.plugins.toggle', $p['slug']) }}">@csrf<button class="btn sm {{ $p['enabled'] ? '' : 'primary' }}">{{ $p['enabled'] ? 'Disable' : 'Enable' }}</button></form>
                @else<small class="muted">plugin.php is missing</small>@endif
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="muted center">No plugins installed.</td></tr>
    @endforelse
    </tbody>
</table>
</div>

<section class="card">
    <h2>Developer API</h2>
<pre class="codes">// plugins/my-plugin/plugin.php
add_action('head', fn () => print '&lt;meta name="x" content="y"&gt;');
add_filter('content.body', fn ($html, $content) =&gt; $html);
add_filter('admin.menu', function ($nav) { $nav[] = ['admin.dashboard', 'My link', null, null]; return $nav; });
add_action('contact.received', fn ($message) =&gt; null);
add_action('content.saved', fn ($content) =&gt; null);
add_action('user.login', fn ($user) =&gt; null);</pre>
    <p class="muted">Available hooks: <code>head</code>, <code>footer</code>, <code>content.body</code>, <code>content.saved</code>, <code>contact.received</code>, <code>user.login</code>, <code>admin.menu</code>, <code>site.menu</code>.</p>
</section>
@endsection
