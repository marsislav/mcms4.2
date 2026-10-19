@extends('admin.layout')
@section('title', __('Коментари'))

@section('content')
@unless($ready)
<section class="card warn-card">
    {!! __('<strong>Таблицата за коментари още не е създадена.</strong> Натиснете „Обнови базата“ или изпълнете <code>database/upgrade/4.2-upgrade.sql</code>.') !!}
    @if(auth()->user()->isAdmin())
    <form method="post" action="{{ route('admin.system.migrate') }}" class="inline-form" style="margin-top:.6rem">@csrf <button class="btn primary">{{ __('Обнови базата') }}</button></form>
    @endif
</section>
@else
@unless(\App\Support\Discussion::on('enabled'))
<div class="flash err">{{ __('Коментарите са изключени (Настройки → Дискусии). Съществуващите не се показват на сайта.') }}</div>
@endunless

<div class="subtabs">
    @foreach (['all' => __('Всички'), 'pending' => __('Чакащи'), 'approved' => __('Одобрени'), 'spam' => __('Спам'), 'trash' => __('Кошче')] as $k => $label)
        <a href="{{ route('admin.comments.index', ['status' => $k] + request()->only('q', 'content')) }}" @class(['active' => $status === $k])>{{ $label }} <small class="muted">({{ $counts[$k] ?? 0 }})</small></a>
    @endforeach
    <form method="get" class="inline-form" style="margin-left:auto">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Търсене…') }}" style="width:14rem">
    </form>
</div>

<form method="post" action="{{ route('admin.comments.bulk') }}" id="bulk-form">
    @csrf
    <div class="btn-row" style="margin-bottom:.6rem">
        <select name="action" aria-label="{{ __('Действие') }}">
            <option value="">{{ __('— действие —') }}</option>
            @if($status !== 'approved' && $status !== 'all')<option value="approve">{{ __('Одобри') }}</option>@endif
            @if($status === 'all' || $status === 'approved')<option value="unapprove">{{ __('Върни за одобрение') }}</option>@endif
            @if($status === 'all')<option value="approve">{{ __('Одобри') }}</option>@endif
            @if($status !== 'spam')<option value="spam">{{ __('Спам') }}</option>@endif
            @if($status === 'spam')<option value="notspam">{{ __('Не е спам') }}</option>@endif
            @if($status !== 'trash')<option value="trash">{{ __('Премести в кошчето') }}</option>@endif
            @if($status === 'trash')<option value="restore">{{ __('Възстанови') }}</option>@endif
            @if($status === 'trash' || $status === 'spam')<option value="delete">{{ __('Изтрий завинаги') }}</option>@endif
        </select>
        <button class="btn">{{ __('Приложи') }}</button>
        @if(in_array($status, ['spam', 'trash'], true) && ($counts[$status] ?? 0))
            <button class="btn danger" formaction="{{ route('admin.comments.empty') }}" name="what" value="{{ $status }}" data-confirm-btn="{{ __('Да изтрия ли всичко завинаги?') }}">{{ __('Изпразни') }} {{ $status === 'spam' ? __('спама') : __('кошчето') }}</button>
        @endif
    </div>

    <div class="table-wrap">
    <table class="table comments-table">
        <thead><tr><th style="width:2rem"><input type="checkbox" data-check-all aria-label="{{ __('Всички') }}"></th><th>{{ __('Автор') }}</th><th>{{ __('Коментар') }}</th><th>{{ __('Към') }}</th></tr></thead>
        <tbody>
        @forelse ($items as $c)
            @php $tr = $c->content?->translations->first(); @endphp
            <tr @class(['unread' => $c->status === 'pending'])>
                <td><input type="checkbox" name="ids[]" value="{{ $c->id }}" aria-label="{{ __('Избери') }}"></td>
                <td>
                    <strong>{{ $c->author_name }}</strong>@if($c->user_id) {!! __('<span class="tag ok">екип</span>') !!}@endif<br>
                    @if($c->author_email)<small><a href="mailto:{{ $c->author_email }}">{{ $c->author_email }}</a></small><br>@endif
                    @if($c->author_url)<small><a href="{{ $c->author_url }}" rel="nofollow noopener noreferrer" target="_blank">{{ \Illuminate\Support\Str::limit(preg_replace('~^https?://~', '', $c->author_url), 30) }}</a></small><br>@endif
                    <small class="muted">{{ $c->author_ip }}</small>
                </td>
                <td>
                    <small class="muted">{{ $c->created_at->format('d.m.Y H:i') }}@if($c->parent_id) {{ __('· отговор на #') }}{{ $c->parent_id }}@endif @if($c->status === 'pending'){!! __('· <strong>чака одобрение</strong>') !!}@endif</small>
                    <div>{!! \App\Support\Discussion::format(\Illuminate\Support\Str::limit($c->body, 400)) !!}</div>
                    <div class="row-actions">
                        {!! __('<a href=":p1">Редакция / отговор</a>', ['p1' => e(route('admin.comments.edit', $c))]) !!}
                    </div>
                </td>
                <td>
                    @if($tr)<a href="{{ route('admin.comments.index', ['content' => $c->content_id]) }}">{{ \Illuminate\Support\Str::limit($tr->title, 40) }}</a>@else <span class="muted">—</span>@endif
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="muted center">{{ __('Няма коментари.') }}</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</form>
{{ $items->links() }}
@endunless
@endsection
