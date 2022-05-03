<?php

use Combodo\iTop\Core\Authentication\Client\OAuth\OAuthClientProviderAbstract;
use Combodo\iTop\Core\Authentication\Client\OAuth\OAuthClientProviderFactory;

require_once('../approot.inc.php');
require_once(APPROOT.'application/utils.inc.php');
require_once(APPROOT.'/application/application.inc.php');
require_once(APPROOT.'/application/startup.inc.php');

require_once(APPROOT.'/application/loginwebpage.class.inc.php');

$oPage = new JsonPage();
$oPage->SetOutputDataOnly(true);
$aResult = ['status' => 'success', 'data' => []];
try {
	$operation = utils::ReadParam('operation', '');
	
	switch ($operation) {
		case 'get_authorization_url':
			$sProvider = utils::ReadParam('provider', '', false, 'raw');
			$sClientId = utils::ReadParam('client_id', '', false, 'raw');
			$sClientSecret = utils::ReadParam('client_secret', '', false, 'raw');
			$sScope = utils::ReadParam('scope', '', false, 'raw');

			$sAuthorizationUrl = OAuthClientProviderFactory::getVendorProviderForAccessUrl($sProvider, $sClientId, $sClientSecret, $sScope);
			$aResult['data']['authorization_url'] = $sAuthorizationUrl;
			break;
		case 'get_display_authentication_results':
			$sProvider = utils::ReadParam('provider', '', false, 'raw');
			$sRedirectUrl = utils::ReadParam('redirect_url', '', false, 'raw');
			$sClientId = utils::ReadParam('client_id', '', false, 'raw');
			$sClientSecret = utils::ReadParam('client_secret', '', false, 'raw');

			$sRedirectUrlQuery = parse_url($sRedirectUrl)['query'];
			$aOAuthResultDisplayClasses = utils::GetClassesForInterface('Combodo\iTop\Core\Authentication\Client\OAuth\IOAuthClientResultDisplay', '', array('[\\\\/]lib[\\\\/]', '[\\\\/]node_modules[\\\\/]', '[\\\\/]test[\\\\/]'));

			$sProviderClass = "\Combodo\iTop\Core\Authentication\Client\OAuth\OAuthClientProvider".$sProvider;
			$sRedirectUrl = OAuthClientProviderAbstract::GetRedirectUri();
			$aQuery = [];
			parse_str($sRedirectUrlQuery, $aQuery);
			$sCode = $aQuery['code'];
			$oProvider = new $sProviderClass(['clientId' => $sClientId, 'clientSecret' => $sClientSecret, 'redirectUri' => $sRedirectUrl]);
			$oAccessToken = $oProvider->GetVendorProvider()->getAccessToken('authorization_code', ['code' => $sCode]);


			foreach($aOAuthResultDisplayClasses as $sOAuthClass) {
				$aResult['data'][] = $sOAuthClass::GetResultDisplayScript($sClientId, $sClientSecret, $sProvider, $oAccessToken);
			}
	}
}
catch(Exception $e){
	$aResult['status'] = 'error';
}
$oPage->SetData($aResult);
$oPage->output();