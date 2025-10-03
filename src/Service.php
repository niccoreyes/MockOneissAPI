<?php
class Service
{
    // Simple mock implementation returning raw XML string as in original service
    public function pushInjuryData($params)
    {
        return $this->makeSuccessResponse('pushInjuryData', $params);
    }

    public function pushApirData($params)
    {
        return $this->makeSuccessResponse('pushApirData', $params);
    }

    public function webInjury($params)
    {
        // Return a small XML payload (or could return array if WSDL adjusted)
        return $this->makeSuccessResponse('webInjury', $params);
    }

    private function makeSuccessResponse($op, $params)
    {
        $dt = date('F j, Y g:i A');
        $sample = "<oneiss>\n  <response_code>104</response_code>\n  <response_desc>Success</response_desc>\n  <response_datetime>{$dt}</response_datetime>\n  <response_value>\n    <SampleData>\n      <lastname>Platon</lastname>\n      <firstname>Jonathan</firstname>\n      <middlename>Elec</middlename>\n      <operation>{$op}</operation>\n    </SampleData>\n  </response_value>\n</oneiss>";
        return $sample;
    }
}
