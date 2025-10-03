Postman configuration for MockONEISS

Files:
- `MockOneiss.postman_collection.json` — Postman collection (structured XML samples)
- `MockOneiss.postman_environment.json` — Environment with `base_url` and `wsdl_url`

How to use:
1. Import the collection JSON and the environment JSON into Postman.
2. Select the environment `MockONEISS - Local` and ensure `base_url` points to your server (default http://localhost:8080).
3. Use any request (e.g., `pushApirData (XML)`) and send.

Notes:
- Requests POST to `{{base_url}}/webservice/index.php` with header `Content-Type: text/xml;charset=UTF-8`.
- Samples use structured XML inside `<Data> ... </Data>`. The mock server parses this and responds with a `<oneiss>` result.
- Included requests:
  - `WSDL - download` (GET)
  - `oneiss (ping)` (POST, no params)
  - `pushInjuryData (XML)` (POST)
  - `pushApirData (XML)` (POST)
  - `webInjury (XML)` (POST)
  - `DataSelect (XML)` (POST)
