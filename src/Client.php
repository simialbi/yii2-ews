<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@outlook.com>
 */

namespace simialbi\yii2\ews;

use jamesiarmes\PhpEws\ClassMap;
use jamesiarmes\PhpNtlm\SoapClient;
use Yii;
use yii\base\InvalidConfigException;

class Client extends \jamesiarmes\PhpEws\Client
{
    /**
     * @var string explicit location (prevent auto generate)
     */
    protected string $location;

    /**
     * {@inheritDoc}
     * @param string $timezone The default timezone to set (Exchange format). Defaults to `W. Europe standard time`.
     * @throws InvalidConfigException
     */
    public function __construct($server = null, $username = null, $password = null, $version = self::VERSION_2013, ?string $timezone = null)
    {
        parent::__construct($server, $username, $password, $version);

        if (null === $timezone) {
            $this->autoSetTimezone();
        } else {
            $this->setTimezone($timezone);
        }
    }

    /**
     * Sets the location property
     *
     * @param string $location
     */
    public function setLocation(string $location): void
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
            Yii::getAlias('@vendor/php-ews/php-ews/src/assets/services.wsdl'),
            [
                'user' => $this->username,
                'password' => $this->password,
                'location' => $this->location ?? 'https://' . $this->server . '/EWS/Exchange.asmx',
                'classmap' => ClassMap::getMap(),
                'curlopts' => $this->curl_options,
                'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
            ]
        );

        return $this->soap;
    }

    /**
     * Try to set exchange timezone from app configuration
     *
     * @return void
     * @throws InvalidConfigException
     */
    private function autoSetTimezone(): void
    {
        $list = timezone_abbreviations_list();
        $offset = 0;
        $timezoneName = Yii::$app->formatter->asDate('now', 'VV');

        foreach ($list as $items) {
            foreach ($items as $item) {
                if ($item['dst']) {
                    continue 2;
                }
                if ($item['timezone_id'] === $timezoneName) {
                    $offset = $item['offset'];
                    break 2;
                }
            }
        }

        $this->setTimezone(match ($offset) {
            -39600 => 'UTC-11',
            -36000 => 'Hawaiian Standard Time',
            -28800 => 'Pacific Standard Time',
            -25200 => 'Mountain Standard Time',
            -21600 => 'Central America Standard Time',
            -18000 => 'Eastern Standard Time',
            -16200 => 'Venezuela Standard Time',
            -14400 => 'SA Western Standard Time',
            -10800 => 'Pacific SA Standard Time',
            -7200 => 'UTC-02',
            -3600 => 'Cape Verde Standard Time',
            default => 'UTC',
            3600 => 'W. Europe Standard Time',
            7200 => 'FLE Standard Time',
            10800 => 'Arab Standard Time',
            12600 => 'Iran Standard Time',
            14400 => 'Caucasus Standard Time',
            19800 => 'India Standard Time',
            20700 => 'Nepal Standard Time',
            21600 => 'Bangladesh Standard Time',
            23400 => 'Myanmar Standard Time',
            25200 => 'SE Asia Standard Time',
            28800 => 'Singapore Standard Time',
            32400 => 'Tokyo Standard Time',
            36000 => 'AUS Eastern Standard Time',
            39600 => 'Central Pacific Standard Time',
            43200 => 'UTC+12',
            46800 => 'Samoa Standard Time'
        });
    }
}
