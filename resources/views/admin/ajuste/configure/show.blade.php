@extends('layouts.master')

@section('content')

@section('nav_title', 'Configurações do ajuste')

@include('admin.menu')
<div class="container" style="width: 380px">
<form method="GET" action="/admin/ajuste/config/editar">
  {{csrf_field()}}
  <table class="table">
    <thead>
      <tr>
        <th scope="col">Nome</th>
        <th scope="col">Valor</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <th scope="row">Data de abertura</th>
        <td>{{$datas['abertura']}}</td>
      </tr>
      <tr>
        <th scope="row">Data de fechamento</th>
        <td>{{$datas['fechamento']}}</td>
      </tr>
      <tr>
      </tr>
    </tbody>
  </table>
  
  <button type="submit" class="btn btn-primary">Editar</button>
</form>
</div>

@endsection