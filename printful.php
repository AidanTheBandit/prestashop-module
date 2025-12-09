<?php
/**
 * PrestaShop module for Printful
 *
 * @author    Printful
 * @copyright 2019 Printful
 * @license   GPL 3 license see LICENSE.txt
 */

require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/src/services/web/WebserviceSpecificManagementPrintful.php';
require_once dirname(__FILE__) . '/controllers/admin/PrintfulConnectController.php';
require_once dirname(__FILE__) . '/controllers/admin/PrintfulConnectReturnController.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class Printful
 */
class Printful extends Module
{
    const ENV_DEV = 'dev';
    const ENV_PROD = 'prod';

    // active env
    const ENV = self::ENV_PROD;

    // PF Configuration keys
    const CONFIG_PRINTFUL_SERVICE_KEY_ID = 'PRINTFUL_SERVICE_KEY_ID';
    const CONFIG_PRINTFUL_IDENTITY = 'PRINTFUL_IDENTITY';
    const CONFIG_PRINTFUL_API_KEY = 'PRINTFUL_API_KEY';
    const CONFIG_PRINTFUL_OAUTH_KEY = 'PRINTFUL_OAUTH_KEY';
    const CONFIG_PRINTFUL_PLUGIN_VERSION = 'PRINTFUL_PLUGIN_VERSION';
    const CONFIG_PRINTFUL_SAPI_NAME = 'PRINTFUL_SAPI_NAME';
    const CONFIG_PRINTFUL_VERSION_CHECK_DATA = 'PRINTFUL_VERSION_CHECK_DATA';

    // Common Configuration keys
    const CONFIG_WEBSERVICE = 'PS_WEBSERVICE';
    const CONFIG_WEBSERVICE_CGI_HOST = 'PS_WEBSERVICE_CGI_HOST';

    // Used in uninstall
    const PRINTFUL_CONFIGURATION_KEYS = [
        self::CONFIG_PRINTFUL_SERVICE_KEY_ID,
        self::CONFIG_PRINTFUL_IDENTITY,
        self::CONFIG_PRINTFUL_API_KEY,
        self::CONFIG_PRINTFUL_OAUTH_KEY,
        self::CONFIG_PRINTFUL_PLUGIN_VERSION,
        self::CONFIG_PRINTFUL_SAPI_NAME,
        self::CONFIG_PRINTFUL_VERSION_CHECK_DATA,
    ];

    // Controller names
    const CONTROLLER_IMPROVE = 'IMPROVE';
    const CONTROLLER_PRINTFUL = 'PrintfulHome';
    const CONTROLLER_DASHBOARD = 'PrintfulDashboard';
    const CONTROLLER_ORDERS = 'PrintfulOrders';
    const CONTROLLER_CONNECT = 'PrintfulConnect';
    const CONTROLLER_CONNECT_RETURN = 'PrintfulConnectReturn';

    // Printful host
    const PRINTFUL_HOST = 'https://www.printful.com/';
    const PRINTFUL_HOST_DEV = 'https://www.printful.test/';

    // Printful API host
    const PRINTFUL_API_HOST = 'https://api.printful.com';
    const PRINTFUL_API_HOST_DEV = 'https://api.printful.test';

    const PRINTFUL_PLUGIN_PATH = 'download-plugin/prestashop';

    /**
     * Service registry to cache service instances
     * @var array
     */
    private static $serviceRegistry = array();

