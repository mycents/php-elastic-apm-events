<?php

/**
 * configuration short summary.
 *
 * configuration description.
 *
 * @version 1.0
 * @author fernando.vilhena
 */
class configuration
{
    public $data = array();

    function __construct($file_settings = 'appsettings.php') {
        $this->data = include $file_settings;
    }

    public function __get($key) {
        return $this->data[$key];
    }
}
