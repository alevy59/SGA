@extends('layouts.master')
@section('title', 'SGA - Login do Servidor')

@section('content')
<div class="servant-login">
	<form method="POST" action="/servidor/login" class="form-signin">
		@csrf
		<h1 class="h3 mb-3 font-weight-normal" style="text-align: center;">Login do Servidor</h1>

		<div class="form-group" style="margin-top: 80px">
			<label for="cpf">CPF</label>
			<input type="text" class="form-control" id="cpf" placeholder="CPF" name="cpf" required>
		</div>

		<div class="form-group">
			<label for="password">Senha</label>
			<input type="password" class="form-control" id="password" placeholder="Sua senha do IdUFF" name="password" required>
		</div>

		<div class="form-group">
			<button type="submit" class="btn btn-lg btn-primary btn-block">Entrar</button>
		</div>
		@include('partials.errors')
	</form>
</div>
@endsection

@section('custom_styles')
<!-- Custom Signin Template for Bootsrap -->
<link rel="stylesheet" href="{{asset('css/signin.css')}}">
@endsection

@section('custom_scripts')
<script type="text/javascript" src="{{ asset('js/jquery.mask.min.js') }}"></script>

<script type="text/javascript">
$(document).ready(function() {
    $('#cpf').mask('000.000.000-00', {reverse: true});

    $('form').on('submit', function() {
        $('#cpf').unmask();
    })
});
</script>
@endsection
