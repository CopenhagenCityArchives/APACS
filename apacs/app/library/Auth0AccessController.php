<?php

use Auth0\SDK\Exception\InvalidTokenException;
use Auth0\SDK\Helpers\JWKFetcher;
use Auth0\SDK\Helpers\Tokens\AsymmetricVerifier;
use Auth0\SDK\Helpers\Tokens\TokenVerifier;
use Kodus\Cache\FileCache;

class Auth0AccessController implements IAccessController {

    private $request;
    private $message;
    private $token = null;
    private $tokenInfo = null;

    private $config;

	public function __construct($di) {
        $this->request = $di->get('request');
        $this->config = $di->get('auth0Config');
        $this->message = 'constructed';
    }

	public function AuthenticateUser() {
        $accessToken = $this->GetAccessToken();

        if ($accessToken == false) {
            $this->message = 'Access denied: Missing token.';
            return false;
        }

        $cacheHandler = new FileCache($this->config['cacheLocation'], $this->config['cacheDuration']);
        $jwksUri      = $this->config['issuer'] . '.well-known/jwks.json';

        $jwksFetcher   = new JWKFetcher($cacheHandler, [ 'base_uri' => $jwksUri ]);
        $sigVerifier   = new AsymmetricVerifier($jwksFetcher);
        $tokenVerifier = new TokenVerifier($this->config['issuer'], $this->config['audience'], $sigVerifier);

        // TODO: Can other exceptions be thrown?
        try {
            $this->tokenInfo = $tokenVerifier->verify($accessToken);
            $this->token = $token;
            return true;
        }
        catch(InvalidTokenException $e) {
            var_dump($this->config);
            $this->message = 'Access denied: ' . $e->getMessage();
            return false;
        }
    }

	public function GetMessage() {
        return $this->message;
    }

	public function GetUserId() {
        return $tokenInfo['user_id'];
    }

	public function GetUserName() {
        return $tokenInfo['user_name'];
    }

	public function UserCanEdit($entry) {
        return false;
    }

    public function IsSuperUser() {
        return false;
    }

    private function GetAccessToken() {
        $authHeader = $this->request->getServer('HTTP_AUTHORIZATION');

		//Checks the REDIRECT prefix. It is set if the server redirects the request before landing
		//on the executing page
		if (is_null($authHeader)) {
			$authHeader = $this->request->getServer('REDIRECT_HTTP_AUTHORIZATION');
        }
        
        if (!is_null($authHeader)) {
            $matches = array();
            preg_match('/(B|b)earer (.*)/', $authHeader, $matches);

            if (isset($matches[2])) {
                return $matches[2];
            }
        }

        return false;
    }
}