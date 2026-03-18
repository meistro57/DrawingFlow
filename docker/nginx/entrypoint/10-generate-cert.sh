#!/bin/sh
set -eu

CERT_PATH="${TLS_CERT_PATH:-/etc/nginx/certs/tls.crt}"
KEY_PATH="${TLS_KEY_PATH:-/etc/nginx/certs/tls.key}"
CERT_CN="${TLS_CERT_CN:-localhost}"
CERT_SAN="${TLS_CERT_SAN:-DNS:localhost,DNS:host.docker.internal}"

if [ -f "$CERT_PATH" ] && [ -f "$KEY_PATH" ]; then
    echo "Using existing TLS certificate: $CERT_PATH"
    exit 0
fi

mkdir -p "$(dirname "$CERT_PATH")" "$(dirname "$KEY_PATH")"

TMP_CONF="$(mktemp)"
cat > "$TMP_CONF" <<EOF
[req]
default_bits = 2048
prompt = no
default_md = sha256
distinguished_name = dn
x509_extensions = v3_req

[dn]
CN = ${CERT_CN}

[v3_req]
subjectAltName = ${CERT_SAN}
EOF

openssl req -x509 -nodes -newkey rsa:2048 \
    -keyout "$KEY_PATH" \
    -out "$CERT_PATH" \
    -days 825 \
    -config "$TMP_CONF"

rm -f "$TMP_CONF"

echo "Generated self-signed TLS certificate: $CERT_PATH"
