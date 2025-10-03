Postman configuration for MockONEISS

Files:
- `MockOneiss.postman_collection.json` — Postman collection (pushInjuryData, pushApirData)
- `MockOneiss.postman_environment.json` — Environment with `base_url` (default http://localhost:8080)

How to use:
1. Open Postman -> Import -> choose the collection JSON file above.
2. Import the environment JSON and select it in the top-right environment selector.
3. Run the `pushInjuryData` or `pushApirData` request. Ensure the mock server is running (`docker-compose up --build -d`).

Notes:
- Requests POST to `{{base_url}}/index.php` with SOAP XML body and header `Content-Type: text/xml;charset=UTF-8`.
- If you change the port, update the `base_url` variable in the environment.
