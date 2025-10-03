Test scripts for MockONEISS

PowerShell (Windows / PowerShell Core):
- `scripts\test_pushInjuryData.ps1` — send sample pushInjuryData request, parse response, exit 0 on success
- `scripts\test_pushApirData.ps1` — send sample pushApirData request
- `scripts\run_all_tests.ps1` — run both tests and return non-zero if any fail

Usage (PowerShell):
1. Start the mock server: `docker-compose up --build -d`
2. From repository root run:
   pwsh ./scripts/test_pushInjuryData.ps1
   pwsh ./scripts/test_pushApirData.ps1
   pwsh ./scripts/run_all_tests.ps1

Bash (Linux / macOS / WSL):
- `scripts/test_all.sh` — runs both tests using `curl`. It attempts to use `xmllint` and `python3` if available to parse and unescape XML.

Usage (bash):
1. Ensure mock server is running
2. Make script executable: `chmod +x scripts/test_all.sh`
3. Run: `./scripts/test_all.sh` or `./scripts/test_all.sh http://localhost:8080`

Notes:
- Scripts expect the mock to be available at http://localhost:8080 by default. Pass a different base URL as the first argument to `test_all.sh` or use the `-BaseUrl` parameter in PowerShell scripts.
- The PowerShell scripts parse the SOAP response and look for an inner `<oneiss><response_code>` value. They return 0 when `response_code` is `104` (mock success). Adjust if you want different pass/fail logic.
