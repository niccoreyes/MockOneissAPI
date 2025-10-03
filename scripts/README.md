Test scripts for MockONEISS

PowerShell (Windows / PowerShell Core):
- `scripts\test_pushInjuryData.ps1` — send WSDL-compliant pushInjuryData request (Data as string), parse response, exit 0 on success
- `scripts\test_pushApirData.ps1` — send pushApirData (Data as string)
- `scripts\test_webInjury.ps1` — send webInjury (Data as string)
- `scripts\test_oneiss.ps1` — ping operation (no params)
- `scripts\test_dataSelect.ps1` — DataSelect query (Data as string JSON)
- `scripts\run_all_tests.ps1` — run all tests and return non-zero if any fail

Usage (PowerShell):
1. Start the mock server: `docker compose up --build -d`
2. From repository root run:
   pwsh ./scripts/test_pushInjuryData.ps1
   pwsh ./scripts/test_pushApirData.ps1
   pwsh ./scripts/test_webInjury.ps1
   pwsh ./scripts/test_oneiss.ps1
   pwsh ./scripts/test_dataSelect.ps1
   pwsh ./scripts/run_all_tests.ps1

Bash (Linux / macOS / WSL):
- `scripts/test_all.sh` — runs pushInjuryData, pushApirData, and webInjury using curl. It attempts to use `xmllint` and `python3` if available to parse and unescape XML.

Usage (bash):
1. Ensure mock server is running
2. Make script executable: `chmod +x scripts/test_all.sh`
3. Run: `./scripts/test_all.sh` or `./scripts/test_all.sh http://localhost:8080`

Notes:
- All scripts POST to `{{base_url}}/webservice/index.php` with header `Content-Type: text/xml;charset=UTF-8`.
- The official WSDL uses RPC/encoded with a single `Data` parameter of type `xsd:string`.
- The PowerShell scripts parse the SOAP response and look for `<oneiss><response_code>` inside `<return>`. They return 0 when `response_code` is `104`.
