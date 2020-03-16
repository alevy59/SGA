<?php

namespace App\Lib;

use Illuminate\Support\Arr;
use App\Interfaces\IdUFFCrawlerInterface as Crawler;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use duzun\hQuery;
use Illuminate\Support\Facades\Log;

class IdUFFCrawler implements Crawler
{
	private $base_uri;
	private $timeout;
	private $client;
	private $xpath;
	private $courseId;
	private $hQueryDom;
	private $keyNames;
	private $enrolment;
	public $failed;
	public $bag;
	public $jar;
	
	public function __construct($domDocument)
	{
		$this->base_uri = 'https://app.uff.br/iduff/';
		$this->timeout = 20;
		$this->dom = $domDocument;
		$this->jar = new CookieJar();
		$this->courseId = '^\d{3}023';
		$this->failed = true;
		$this->enrolment = collect();
		$this->bag = collect();

		$this->client = new Client([
			'base_uri' => $this->base_uri,
			'timeout' => $this->timeout,
			'cookies' => true,
		]);

		$this->keyNames = collect([
			'Desdobramento' => 'degree',
			'Habilitação' => 'degree_type',
			'Enfase' => 'emphasis',
			'Currículo' => 'curriculum',
			'Carga horária obrigatória total' => 'total_workload',
			'Carga Horária Obtida' => 'obtained_workload',
			'Carga Horária Cursada' => 'attended_workload',
			'Percentual Concluído' => 'percentage_completed',
			'Coeficiente de Rendimento' => 'performance_coeficient',
			'Situação Atual' => 'current_status',

		]);
	}

	public function verifyCredentials($request, $path = 'login.uff')
	{
		$response = $this->client->request('GET', 'login.uff');
		$response = $this->client->request('POST', $path,
			[ 
				'form_params' => [
					"login" => "login",
					"login:id" => $request->cpf,
					"login:senha" => $request->password,
					"login:btnLogar" => "Logar",
					'javax.faces.ViewState' => 'j_id1'
				],
			]);
		
		$html = (string) $response->getBody();
		$this->hQueryDom = hQuery::fromHTML($html);
		$error = $this->hQueryDom->find('.form-messages-error');
		return !$error ? true : false;
	}

	public function attemptLogin($path, $request)
	{
		$response = $this->client->request('GET', 'login.uff');
		$response = $this->client->request('POST', $path,
			[ 
				'form_params' => [
					"login" => "login",
					"login:id" => $request->cpf,
					"login:senha" => $request->password,
					"login:btnLogar" => "Logar",
					'javax.faces.ViewState' => 'j_id1'
				],
			]);
		$response = $this->client->request('GET', 'privado/home.uff');
		
		$html = (string) $response->getBody();
		libxml_use_internal_errors(true);


		$this->hQueryDom = hQuery::fromHTML($html);

		$this->dom->loadHTML($html);
		$this->dom->saveHTML();
		$this->xpath = new \DOMXpath($this->dom);

		//Try to log the user in
		if($this->login()) {
			//Not in profile page 
			//
			//
			if($this->inEnrolmentSelectionPage()) {
				//But is not in profile page because of multiple enrolments
				$enrolments = $this->getEnrolments();
				$enrolment = $this->getValidEnrolment($enrolments);
				
				if($enrolment->count() == 1) {
					//Test
					$this->enrolment->put('number', $enrolment->first());
					$this->enrolment->put('index', array_keys($enrolment->toArray())[0]);
					$this->enrolment->put('selected', false);
					//Test
					
					//Let's check for a valid one
					$this->bag['enrolment_number'] = $enrolment->first();
					//$index = array_keys($enrolment->toArray())[0];
				} else {
					//Has logged in but enrolment number does not apply
					$this->failed = true;
					return;
				}
			}
			//Inspect single enrolment students

		} else {
			//Something went wrong with cpf or password
			$this->failed = true;
			return;
		}
		$progressPage = $this->toProgressPage();
		$historyPage = $this->toHistoryPage();

		$personalDataPage = $this->toPersonalDataPage();

		$this->getProgressData($progressPage);
		$this->getPersonalData($personalDataPage);
		$this->getHistoryData($historyPage);


		$this->bag = $this->renameKeys($this->bag, $this->keyNames);
		$this->bag->when(!$this->bag->has('attended_workload'), function($bag) {
			$bag->put('attended_workload', 0);
		});

		$hasAllAttributes = $this->matchAttributes($this->bag, $this->keyNames);

		if(!$hasAllAttributes)  {
			$this->failed = true;
			Log::channel('slack')->info($request, get_object_vars($this));
			return;
		}
		$this->failed = false;
		return;
	}
	public function matchAttributes($source, $target)
	{
		$diff = [];
		$target->map(function($value) use ($source, &$diff) {
			if(!$source->has($value)) {
				array_push($diff, $value);
			}
		});
		return count($diff) > 0 ? false : true;
	}

