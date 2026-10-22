@extends('admin.layout')
@section('title', __('Блокове и шаблони'))
@section('actions')@if($ready)<a class="btn sm primary" href="{{ route('admin.blocks.create') }}">＋ {{ __('Нов блок') }}</a> <a class="btn sm" href="{{ route('admin.blocks.create', ['kind' => 'template']) }}">＋ {{ __('Нов шаблон') }}</a>@endif @endsection

@section('content')
@if(! $ready)
    <div class="flash err">{{ __('Таблицата още не е създадена.') }} <a href="{{ route('admin.system.migrations') }}">{{ __('Обновете базата данни') }}</a></div>
@else
<section class="card"><p class="muted">{!! __('<strong>Блок</strong> е парче съдържание за многократна употреба — вмъква се в текст с <code>[block име]</code> или чрез джаджата „Блок“. <strong>Шаблон</strong> е начално съдържание, което избирате при създаване на нова статия/страница.') !!}</p></section>
<div class="table-wrap">
<table class="table">
    <thead><tr><th>{{ __('Име') }}</th><th>{{ __('Вид') }}</th><th>{{ __('Код') }}</th><th></th></tr></thead>
    <tbody>
    @forelse ($rows as $r)
        <tr>
            <td><a href="{{ route('admin.blocks.edit', $r->id) }}">{{ $r->name }}</a></td>
            <td>{{ __(\App\Support\Blocks::KINDS[$r->kind] ?? $r->kind) }}@if($r->kind === 'template') <small class="muted">({{ __(\App\Support\Blocks::APPLIES[$r->applies_to] ?? '') }})</small>@endif</td>
            <td>@if($r->kind === 'block')<code>[block {{ $r->slug }}]</code>@endif</td>
            <td class="row-actions"><form method="post" action="{{ route('admin.blocks.destroy', $r->id) }}" data-confirm="{{ __('Да изтрия ли записа?') }}">@csrf @method('DELETE')<button class="btn sm danger">{{ __('Изтрий') }}</button></form></td>
        </tr>
    @empty
        <tr><td colspan="4" class="muted center">{{ __('Още няма блокове или шаблони.') }}</td></tr>
    @endforelse
    </tbody>
</table>
</div>
@endif
@endsection
