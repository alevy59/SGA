@extends('layouts.master')

@section('title', 'SGA - Editar Evento')

@section('nav_title', 'Editar Evento')

@section('content')
@include('partials.menu')
<div class="container" style="width: 900px">
	<form method="POST" action="/admin/eventos/{{$event->id}}">
	{{csrf_field()}}
	{{method_field('PATCH')}}
		<div class="form-group">
			<label for="nome">Nome:</label>
			<input type="text" class="form-control" id="nome" name="nome" placeholder="Nome do evento" 
				value="{{$event['nome']}}">
		</div>
		<div class="form-group">
			<label for="descricao">Descrição:</label>
    		<textarea class="form-control" name="descricao" id="descricao" rows="3">{{$event['descricao']}}</textarea>
		</div>
		<div class="form-group">
			<label for="local">Local:</label>
			<input type="text" class="form-control" id="local" name="local" placeholder="Local do evento"
				value="{{$event['local']}}">
		</div>
		<div class="form-group">
			<label for="data">Data:</label>
			<input type="datetime-local" class="form-control" id="data" name="data" placeholder="Data do evento"
				value="{{$event['data']}}">
		</div>
		<div class="form-group">
			<label for="tipo">Tipo (atividade complementar):</label>
			<input type="text" class="form-control" id="tipo" name="tipo" placeholder="1, 2 ou 3"
				value="{{$event['tipo']}}">
		</div>
		<div class="form-group">
			<label for="duracao">Duração (em horas):</label>
			<input type="number" class="form-control" min="1" step="1" id="duracao" name="duracao" placeholder="Duração do evento"
				value="{{$event['duracao']}}">
		</div>
		<div class="form-group">
			<label for="carga-horaria">Carga horária:</label>
			<input type="number" class="form-control" min="1" step="1" id="carga-horaria" name="carga_horaria" placeholder="Carga horária do evento"
				value="{{$event['carga_horaria']}}">
		</div>
		<div class="form-group">
			<label for="organizador">Organizador:</label>
			<input type="text" class="form-control" id="organizador" name="organizador" placeholder="Organizador do evento"
				value="{{$event['organizador']}}">
		</div>
		<div class="form-group">
			<label for="template">Template:</label>
			<select class="form-control" id="template" name="template">
			@foreach($templates as $template)
				<option value="{{$template}}" {{$event['template'] == $template ? 'selected' : ''}}>{{$template}}</option>
			@endforeach
			</select>
		</div>

		<!-- Método de inserção de participantes -->
		<div class="form-group">
			<label for="metodo-inserir">Inserir participantes:</label>
			<select class="form-control" id="metodo-inserir" name="insertion-method">
				<option value="manual">Manual</option>
				<option value="csv">CSV</option>
			</select>
		</div>

		<div class="container form-group" 
		  	style="border: 1px solid #CBD2D9; padding: 20px; border-radius: 4px">
			<div class="container" id="manual">
				<table id="myTable" class="table order-list">
				    <thead>
				        <tr>
				            <th scope="col" style="width: 30%">Nome</td>
				            <th scope="col" style="width: 23%">CPF</td>
				            <th scope="col" style="width: 17%">Matrícula</td>
				            <th scope="col" style="width: 30%">Email</td>
				        </tr>
				    </thead>
				    <tbody>
				        <tr>
				            <td class="">
				                <input type="text" class="form-control" id="nome-participante" name="nome-participante[0]">
				            </td>
							<td class="">
				            	<input type="text" class="form-control" id="cpf-participante"  name="cpf-participante[0]">
							</td>
							<td class="">
								<input type="text" class="form-control" id="matricula-participante" name="matricula-participante[0]">
							</td>
							<td class="">
								<input type="email" class="form-control" id="email-participante" name="email-participante[0]">
							</td>

				            <td class=""><a class="deleteRow"></a></td>
				        </tr>
				    </tbody>
				    <tfoot>
				        <tr>
				            <td colspan="5" style="text-align: left;">
				                <input type="button" class="btn btn-light btn-block " id="addrow" value="Adicionar"/>
				            </td>
				        </tr>
				        <tr>
				        </tr>
				    </tfoot>
				</table>
			</div>

			<div class="container collapse" id="csv">
				<div class="input-group mb-3">
				  <div class="input-group-prepend">
				    <span class="input-group-text" id="inputGroupFileAddon01">Upload</span>
				  </div>
				  <div class="custom-file">
				    <input type="file" accept=".csv" id="csv-file" class="custom-file-input">
				    <label class="custom-file-label" for="csv-file">Escolher arquivo</label>
				    <input type="hidden" id="file-contents" name="file-contents">
				  </div>
				</div>
			</div>
		</div> <!-- .container -->

		<div class="form-group">
			<button type="submit" class="btn btn-primary">Salvar</button>
		</div>

		@include('partials.errors')

	</form>
</div>
@endsection

@section('custom_scripts')
<script type="text/javascript" src="{{ asset('js/my_functions.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/manage_events.js') }}"></script>
@endsection