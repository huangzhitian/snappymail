<?php

namespace OCA\SnappyMail\Controller;

use OCA\SnappyMail\Util\SnappyMailHelper;
use OCA\SnappyMail\ContentSecurityPolicy;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;

class PageController extends Controller
{
	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index()
	{
		$config = \OC::$server->getConfig();

		$bAdmin = false;
		if (!empty($_SERVER['QUERY_STRING'])) {
			SnappyMailHelper::loadApp();
			$bAdmin = \RainLoop\Api::Config()->Get('security', 'admin_panel_key', 'admin') == $_SERVER['QUERY_STRING'];
			if (!$bAdmin) {
				SnappyMailHelper::startApp(true);
			}
		}

		$cspManager = \OC::$server->getContentSecurityPolicyNonceManager();
		if (\method_exists($cspManager, 'browserSupportsCspV3') && !$cspManager->browserSupportsCspV3()) {
			exit('SnappyMail does not work in this browser due to a <a href="https://github.com/the-djmaze/snappymail/issues/633">bug in Nextcloud</a>.
				<br/>You must <a href="../../settings/admin/additional">turn on iframe mode</a>');
		}

		if (!$bAdmin && $config->getAppValue('snappymail', 'snappymail-no-embed')) {
			\OC::$server->getNavigationManager()->setActiveEntry('snappymail');
			\OCP\Util::addScript('snappymail', 'snappymail');
			\OCP\Util::addStyle('snappymail', 'style');
			SnappyMailHelper::startApp();
			$response = new TemplateResponse('snappymail', 'index', [
				'snappymail-iframe-url' => SnappyMailHelper::normalizeUrl(SnappyMailHelper::getAppUrl())
					. (empty($_GET['target']) ? '' : "#{$_GET['target']}")
			]);
			$csp = new ContentSecurityPolicy();
			$csp->addAllowedFrameDomain("'self'");
			$response->setContentSecurityPolicy($csp);
			return $response;
		}

		\OC::$server->getNavigationManager()->setActiveEntry('snappymail');

		\OCP\Util::addStyle('snappymail', 'embed');

		SnappyMailHelper::startApp();
		$oConfig = \RainLoop\Api::Config();
		$oActions = $bAdmin ? new \RainLoop\ActionsAdmin() : \RainLoop\Api::Actions();
		$oHttp = \MailSo\Base\Http::SingletonInstance();
		$oServiceActions = new \RainLoop\ServiceActions($oHttp, $oActions);
		$sAppJsMin = $oConfig->Get('labs', 'use_app_debug_js', false) ? '' : '.min';
		$sAppCssMin = $oConfig->Get('labs', 'use_app_debug_css', false) ? '' : '.min';
		$sLanguage = $oActions->GetLanguage(false);

		$params = [
			'Admin' => $bAdmin ? 1 : 0,
			'LoadingDescriptionEsc' => \htmlspecialchars($oConfig->Get('webmail', 'loading_description', 'SnappyMail'), ENT_QUOTES|ENT_IGNORE, 'UTF-8'),
			'BaseTemplates' => \RainLoop\Utils::ClearHtmlOutput($oServiceActions->compileTemplates($bAdmin)),
			'BaseAppBootScript' => \file_get_contents(APP_VERSION_ROOT_PATH.'static/js'.($sAppJsMin ? '/min' : '').'/boot'.$sAppJsMin.'.js'),
			'BaseAppBootScriptNonce' => $cspManager->getNonce(),
			'BaseLanguage' => $oActions->compileLanguage($sLanguage, $bAdmin),
			'BaseAppBootCss' => \file_get_contents(APP_VERSION_ROOT_PATH.'static/css/boot'.$sAppCssMin.'.css'),
			'BaseAppThemeCssLink' => $oActions->ThemeLink($bAdmin),
			'BaseAppThemeCss' => \preg_replace(
				'/\\s*([:;{},]+)\\s*/s',
				'$1',
				$oActions->compileCss($oActions->GetTheme($bAdmin), $bAdmin)
			)
		];

//		\OCP\Util::addScript('snappymail', '../app/snappymail/v/'.APP_VERSION.'/static/js'.($sAppJsMin ? '/min' : '').'/boot'.$sAppJsMin);

		// Nextcloud html encodes, so addHeader('style') is not possible
//		\OCP\Util::addHeader('style', ['id'=>'app-boot-css'], \file_get_contents(APP_VERSION_ROOT_PATH.'static/css/boot'.$sAppCssMin.'.css'));
		\OCP\Util::addHeader('link', ['type'=>'text/css','rel'=>'stylesheet','href'=>\RainLoop\Utils::WebStaticPath('css/'.($bAdmin?'admin':'app').$sAppCssMin.'.css')], '');
//		\OCP\Util::addHeader('style', ['id'=>'app-theme-style','data-href'=>$params['BaseAppThemeCssLink']], $params['BaseAppThemeCss']);

		$response = new TemplateResponse('snappymail', 'index_embed', $params);

		$response->setContentSecurityPolicy(new ContentSecurityPolicy());

		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function appGet()
	{
		SnappyMailHelper::startApp(true);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function appPost()
	{
		SnappyMailHelper::startApp(true);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function indexPost()
	{
		SnappyMailHelper::startApp(true);
	}
}
