<?php
namespace Config;
use CodeIgniter\Config\BaseConfig;

class CILogViewer extends BaseConfig {
    public $logFilePattern = 'log-*.log';
    public $viewName = 'panel/logs'; //where logs exists in app/Views/logs.php
}