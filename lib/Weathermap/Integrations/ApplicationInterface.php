<?php

namespace Weathermap\Integrations;

use PDO;

/**
 * A base class for any application integration. Per-app subclasses will implement these
 * methods to provide equivalent functionality inside their user interface. They started out
 * as Cacti function calls, but hopefully they are generic enough to be applicable elsewhere.
 *
 *  * @package Weathermap\Integrations
 */
class ApplicationInterface
{
    /** @var PDO $pdo */
    protected $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getLocale()
    {
        return "en";
    }

    public function getAppVersion()
    {
        return "";
    }

    public function getAppSetting($name, $defaultValue = "")
    {
        return $defaultValue;
    }

    public function setAppSetting($name, $value)
    {
    }

    public function deleteAppSetting($name)
    {
    }

    public function getUserList($includeAnyone = false)
    {
        return array();
    }

    public function getCurrentUserId()
    {
        return 0;
    }

    public function checkUserAccess($userId, $realm)
    {
        return false;
    }

    public function getMapURL($mapConfig)
    {
        return sprintf("output/%s.html", $mapConfig->id);
    }

    public function getMapImageURL($mapConfig)
    {
        return sprintf("output/%s.png", $mapConfig->id);
    }

    public function buildLogger($log)
    {
        return $log;
    }
}
