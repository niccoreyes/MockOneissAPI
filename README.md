# Mock ONEISS SOAP API (Local Test Server)

This repository is a mock implementation of selected ONEISS SOAP operations to help you test client integrations locally. It is NOT the official ONEISS service and is not affiliated with the Department of Health. Use it to simulate what the ONEISS API could look like and to validate your client payloads end-to-end.

What you get
- SOAP endpoint that mirrors key ONEISS operations: `pushInjuryData`, `pushApirData`, `webInjury`, plus `oneiss` (ping) and `DataSelect`.
- Official WSDL alignment (RPC/encoded, single string parameter named `Data`).
- Request persistence to a local SQLite DB and an Admin UI for browsing, filtering, and CSV export.
- Sample payloads and Postman collection to speed up testing.

---

## Run locally

Prerequisites
- Docker Desktop (recommended)

Start the server
- Windows PowerShell / macOS / Linux
  - docker compose up --build -d

Stop the server
- docker compose down

---

## Endpoints

- SOAP endpoint (POST):
  - http://localhost:8080/webservice/index.php
- WSDL (served locally):
  - http://localhost:8080/webservice/index.php?wsdl
- Admin UI (browse requests, filter, CSV export):
  - http://localhost:8080/webservice/admin.php
- AutoDiscover WSDL (optional, if you want a generated WSDL):
  - http://localhost:8080/webservice/autodiscover.php?wsdl

---

## Postman

Files
- postman/MockOneiss.postman_collection.json
- postman/MockOneiss.postman_environment.json

Steps
1) Import both files in Postman.
2) Select environment “MockONEISS - Local”.
3) Run any request (e.g., “pushApirData (XML in CDATA)”).

Notes
- Requests post to `{{base_url}}/webservice/index.php` with header `Content-Type: text/xml;charset=UTF-8`.
- The collection includes: WSDL download, oneiss (ping), pushInjuryData, pushApirData, webInjury, DataSelect.

---

## CLI tests

PowerShell (Windows / PowerShell Core)
- scripts/test_pushInjuryData.ps1
- scripts/test_pushApirData.ps1
- scripts/test_webInjury.ps1
- scripts/test_oneiss.ps1
- scripts/test_dataSelect.ps1
- scripts/run_all_tests.ps1 — runs all tests and fails if any test fails

Bash (Linux / macOS / WSL)
- scripts/test_all.sh — runs pushInjuryData, pushApirData, webInjury

All tests expect `<oneiss><response_code>104</response_code></oneiss>` for success.

---

## Payloads and formats

The official ONEISS WSDL uses RPC/encoded, where each operation takes a single parameter named `Data` of type `xsd:string`. Many clients still prefer composing XML for readability. To keep XML while satisfying the WSDL, we wrap the XML inside CDATA so it is transmitted as a string.

You can find ready-to-send samples under `src/request-samples/`.

- pushApirData (XML-in-CDATA)
  - File: `src/request-samples/pushApirData.xml`
  - Structure:
    - SOAP Envelope → Body → `urn:pushApirData` → `<Data><![CDATA[ <OneissData>...fields...</OneissData> ]]></Data>`
- pushInjuryData (XML-in-CDATA)
  - File: `src/request-samples/pushInjuryData.xml`
  - Structure:
    - SOAP Envelope → Body → `urn:pushInjuryData` → `<Data><![CDATA[ <OneissData>...fields...</OneissData> ]]></Data>`
- webInjury (structured XML)
  - File: `src/request-samples/webInjury.xml`
  - Structure:
    - SOAP Envelope → Body → `urn:webInjury` → `<Data>...fields...</Data>`
  - Tip: If you see encoding errors from strict SOAP clients, you can also wrap the inner XML in CDATA for consistency.

Why CDATA?
- The WSDL says `Data` is an `xsd:string`. Wrapping your XML in CDATA keeps `Data` a string while letting you retain an XML-shaped payload.
- The mock parses either JSON strings or XML strings inside `Data` and converts them to arrays automatically.

---

## Admin UI

- URL: http://localhost:8080/webservice/admin.php
- Features:
  - Filter by operation, search content, and date range
  - Paginated results with expandable, structured views of payloads and responses
  - Export CSV of matching results

---

## Troubleshooting

- SOAP-ERROR: Encoding: Violation of encoding rules
  - Cause: RPC/encoded with `xsd:string` parameter `Data` rejects inline nested XML elements.
  - Fix: Wrap your inner XML inside CDATA so `Data` remains a string.
- Empty or 400 responses
  - The mock validates some required fields and returns `400` codes with a list of missing fields. Check the Admin UI or console for details.

---

## Repository layout

- src/public/webservice/index.php — SOAP endpoint + WSDL + landing page
- src/public/webservice/admin.php — Admin UI
- src/Service.php — Mock service implementation and persistence
- src/wsdl/oneiss.wsdl — WSDL used by SoapServer (copied from source)
- src/request-samples/*.xml — Ready-to-send SOAP request samples
- postman/* — Postman collection and environment
- scripts/* — PowerShell and Bash test scripts

---

## Disclaimer

This is a local mock for development and testing. It is not the official ONEISS system. Do not send real or sensitive patient data to this server.

