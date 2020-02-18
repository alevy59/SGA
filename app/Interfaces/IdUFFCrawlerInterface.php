<?php

namespace App\Interfaces;


interface IdUFFCrawlerInterface
{
	//public function attemptLogin($path, $cpf, $password);
	public function attemptLogin($path, $request);
	public function getHtml();
	public function pageHas($selector);
	public function login();
	//public function enrolmentNumberHas($number);
}
