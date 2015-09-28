<?php

	$mockConf = [
		'required' => true,
		'dbTableName' => 'insert_table',
		'isMarkable' => true,
		'countPerEntry' => 1, //'one' (exactly one), 'zero' (it is allowed to have none (0 to many)), 'many' (minimum 1 but no max (1 to many))
		'fields' => [
			[
				'name' => 'firstname',
				'type' => 'text', //Could be: text, dropdown (existing data selection mandatory), autocomplete (it is possible to add not already existing data)
				'guiName' => 'Fornavn',
				'defaultValue' => false,
				'placeholder' => 'Fornavn',
				'helpText' => 'Indtast personens fornavn(e). Alt andet end det sidste efternavn',
				'helpLink' => 'kbharkiv.dk/wiki/indtast/begravelsesprotokoller/fornavne',
				'dbFieldName' => 'firstname',
				'required' => true,
				'validationRegularExpression' => false, //Ingen specialtegn eller cifre (kun a-å)
				'validationErrorMessage' => 'Fornavn skal indtastes. Ingen specialtegn tilladt.',
				'maxLength' => 150
			],
			[
				'name' => 'lastname',
				'type' => 'text',
				'guiName' => 'Efternavn',
				'defaultValue' => false,
				'placeholder' => 'Efternavn',
				'helpText' => 'Indtast personens efternavn',
				'helpLink' => 'kbharkiv.dk/help',
				'dbFieldName' => 'lastname',
				'required' => true,
				'validationRegularExpression' => false,
				'validationErrorMessage' => 'Efternavn skal indtastes',				
				'maxLength' => 150
			],
			[
				'name' => 'birthdate',
				'type' => 'text',
				'guiName' => 'Fødselsdato',
				'defaultValue' => '01-01-1700',
				'placeholder' => 'Fødselsdato (dd-mm-åååå)',
				'helpText' => 'Indtast personens fødselsdato. Ved manglende information, tast "01"',
				'helpLink' => 'kbharkiv.dk/foedselsdatoer',
				'dbFieldName' => 'birthdate',
				'required' => false,
				'validationRegularExpression' => false,
				'validationErrorMessage' => 'Indtast en dato mellem 1800 og 1923 i formatet dd-mm-åååå',
				'maxLength' => false
			],
			[
				'name' => 'work',
				'type' => 'autocomplete',
				'guiName' => 'Stilling(er)',
				'defaultValue' => false,
				'placeholder' => 'Hovedpersonens stillinger',
				'helpText' => 'Indtast personens stillinger. Adskil med komma.',
				'helpLink' => 'kbharkiv.dk/indtast/stillinger',
				'dbFieldName' => 'work',
				'required' => false,
				'validationRegularExpression' => false,
				'validationErrorMessage' => false,
				'maxLength' => false
			]				
		]
	];
return $mockConf;