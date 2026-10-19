@extends('errors.layout')
@section('code', '503')
@section('title', t('maintenance.title', __('Сайтът е в профилактика')))
@section('message', \App\Support\Options::get('maintenance_message') ?: t('maintenance.text', __('Скоро ще се върнем.')))