	public function renameKeys($bag, $source)
	{
		$bag->map(function($value, $oldKey) use ($source, $bag) {
			if($source->has($oldKey)) {
				$newKey = $source->get($oldKey);
				$bag->forget($oldKey);
				$bag->put($newKey, $value);
			} else return $value;
		});
		return $bag;
	}

	public function getHistoryData($page)
	{
		$this->hQueryDom = hQuery::fromHTML($page);
		$cells = $this->hQueryDom->find('.labelNegrito tr td');
		$data = collect();
		$i = 0;
		foreach($cells as $cell) {
			if($i % 2 == 0) {
				$title = preg_replace('/[\r\n\:]+|\s{2}+|\s\*+/', '', $cell->text());
				$value = preg_replace('/\s/', '', $cells[$i+1]);
				$data->put($title, $value);
			}
			++$i;
		}
		$this->bag = $this->bag->merge($data);
	}


	public function toHistoryPage()
	{
		if($this->enrolment->has('index') && !$this->enrolment->get('selected')) {
			$this->selectEnrolment();
		}

		$response = $this->client
			->request('GET', 'privado/declaracoes/private/historico.uff');
		$html = (string) $response->getBody();
		return $html;

	}
	public function selectEnrolment()
	{
		$viewState = $this->hQueryDom->find('input[name="javax.faces.ViewState"]')->val();
		$response = $this->client->request('POST', 'privado/home.uff', 
			[
				'form_params' => [
					"templatePrincipal2" => "templatePrincipal2",
					"javax.faces.ViewState"=> $viewState,
					"templatePrincipal2:j_id205:{$this->enrolment->get('index')}:j_id207" => "templatePrincipal2:j_id205:{$this->enrolment->get('index')}:j_id207",
				],
			]
		);
		$this->enrolment->put('selected', true);
	}

	public function toPersonalDataPage()
	{
		if($this->enrolment->has('index') && !$this->enrolment->get('selected')) {
			$this->selectEnrolment();
		}

		$response = $this->client
			->request('GET', 'privado/iduff/identificacao/editarIdentificacao.uff');
		$html = (string) $response->getBody();
		return $html;
	}
	
	public function getPersonalData($page)
	{
		$this->hQueryDom = hQuery::fromHTML($page);

		$phoneInputs = $this->hQueryDom->find('table .colunasDosDadosIdentificacao input');

		$data = [];
		foreach($phoneInputs as $input) {
			array_push($data, $input->val());
		}
		$data = collect($data)->slice(4,4)->values();

		$phoneNumber = ($data[0] && $data[1]) ? $data[0].$data[1] : '';
		$cellPhoneNumber = ($data[2] && $data[3]) ? $data[2].$data[3] : '';
		$this->bag
			->put('phone_number', $phoneNumber)
			->put('cell_phone_number', $cellPhoneNumber);

		$allInputs = $this->hQueryDom->find('input');

		$emails = [];
		foreach($allInputs as $input) {
			$match = preg_match('/^[a-z0-9_.]+@[a-z0-9]+\.[a-z]+(\.[a-z]+)?$/i', $input->val(), $matches);
			if($match) array_push($emails, $matches[0]);
		}
		$emailSecondary = array_key_exists(1, $emails) ? $emails[1] : '';
		if(count($emails) > 1) {
			$this->bag->put('email_primary', $emails[0])
				->put('email_secondary', $emailSecondary);
		}
	}
	
