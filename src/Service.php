<?php
class Service
{
    private $dbFile;

    public function __construct()
    {
        $this->dbFile = __DIR__ . '/data/oneiss.db';
        $this->ensureDb();
    }

    // pushInjuryData: full schema validation
    public function pushInjuryData($params)
    {
        $data = $this->normalizeData($params);

        $required = [
            'Pat_Facility_No','Status','rstatuscode','date_report','time_report',
            'reg_no','tempreg_no','hosp_no','hosp_reg_no','hosp_cas_no','ptype_code'
        ];

        $missing = $this->validateRequired($data, $required);
        if (!empty($missing)) {
            $resp = $this->makeResponse('400', 'Validation Error - missing fields: '.implode(', ', $missing));
            $this->persistRecord('pushInjuryData', $data, $resp);
            return $resp;
        }

        $inner = $this->arrayToXml($data);
        $resp = $this->makeResponse('104', 'Success', $inner);
        $this->persistRecord('pushInjuryData', $data, $resp);
        return $resp;
    }

    // pushApirData: specific schema
    public function pushApirData($params)
    {
        $data = $this->normalizeData($params);

        $required = [
            'Pat_Facility_No','Pat_Last_Name','Pat_First_Name','Pat_Middle_Name',
            'Pat_Sex','Pat_Current_Address_StreetName','Pat_Current_Address_Region',
            'Pat_Current_Address_Province','Pat_Current_Address_City','inj_date','inj_time',
            'Encounter_Date','Encounter_Time','involve_code','typeof_injurycode','diagnosis','liquor','disposition_code'
        ];

        $missing = $this->validateRequired($data, $required);
        if (!empty($missing)) {
            $resp = $this->makeResponse('400', 'Validation Error - missing fields: '.implode(', ', $missing));
            $this->persistRecord('pushApirData', $data, $resp);
            return $resp;
        }

        $inner = $this->arrayToXml($data);
        $resp = $this->makeResponse('104', 'Success', $inner);
        $this->persistRecord('pushApirData', $data, $resp);
        return $resp;
    }

    public function webInjury($params)
    {
        $data = $this->normalizeData($params);
        $inner = $this->arrayToXml($data);
        $resp = $this->makeResponse('104', 'Success', $inner);
        $this->persistRecord('webInjury', $data, $resp);
        return $resp;
    }

    // Persistence
    private function ensureDb()
    {
        $dir = dirname($this->dbFile);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        if (!file_exists($this->dbFile)) {
            $pdo = new PDO('sqlite:' . $this->dbFile);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("CREATE TABLE IF NOT EXISTS records (id INTEGER PRIMARY KEY AUTOINCREMENT, operation TEXT, payload TEXT, response TEXT, created_at TEXT)");
        }
    }

    private function persistRecord($operation, $payloadArray, $responseXml)
    {
        try {
            $pdo = new PDO('sqlite:' . $this->dbFile);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $pdo->prepare('INSERT INTO records (operation, payload, response, created_at) VALUES (:op, :payload, :resp, :dt)');
            $stmt->execute([
                ':op' => $operation,
                ':payload' => json_encode($payloadArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ':resp' => $responseXml,
                ':dt' => date('c')
            ]);
        } catch (Exception $e) {
            // ignore persistence errors in mock
        }
    }

    // Helpers (unchanged except visibility)
    private function normalizeData($params)
    {
        if (is_object($params) && property_exists($params, 'Data')) {
            $raw = $params->Data;
            if (is_object($raw)) {
        }
        return $arr;
    }

    private function validateRequired(array $data, array $required)
    {
        $missing = [];
        foreach ($required as $k) {
            if (!array_key_exists($k, $data) || $data[$k] === null || trim((string)$data[$k]) === '') {
                $missing[] = $k;
            }
        }
        return $missing;
    }

    private function arrayToXml(array $data)
    {
        $xml = "<SampleData>\n";
        foreach ($data as $k => $v) {
            // convert nested arrays to JSON string to keep response compact
            if (is_array($v)) {
                $val = htmlspecialchars(json_encode($v), ENT_XML1 | ENT_COMPAT, 'UTF-8');
            } else {
                $val = htmlspecialchars((string)$v, ENT_XML1 | ENT_COMPAT, 'UTF-8');
            }
            $xml .= "  <" . $this->escapeTag($k) . ">" . $val . "</" . $this->escapeTag($k) . ">\n";
        }
        $xml .= "</SampleData>\n";
        return $xml;
    }

    private function escapeTag($tag)
    {
        // ensure XML element names are safe (replace spaces and invalid chars)
        $tag = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $tag);
        if (preg_match('/^[0-9]/', $tag)) $tag = 'f_' . $tag;
        return $tag;
    }

    private function makeResponse($code, $desc, $responseValue = null)
    {
        $dt = date('F j, Y g:i A');
        $xml = "<oneiss>\n";
        $xml .= "  <response_code>" . htmlspecialchars((string)$code, ENT_XML1) . "</response_code>\n";
        $xml .= "  <response_desc>" . htmlspecialchars((string)$desc, ENT_XML1) . "</response_desc>\n";
        $xml .= "  <response_datetime>" . htmlspecialchars($dt, ENT_XML1) . "</response_datetime>\n";
        if ($responseValue !== null) {
            $xml .= "  <response_value>\n";
            // responseValue expected to be XML fragment
            $xml .= $this->indentXml($responseValue, 4);
            $xml .= "  </response_value>\n";
        }
        $xml .= "</oneiss>";
        return $xml;
    }

    private function indentXml($xmlFragment, $spaces = 2)
    {
        $lines = preg_split('/\r?\n/', trim($xmlFragment));
        $out = '';
        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            $out .= str_repeat(' ', $spaces) . $line . "\n";
        }
        return $out;
    }
}
