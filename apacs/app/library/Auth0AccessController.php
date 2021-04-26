<?php
use Auth0\SDK\Exception\CoreException;
use Auth0\SDK\Exception\ApiException;

use Auth0\SDK\Exception\InvalidTokenException;
use Auth0\SDK\Helpers\JWKFetcher;
use Auth0\SDK\Helpers\Tokens\AsymmetricVerifier;
use Auth0\SDK\Helpers\Tokens\SymmetricVerifier;

use Auth0\SDK\Helpers\Tokens\TokenVerifier;
use Kodus\Cache\FileCache;

class Auth0AccessController implements IAccessController {

    private $request;
    private $message;
    private $token = null;
    private $userInfo = null;

    private $config;

	public function __construct($di) {
        $this->request = $di->get('request');
        $this->config = $di->get('auth0Config');

        if(!isset($this->config['tokenSalt'])){
            throw new Exception("tokenSalt not set in aut0Config. Cannot continue.");
        }

        $this->userInfo = [];
    }

    private function getWebPage($url) {
		$options = array(
			CURLOPT_RETURNTRANSFER => true, // return web page
			CURLOPT_HEADER => false, // don't return headers
			CURLOPT_FOLLOWLOCATION => true, // follow redirects
			CURLOPT_MAXREDIRS => 10, // stop after 10 redirects
			CURLOPT_ENCODING => "", // handle compressed
			CURLOPT_USERAGENT => "test", // name of client
			CURLOPT_AUTOREFERER => true, // set referrer on redirect
			CURLOPT_CONNECTTIMEOUT => 10, // time-out on connect
            CURLOPT_TIMEOUT => 10, // time-out on response
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->token]
			
		);

        $ch = curl_init($url);
		curl_setopt_array($ch, $options);
		
		$content = curl_exec($ch);
		$err = curl_error($ch);
		if ($err) {
			$this->message = 'Could not contact auth server: ' . $err;
			return false;
		}

