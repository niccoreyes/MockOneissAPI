#!/usr/bin/env bash
BASE=${1:-http://localhost:8080}
REQ1=src/request-samples/pushInjuryData.xml
REQ2=src/request-samples/pushApirData.xml

run() {
  local req=$1
  echo "POST $BASE/index.php with $req"
  resp=$(curl -s -H "Content-Type: text/xml;charset=UTF-8" --data-binary @"$req" "$BASE/index.php")

  if [ -z "$resp" ]; then
    echo "Empty response"
    return 2
  fi

  # extract inner <return> content
  if command -v xmllint >/dev/null 2>&1; then
    inner=$(echo "$resp" | xmllint --xpath "string(//*[local-name()='return'])" - 2>/dev/null)
  else
    inner=$(echo "$resp" | sed -n 's/.*<return>\(.*\)<\/return>.*/\1/p')
  fi

  if [ -z "$inner" ]; then
    echo "No <return> found"
    echo "$resp"
    return 2
  fi

  # unescape HTML entities using python if available
  if command -v python3 >/dev/null 2>&1; then
    inner_unescaped=$(python3 - <<'PY'
import sys, html
s=sys.stdin.read()
print(html.unescape(s))
PY
<<<"$inner")
  elif command -v python >/dev/null 2>&1; then
    inner_unescaped=$(python - <<'PY'
import sys, html
s=sys.stdin.read()
print(html.unescape(s))
PY
<<<"$inner")
  else
    inner_unescaped="$inner"
  fi

  code=$(echo "$inner_unescaped" | grep -oP '(?<=<response_code>).*?(?=</response_code>)' || true)
  desc=$(echo "$inner_unescaped" | grep -oP '(?<=<response_desc>).*?(?=</response_desc>)' || true)

  echo "response_code: $code"
  echo "response_desc: $desc"

  if [ "$code" = "104" ]; then
    echo "OK"
    return 0
  else
    echo "FAIL"
    return 1
  fi
}

run "$REQ1"; rc1=$?
run "$REQ2"; rc2=$?

echo "Summary: pushInjuryData=$rc1 pushApirData=$rc2"
if [ $rc1 -ne 0 -o $rc2 -ne 0 ]; then
  exit 1
else
  exit 0
fi
