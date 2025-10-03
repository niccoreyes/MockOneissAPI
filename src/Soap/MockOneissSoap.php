<?php
namespace MockOneiss\Soap;

use MockOneiss\Model\PushInjuryData;
use MockOneiss\Model\PushApirData;

/**
 * Service class used only for WSDL AutoDiscover generation by Laminas.
 * Methods are annotated to describe complex types.
 */
class MockOneissSoap
{
    /**
     * pushInjuryData
     * @param PushInjuryData $Data
     * @return string
     */
    public function pushInjuryData(PushInjuryData $Data)
    {
        return '';
    }

    /**
     * pushApirData
     * @param PushApirData $Data
     * @return string
     */
    public function pushApirData(PushApirData $Data)
    {
        return '';
    }

    /**
     * webInjury
     * @param PushInjuryData $Data
     * @return string
     */
    public function webInjury(PushInjuryData $Data)
    {
        return '';
    }
}
