Postman configuration for MockONEISS

Files:
- `MockOneiss.postman_collection.json` — Postman collection (official WSDL RPC/encoded)
- `MockOneiss.postman_environment.json` — Environment with `base_url` and `wsdl_url`

How to use:
1. Import the collection JSON and the environment JSON into Postman.
2. Select the environment `MockONEISS - Local` and ensure `base_url` points to your server (default http://localhost:8080).
3. Use any request (e.g., `pushApirData (Data as string)`) and send.

Notes:
- The official WSDL uses RPC/encoded with a single parameter named `Data` of type `xsd:string`.
- The collection sends JSON inside CDATA within `<Data><![CDATA[{...}]]></Data>` to comply with the WSDL while keeping payloads readable.
- Requests POST to `{{base_url}}/webservice/index.php` with header `Content-Type: text/xml;charset=UTF-8`.
- Included requests:
  - `WSDL - download` (GET)
  - `oneiss (ping)` (POST, no params)
  - `pushInjuryData (Data as string)` (POST)
  - `pushApirData (Data as string)` (POST)
  - `webInjury (Data as string)` (POST)
  - `DataSelect (Data as string)` (POST)
