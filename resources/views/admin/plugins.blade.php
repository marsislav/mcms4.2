@extends('admin.layout')
@section('title', 'Плъгини')

@section('content')
<p class="muted">Плъгините се качват по FTP в папка <code>plugins/&lt;име&gt;/</code> (с <code>plugin.json</code> и <code>plugin.php</code>) — умишлено не могат да се качват през панела. Активирайте само код, на който имате доверие: плъгинът изпълнява PHP с пълните права на сайта.</p>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>Плъгин</th><th>Версия</th><th>Описание</th><th>Състояние</th><th></th></tr></thead>
    <tbody>
    @forelse ($plugins as $p)
        <tr>
            <td><strong>{{ $p['name'] }}</strong><br><small class="muted">{{ $p['slug'] }}{{ $p['author'] ? ' · '.$p['author'] : '' }}</small></td>
            <td>{{ $p['version'] }}</td>
            <td>{{ $p['description'] }}</td>
            <td>{!! $p['enabled'] ? '<span class="tag ok">активен</span>' : '<span class="tag">изключен</span>' !!}</td>
            <td class="row-actions">
                @if($p['has_entry'])
                <form method="post" action="{{ route('admin.plugins.toggle', $p['slug']) }}">@csrf<button class="btn sm {{ $p['enabled'] ? '' : 'primary' }}">{{ $p['enabled'] ? 'Изключи' : 'Активирай' }}</button></form>
                @else<small class="muted">липсва plugin.php</small>@endif
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="muted center">Няма инсталирани плъгини.</td></tr>
    @endforelse
    </tbody>
</table>
</div>

<section class="card">
    <h2>API за разработчици</h2>
<pre class="codes">// plugins/my-plugin/plugin.php
add_action('head', fn () => print '&lt;meta name="x" content="y"&gt;');
add_filter('content.body', fn ($html, $content) =&gt; $html);
add_filter('admin.menu', function ($nav) { $nav[] = ['admin.dashboard', 'Моя връзка', null, null]; return $nav; });
add_action('contact.received', fn ($message) =&gt; null);
add_action('content.saved', fn ($content) =&gt; null);
add_action('user.login', fn ($user) =&gt; null);</pre>
    <p class="muted">Налични куки: <code>head</code>, <code>footer</code>, <code>content.body</code>, <code>content.saved</code>, <code>contact.received</code>, <code>user.login</code>, <code>admin.menu</code>, <code>site.menu</code>.</p>
</section>
@endsection