		if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == '401') {
			$this->message = 'Invalid token (401 from auth server)';
			curl_close($ch);
            return false;
		}

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == '429') {
			$this->message = 'Too many requests (429 from auth server)';
			curl_close($ch);
            return false;
		}

        if(curl_getinfo($ch, CURLINFO_HTTP_CODE) != '200'){
            $this->message = "Could not get data from auth server. HTTP code: " . curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return false;
        }

		curl_close($ch);

		return $content;
	}

	public function AuthenticateUser() {
        $accessToken = $this->GetAccessToken();

        if ($accessToken == false) {
            $this->message = 'Access denied: Missing token.';
            return false;
        }

        //TODO: Caching of tokens should be considered
        $cacheHandler = new FileCache($this->config['cacheLocation'], $this->config['cacheDuration']);

        $jwksUri = $this->config['jwks_uri'];
        $jwksFetcher   = new JWKFetcher($cacheHandler, [ 'base_uri' => $jwksUri ]);
        $sigVerifier   = new AsymmetricVerifier($jwksFetcher);
        $tokenVerifier = new TokenVerifier($this->config['issuer'], $this->config['audience'], $sigVerifier);

        try {
            $this->tokenInfo = $tokenVerifier->verify($accessToken);
            $this->token = $accessToken;
        }
        catch(InvalidTokenException $e) {
            $this->message = 'Access denied: ' . $e->getMessage();
            return false;
        }
        catch(Exception $exp){
            $this->message = 'Access denied: ' . $exp->getMessage();
            return false;
        }

        $synchronizeUser = false;
        if(!$this->GetUserInfoFromDB()){
            if($this->GetUserInfoFromAuth0()){
                $synchronizeUser = true;
                $this->SaveUserInfoInDB();
            }
            else{
                return false; 
            }
        }
        
        // Find APACS user based on AUTH0 user id
        $apacsUser = Users::findFirst([
            'conditions' => 'auth0_user_id = :sub:',
            'bind' => ['sub' => $this->userInfo['auth0_user_id']]
        ]);
        
        if (!$apacsUser){
            throw new Exception("Couldn't find user in APACS users table");
        }

        // Use APACS user id as user id
        $this->userInfo['id'] = $apacsUser->id;

        // If Auth0 and APACS username does not match, syncronise APACS info
        if($apacsUser->username !== $this->userInfo['username'] && $synchronizeUser){
            $this->SyncronizeUser();
        }
        
        return true;
    }

    private function hashToken($token)
    {
        return hash('sha256',$token);
    }

    private function GetUserInfoFromDB(){
        $encrypted_token = $this->hashToken($this->token);

        // Validate token in db
        $dbToken = Tokens::findFirst(['conditions' => 'expires > ' . time() . ' AND token = \'' . $encrypted_token . '\'']);
        
        if($dbToken){
            // Map Auth0 nickname to APACS username
            $this->userInfo['username'] = $dbToken->user_name;
            // Auth0 user id comes from "sub"
            $this->userInfo['auth0_user_id'] = $dbToken->auth0_user_id;
            return true;
        }

        $this->message = 'No token found in database';

        return false;
    }

    private function SaveUserInfoInDB(){
        $token = new Tokens();
        $token->token = $this->hashToken($this->token);
        $token->user_name = $this->userInfo['username'];
        $token->auth0_user_id = $this->userInfo['auth0_user_id'];
        // Let user info expire after 1 hour
        $token->expires = time() + 60 * 60;
		
        $token->save();
    }

    private function GetUserInfoFromAuth0(){
        // Get userinfo (id_token) with access token from /userinfo endpoint at Auth0
        $auth0_userinfo = json_decode($this->getWebPage('https://kbharkiv.eu.auth0.com/userinfo'), true);

        if($auth0_userinfo == false){
            $this->message = 'Could not get userinfo from auth server: ' . $this->message;
            return false;
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->message = 'Could not decode response from auth server';
            return false;
        }

        // Map Auth0 nickname to APACS username
        $this->userInfo['username'] = $auth0_userinfo['nickname'];
        // Auth0 user id comes from "sub"
        $this->userInfo['auth0_user_id'] = $auth0_userinfo['sub'];

        return true;
    }

    private function SyncronizeUser() {
		$user = new Users();
		$user->save($this->userInfo);
	}

	public function GetMessage() {
        return $this->message;
    }

	public function GetUserId() {
        return $this->userInfo['id'];
    }

	public function GetUserName() {
        return $this->userInfo['username'];
    }

	public function UserCanEdit($entry) {
		/**
		 * Who can edit when:
		 * 1) Users who created the post, at any time, and super users, at any time.
		 * No relevant by the time (commented out in the code):
		 * 2) Super users if no error reports are present
		 * 3) Superusers, if an error report are present, a specified amount of time after the error has been reported
		 */

        $attemptingUser = $this->GetUserId();

		//Creating user can always edit
		if ($entry->users_id == $attemptingUser || $this->IsSuperUser($entry->tasks_id)) {
			return true;
        }
                
        return false;
    }

	/**
	 * Check if the authorized user is a superuser for:
	 *   1) The specified task, if given, or
	 *   2) Any task, if none is given.
	 * @param int taskId The id to check for superuser status for.
	 * @return bool Is the authorized user superuser.
	 */ 
	public function IsSuperUser($taskId = null) {
		if ($taskId === null) {
			return SuperUsers::count([
				"conditions" => "users_id = :userId:",
				"bind" => [
					"userId" => $this->GetUserId()
				]
			]) > 0;
		} else {
			return SuperUsers::count([
				"conditions" => "users_id = :userId: AND tasks_id = :taskId:",
				"bind" => [
					"userId" => $this->GetUserId(),
					"taskId" => $taskId
				]
			]) > 0;
		}
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