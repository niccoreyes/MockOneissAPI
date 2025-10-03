# mockONEISS

Minimal Dockerized mock of the ONEISS SOAP API (pushInjuryData, pushApirData, webInjury).

Quick start (Windows PowerShell):

1. Build and run
   docker-compose up --build -d

2. Open WSDL in a browser
   http://localhost:8080/index.php?wsdl

3. Test with curl (PowerShell)
   curl -v -H "Content-Type: text/xml;charset=UTF-8" --data-binary @src/request-samples/pushInjuryData.xml http://localhost:8080/index.php

4. Test with PHP SoapClient (requires PHP with ext-soap on host)
   $client = new SoapClient('http://localhost:8080/index.php?wsdl', ['trace'=>1]);
   $res = $client->pushInjuryData(['Data' => json_encode(['Pat_Last_Name'=>'Platon'])]);
   var_dump($res);

What I created
- `Dockerfile` — PHP 8.2 + Apache + ext-soap
- `docker-compose.yml` — runs container on port 8080
- `src/public/index.php` — front controller, serves WSDL and SOAP POSTs
- `src/Service.php` — mock implementation for three operations
- `src/wsdl/oneiss.wsdl` — minimal WSDL exposing those operations
- `src/request-samples/*.xml` — sample SOAP requests

Next options (tell me which):
- Change port (default 8080) — specify a port.
- Full WSDL: generate a WSDL that includes the full complex type schema for all fields from your docs (I can generate it automatically or by hand). This will make SoapClient strongly-typed.
- Laminas AutoDiscover variant: add Composer and `laminas/laminas-soap` so WSDL is generated from PHP class annotations (recommended if you want full complex types and maintainability).
- Persisted mock responses / dynamic rules: map incoming fields to different response codes, or store received records in a local SQLite DB.

Questions I have
1) Which port do you want the mock to listen on (current: 8080)?
2) Do you want the full WSDL (all fields) or is the minimal WSDL sufficient for testing?
3) Do you want authentication or TLS for the mock (not recommended for local dev unless needed)?

If you want I can also:
- Add a small test script that runs inside CI (GitHub Actions) to spin up the container and run the sample request.
- Produce the Laminas/composer variant.

