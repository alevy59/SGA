function buscarDisciplinas(route = '/ajuste', selected = null)
{
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    //em 'admin/ajuste' o select do período é: $('select[name=periodo]')
    //em '/ajuste' é $('.periodo')

    var periodo, colDisciplinas;

    if(route === '/ajuste')
    {
		var row = $(selected).parents(':eq(1)');
		periodo = row.find('.periodo :selected').text();
		colDisciplinas = row.find('.disciplina');
    }

    else if(route === '/admin/ajuste') {
    	//console.log(route);

    	periodo = $('select[name=periodo] :selected').text();
    	colDisciplinas = $('select[name=disciplina]');
    }
    
	//Limpar conteudo da coluna de disciplinas
	colDisciplinas.html('');

	//Coletar as disciplinas do periodo selecionado
	$.ajax({
		url: '/subjects',
		type: 'POST',
		data: {
			'periodo' : periodo
		},
		dataType: 'JSON',
		success: function(response) {
			if(route === '/admin/ajuste') colDisciplinas.append('<option value="Todos">Todas</option>');

			for(var i = 0; i < response.length; ++i) {
				colDisciplinas.
				append('<option>' + response[i]['name'] + '</option>');
			}
		},
		error: function(data) {
			console.log("Error: " + JSON.stringify(data));
		}
	});
}
function sendFilterParams(form) 
{
	//Remover o nome dos inputs desmarcados
	var checkboxes = form.find('input[type=checkbox]').each(function() {
		var input = $(this);
		var inputSiblings = $(this).siblings('.input-filtros');

		//Não está marcada ou está marcada 
		if(! input.is(':checked') || (input.is(':checked') && (inputSiblings.val() === 'Todos'))) {
			console.log(inputSiblings.val())
			inputSiblings.removeAttr('name');
		}
		//TODO Remover nome de filtros com select em 'Todos'
	});
}
function bulkSelect()
{
	console.log('bulkSelect');
	liberarDeferir();
	var checkLinhas = $('.checkable').filter(function(){
		var row = $(this).closest('tr');
		if(row.css('display') === 'none') return false;
		else return true;
	});

	if($(this).is(':checked')) {
		//excluir os não visiveis

		if(!checkLinhas.is(':checked')) {
			checkLinhas.prop('checked', true);
		}
	}
	else checkLinhas.prop('checked', false);
}
function liberarDeferir()
{
	var deferirBtn = $('#deferir');
	var elements = $('#requerimentos').find('input[type=checkbox]:checked');
	elements.length >= 1 ? deferirBtn.prop('disabled', false) : deferirBtn.prop('disabled', true);
}
function toggleActionButtons()
{
	var acoesBtns = $('#acoes-btns button');
	var elements = $('#requerimentos').find('input[type=checkbox]:checked');
	if(elements.length == 0) acoesBtns.prop('disabled', true);
}
function processarAjuste(acao)
{	
	var route = '/admin/ajuste/' + $(acao).attr('id');
	//Se o botão de ação clicado for deferir, 
	console.log(route);


	var form = $('form');
	form.find('tbody tr').each(function() {
		var check = $(this).find('input[type=checkbox]');

		if(!check.is(':checked')) {
			$(this).find('input[type=hidden]').removeAttr('name');
		}
	});

	//Implementar Ajax
	$.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

	$.ajax({
			url: route,
			type: 'POST',
			data: form.serialize(),
			success: function(response) {
				//console.log('Success: ' + JSON.stringify(response));
				atualizarTabela();
			},
			error: function(data) {
				console.log("Error: " + JSON.stringify(data));
			}
	});
}
function atualizarTabela()
{ 
    $("#requerimentos table").load(window.location.href + '#requerimentos table');
}