    /**
     * Printful constructor.
     */
    public function __construct()
    {
        $this->name = 'printful';
        $this->tab = 'others';
        $this->version = '2.1';
        $this->author = 'Printful';
        $this->need_instance = 1;

        $this->ps_versions_compliancy = [
            'min' => '1.7.6',
            'max' => '9.99.99',
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Printful: Print-on-demand dropshipping');
        $this->description = $this->l('Use Printful to design and sell your own shirts, hats, bags and more! We will handle inventory, production, and shipping, so you can focus on building your store.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');


        if (!self::isConnected()) {
            $this->warning = $this->l('Your store is not connected to Printful');
        } else {
            /** @var Printful\services\VersionValidatorService $versionValidator */
            $versionValidator = self::getService(Printful\services\VersionValidatorService::class);
            $data = $versionValidator->validateVersion($this->version);
            if ($data && !$data->isValidVersion) {
                $this->warning = $this->l('Your current Printful module is out of date');
            }
        }

        $this->module_key = 'f9dc46e8f45d06a7ee5ad692ff89eb15';
    }

    /**
     * Install module
     * @return bool
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        try {
            /** @var Printful\services\InstallService $installService */
            $installService = self::getService(Printful\services\InstallService::class);

            return $installService->install($this);
        } catch (Throwable $throwable) {
            // notify PF about failed install?
            return false;
        }
    }

    /**
     * Uninstall module
     * @return bool
     */
    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        try {
            /** @var Printful\services\UninstallService $uninstallService */
            $uninstallService = self::getService(Printful\services\UninstallService::class);

            return $uninstallService->uninstall($this);
        } catch (Exception $exception) {
            // notify PF about failed uninstall?
            return false;
        }
    }

    /**
     * @param string $className
     * @return mixed|object
     * @throws \PrestaShop\PrestaShop\Adapter\CoreException
     */
    public static function getService($className)
    {
        // Check if service is already instantiated in registry
        if (isset(self::$serviceRegistry[$className])) {
            return self::$serviceRegistry[$className];
        }

        // Try legacy ServiceLocator for PrestaShop < 9
        if (class_exists('Adapter_ServiceLocator')) {
            $service = Adapter_ServiceLocator::get($className);
            self::$serviceRegistry[$className] = $service;
            return $service;
        } elseif (class_exists('PrestaShop\PrestaShop\Adapter\ServiceLocator')) {
            $service = PrestaShop\PrestaShop\Adapter\ServiceLocator::get($className);
            self::$serviceRegistry[$className] = $service;
            return $service;
        }

        // Fallback for PrestaShop 9+: manually instantiate services
        $service = self::createService($className);
        self::$serviceRegistry[$className] = $service;
        return $service;
    }

    /**
     * Manually create service instances for PrestaShop 9+ compatibility
     * @param string $className
     * @return mixed|object
     * @throws Exception
     */
    private static function createService($className)
    {
        // Service dependencies are resolved here
        switch ($className) {
            case Printful\services\VersionValidatorService::class:
                return new Printful\services\VersionValidatorService();
            
            case Printful\services\InstallService::class:
                return new Printful\services\InstallService();
            
            case Printful\services\UninstallService::class:
                return new Printful\services\UninstallService();
            
            case Printful\PrintfulClient::class:
                return new Printful\PrintfulClient();
            
            case Printful\PrintfulApi::class:
                $client = self::getService(Printful\PrintfulClient::class);
                return new Printful\PrintfulApi($client);
            
            case Printful\services\WebserviceService::class:
                $api = self::getService(Printful\PrintfulApi::class);
                return new Printful\services\WebserviceService($api);
            
            case Printful\services\AuthMigrationService::class:
                $api = self::getService(Printful\PrintfulApi::class);
                return new Printful\services\AuthMigrationService($api);
            
            case Printful\services\ConnectService::class:
                $webserviceService = self::getService(Printful\services\WebserviceService::class);
                $authMigrationService = self::getService(Printful\services\AuthMigrationService::class);
                return new Printful\services\ConnectService($webserviceService, $authMigrationService);
            
            default:
                throw new Exception('Unknown service: ' . $className);
        }
    }

    /**
     * Configuration page, currently just redirect to dashboard
     * @throws PrestaShopException
     */
    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink(self::CONTROLLER_DASHBOARD));
    }

    /**
     * Add our Webservice resource
     * @return array
     */
    public function hookAddWebserviceResources()
    {
        return self::getCustomWebserviceResources();
    }

    /**
     * Include tab css for icon
     */
    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addCss($this->_path . 'views/css/tab.css');
    }

    /**
     * Return custom Webservice endpoints
     * @return array
     */
    public static function getCustomWebserviceResources()
    {
        return array(
            'printful' => array(
                'description' => 'Printful integration',
                'specific_management' => true,
            ),
        );
    }

    /**
     * @return bool|int
     */
    public static function isOlderPSVersion()
    {
        return version_compare(_PS_VERSION_, '1.7.0', '<');
    }

    /**
     * Returns auth data
     *
     * @return Printful\structures\PrintfulAuthData
     * @throws Adapter_Exception
     */
    public function getAuthData()
    {
        if (!$this->isConnected()) {
            return null;
        }

        /** @var Printful\services\ConnectService $connectService */
        $connectService = self::getService(Printful\services\ConnectService::class);
        /** @var Printful\services\WebserviceService $webService */
        $webService = self::getService(Printful\services\WebserviceService::class);

        $connectedWebService = $webService->getConnectedWebservice();
        return $connectService->buildAuthData($connectedWebService);
    }

    /**
     * Check if module is connected to Printful
     * @return bool
     * @throws \PrestaShop\PrestaShop\Adapter\CoreException
     * @throws Adapter_Exception
     */
    public function isConnected()
    {
        /** @var Printful\services\ConnectService $service */
        $service = self::getService(Printful\services\ConnectService::class);

        return $service->isConnected();
    }

    /**
     * @return Printful\structures\PrintfulPluginVersionCheckData|null
     * @throws Adapter_Exception
     */
    public static function validateCurrentVersion()
    {
        /** @var Printful\services\VersionValidatorService $versionValidator */
        $versionValidator = self::getService(Printful\services\VersionValidatorService::class);

        return $versionValidator->validateVersion(Printful::getInstance()->version);
    }

    /**
     * @return string
     */
    public static function getStoreAddress()
    {
        return Tools::getHttpHost(true) . __PS_BASE_URI__;
    }

    /**
     * @return string
     */
    public static function getPrintfulHost()
    {
        return self::isDevMode() ? self::PRINTFUL_HOST_DEV : self::PRINTFUL_HOST;
    }

    /**
     * @return string
     */
    public static function getPluginDownloadUrl()
    {
        return self::getPrintfulHost() . self::PRINTFUL_PLUGIN_PATH;
    }

    /**
     * @return bool
     */
    public static function isDevMode()
    {
        return self::ENV === self::ENV_DEV;
    }

    /**
     * Get Printful module instance
     * @return Printful
     */
    public static function getInstance()
    {
        return Module::getInstanceByName('printful');
    }

    /**
     * Return web path for module
     * @return string
     */
    public function getWebPath()
    {
        return $this->_path;
    }
}
