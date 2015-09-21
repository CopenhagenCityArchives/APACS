<?php


function GetEntryConfMock()
{
	return array(
		'required' => true,
		'dbTableName' => 'indexdata_test',
		'isMarkable' => true,
		'countPerEntry' => 1, //'one' (exactly one), 'zero' (it is allowed to have none (0 to many)), 'many' (minimum 1 but no max (1 to many))
		'fields' => [
			'firstname' => [
				'type' => 'text', //Could be: text, dropdown (existing data selection mandatory), autocomplete (it is possible to add not already existing data)
				'name' => 'Fornavn',
				'defaultValue' => false,
				'placeholder' => 'Fornavn',
				'helpText' => 'Indtast personens fornavn(e). Alt andet end det sidste efternavn',
				'helpLink' => 'kbharkiv.dk/wiki/indtast/begravelsesprotokoller/fornavne',
				'dbFieldName' => 'firstname',
				'required' => true,
				'validation' => false, //Ingen specialtegn eller cifre (kun a-å)
				'validationErrorMessage' => 'Fornavn skal indtastes. Ingen specialtegn tilladt.',
				'maxLength' => 150
			],
			'lastname' => [
				'type' => 'text',
				'name' => 'Efternavn',
				'defaultValue' => false,
				'placeholder' => 'Efternavn',
				'helpText' => 'Indtast personens efternavn',
				'helpLink' => 'kbharkiv.dk/help',
				'dbFieldName' => 'lastname',
				'required' => true,
				'validation' => false,
				'validationErrorMessage' => 'Efternavn skal indtastes',				
				'maxLength' => 150
			],
			'birthdate' => [
				'type' => 'text',
				'name' => 'Fødselsdato',
				'defaultValue' => '01-01-1700',
				'placeholder' => 'Fødselsdato (dd-mm-åååå)',
				'helpText' => 'Indtast personens fødselsdato. Ved manglende information, tast "01"',
				'helpLink' => 'kbharkiv.dk/foedselsdatoer',
				'dbFieldName' => 'birthdate',
				'required' => false,
				'validation' => 'REGEXP for date values between 1800 and 1923',
				'validationErrorMessage' => 'Indtast en dato mellem 1800 og 1923 i formatet dd-mm-åååå',
				'maxLength' => false
			],
			'work' => [
				'type' => 'autocomplete',
				'name' => 'Stilling(er)',
				'defaultValue' => false,
				'placeholder' => 'Hovedpersonens stillinger',
				'helpText' => 'Indtast personens stillinger. Adskil med komma.',
				'helpLink' => 'kbharkiv.dk/indtast/stillinger',
				'dbFieldName' => 'work',
				'required' => false,
				'validation' => false,
				'validationErrorMessage' => false,
				'maxLength' => false
			]				
		]
	);
}