<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@outlook.com>
 */

namespace simialbi\yii2\ews;

use jamesiarmes\PhpEws\ClassMap;
use jamesiarmes\PhpNtlm\SoapClient;

class Client extends \jamesiarmes\PhpEws\Client
{
    /**
     * @var string explicit location (prevent auto generate)
     */
    protected $location;

    /**
     * Sets the location property
     *
     * @param string $location
     */
    public function setLocation(string $location)
    {
        $this->location = $location;

        // We need to reinitialize the SOAP client.
        $this->soap = null;
    }

    /**
     * {@inheritDoc}
     * @throws \SoapFault
     */
    protected function initializeSoapClient(): SoapClient
    {
        $this->soap = new SoapClient(
            dirname(__FILE__) . '/assets/services.wsdl',
            [
                'user' => $this->username,
                'password' => $this->password,
                'location' => !empty($this->location) ? $this->location : 'https://' . $this->server . '/EWS/Exchange.asmx',
                'classmap' => ClassMap::getMap(),
                'curlopts' => $this->curl_options,
                'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
            ]
        );

        return $this->soap;
    }
}
