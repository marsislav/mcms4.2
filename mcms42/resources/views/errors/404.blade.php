@extends('errors.layout')
@section('code', '404')
@section('title', t('error.404.title', 'Страницата не е намерена'))
@section('message', t('error.404.text', 'Адресът не съществува или е преместен.'))
