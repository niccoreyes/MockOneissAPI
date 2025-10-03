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
        // capture raw POST for debugging
        $this->logRawRequest('pushInjuryData');

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
        $this->logRawRequest('pushApirData');
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
        $this->logRawRequest('webInjury');
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

    // Helpers
    private function normalizeData($params)
    {
        // If SOAP provides a wrapper 'parameters' (some clients), unwrap it
        if (is_object($params) && property_exists($params, 'parameters')) {
            $params = $params->parameters;
        }

        // If direct object with many properties (fields match WSDL), convert to array
        if (is_object($params)) {
            $arr = $this->objectToArray($params);
            // If there is a 'Data' key inside, prefer its contents
            if (isset($arr['Data'])) {
                $dataCandidate = $arr['Data'];
                if (is_array($dataCandidate) && count($dataCandidate) > 0) return $dataCandidate;
                if (is_string($dataCandidate)) {
                    $decoded = json_decode($dataCandidate, true);
                    if (json_last_error() === JSON_ERROR_NONE) return $decoded;
                }
            }
            // If object fields are the actual data (e.g., Pat_Facility_No is a property), return arr
            // Filter out empty numeric keys
            if (count($arr) > 0) return $arr;
            return [];
        }

        // If array
        if (is_array($params)) {
            // If Data key exists and contains array or JSON string
            if (isset($params['Data'])) {
                $d = $params['Data'];
                if (is_array($d)) return $d;
                if (is_string($d)) {
                    $json = json_decode($d, true);
                    if (json_last_error() === JSON_ERROR_NONE) return $json;
                    // attempt parse as XML
                    if (strpos(trim($d), '<') === 0) {
                        try { $xml = new SimpleXMLElement($d); return json_decode(json_encode($xml), true); } catch (Exception $e) {}
                    }
                    // otherwise return raw
                    return ['raw' => $d];
                }
            }
            return $params;
        }

        // If param was string (client sent a raw JSON or XML string)
        if (is_string($params)) {
            $trim = trim($params);
            $json = json_decode($trim, true);
            if (json_last_error() === JSON_ERROR_NONE) return $json;
            if (strpos($trim, '<') !== false) {
                try { $xml = new SimpleXMLElement($trim); return json_decode(json_encode($xml), true); } catch (Exception $e) {}
            }
            return ['raw' => $params];
        }

        return [];
    }

    private function logRawRequest($operation)
    {
        $raw = @file_get_contents('php://input');
        $dir = __DIR__ . '/data';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $f = $dir . '/requests.log';
        $entry = date('c') . " [{$operation}]\n" . ($raw ?: "(empty)") . "\n---\n";
        @file_put_contents($f, $entry, FILE_APPEND | LOCK_EX);
    }

    private function objectToArray($obj)
    {
        $arr = [];
        foreach ((array)$obj as $k => $v) {
            $k = preg_replace('/^\w+:/', '', $k);
            if (is_object($v) || is_array($v)) {
                $arr[$k] = $this->objectToArray($v);
            } else {
                $arr[$k] = $v;
            }
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