	public function getProgressData($page)
	{
		$this->dom->loadHTML($page);

		//Testing hQueryDom
		$this->hQueryDom = hQuery::fromHTML($page);
		//
		$this->dom->saveHTML();
		$this->xpath = new \DOMXpath($this->dom);

		$this->getHeaderData();
		$this->getAcademicData();
	}

	public function getAcademicData()
	{
		//Carga horária cursada
		$tds = $this->hQueryDom->find('form#formSuporteIntegralizacao span table:first-child td');

		$cells = [];

		foreach($tds as $td) {
			//var_dump($td->text());
			array_push($cells, $td->text());
		}

		$cells = collect(array_slice($cells, 1));

		$data = collect();

		$cells->map(function($item, $key) use ($cells, &$data) {
			if($key % 2 == 0) {
				$title = preg_replace('/[\r\n\:]+|\s{2}+|\s\*+/', '', $item);
				$value = preg_replace('/\s|%/', '', $cells[$key+1]);
				
				$data->put($title, $value);
			}
		});

		$this->bag = $this->bag->merge($data);
	}

	public function getHeaderData()
	{
		$header = $this->hQueryDom->find('#header');
		$tds = $header->find('td');
		$headerData = [];
		foreach($tds as $td) {array_push($headerData, $td->text());}

		$headerData = collect($headerData)->slice(2,3)->values();

		$name = trim($headerData[0]);
		$name = $this->splitName($name);
		$this->bag->put('first_name', $name[0]);
		$this->bag->put('last_name', $name[1]);
		
		$cpf = preg_match('/\d+/', $headerData[1], $matches);
		$this->bag->put('cpf', $matches[0]);

		$enrolment_number = preg_match('/\d+/', $headerData[2], $matches);
		$this->bag->put('enrolment_number', $matches[0]);
	}

	public function splitName($name) {
		$name = trim($name);
		$first_name = preg_match('/(^[\w]*)\s/', $name, $matches);
		$first_name = $matches[1];
		$last_name = (strpos($name, ' ') === false) ? '' : trim(preg_replace("/{$first_name}/", '', $name));
		return array($first_name, $last_name);
	}

	public function toProgressPage()
	{
		if($this->enrolment->has('index') && !$this->enrolment->get('selected')) {
			$this->selectEnrolment();
		}

		$response = $this->client
			->request('GET', 'privado/academico/aluno/curriculo/suporteAlunoCurriculoIntegralizado.uff');
		$html = (string) $response->getBody();
		return $html;

	}

	public function getValidEnrolment($enrolments)
	{
		$value = $enrolments->filter(function($e) {
			return preg_match("/{$this->courseId}/", $e);
		});
		return $value;
	}
	
	public function getEnrolments()
	{
		$form = $this->dom->getElementById('templatePrincipal2');
		$nodeList = $this->xpath
			->query('//li[@class="extraUserActions"]/a/text()', $form);
		$enrolments = collect(Arr::pluck(\iterator_to_array($nodeList), 'data')) 
			->map(function($e) {
				return preg_replace('/Aluno - /', '', $e);
			});
		return $enrolments;
	}
	public function inEnrolmentSelectionPage()
	{
		$form = $this->dom->getElementById('templatePrincipal2');
		$nodeList = $this->xpath
			->query('//text()[contains(.,"Tipo de acesso")]', $form);
		if($nodeList->length > 0) {
			return true;
		}
		return false;

	}
	public function login()
	{
		return $this->pageDoesNotHave('//form[@id="login"]');
	}
	public function inProfilePage()
	{
		return $this->pageHas('//img[@id="foto_perfil"]');
	}
	public function hasMultipleEnrolments()
	{
		$form = $this->dom->getElementById('templatePrincipal2');
		$nodeList = $this->xpath->query('//text()[contains(.,"Tipo de acesso")]', $form);
		if($nodeList->length > 0) {
			return true;
		}
		return false;
	}
	public function pageDoesNotHave($selector)
	{
		if($this->xpath->query($selector)->length == 0) {
			return true;
		}
		return false;
	}
	public function pageHas($selector)
	{
		if($this->xpath->query($selector)->length > 0) {
			return true;
		}
		return false;
	}
	public function getHtml()
	{
		//echo $this->html;
	}
}
