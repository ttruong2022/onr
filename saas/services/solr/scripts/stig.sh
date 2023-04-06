#!/bin/bash

sed 's/<linux:version datatype="version" operation="equals">1\.8\.0<\/linux:version>/<linux:version datatype="string" operation="pattern match">\^1\\\.8\\\.0\.\*\$<\/linux:version>/g' -i /usr/share/xml/scap/ssg/content/ssg-jre-ds.xml
oscap xccdf eval --remediate --profile xccdf_org.ssgproject.content_profile_stig /usr/share/xml/scap/ssg/content/ssg-jre-ds.xml

echo "deployment.security.blacklist.check.locked" >> /etc/.java/deployment/deployment.properties

# openssl genrsa -out my_key.key 4096
# openssl req -new -key my_key.key -out my_key.csr -subj "/C=US/ST=DC/L=Washington/O=Mobomo LLC/OU=Engineering"
# openssl x509 -req -days 3650 -in my_key.csr -signkey my_key.key -out my_key.crt
# openssl pkcs12 -keypbe PBE-SHA1-3DES -certpbe PBE-SHA1-3DES -export -in my_key.crt -inkey my_key.key -out my_key.pfx -name "my-name" -passout pass:secret

# cp my_key.pfx /opt/solr-8.11.1/server/etc/solr-ssl.keystore.p12
# chown solr:solr /opt/solr-8.11.1/server/etc/solr-ssl.keystore.p12

# Generating KeyStore Errors on STIG machines
# Created on non-STIG machine with:
# keytool -genkeypair -alias solr-ssl -keyalg RSA -keysize 2048 -keypass secret -storepass secret -validity 9999 -keystore solr-ssl.keystore.jks -ext SAN=DNS:localhost,IP:127.0.0.1 -dname "CN=localhost, OU=Engineering, O=Mobomo, L=Vienna, ST=VA, C=USA"

cp /var/www/saas/services/solr/ssl/solr-ssl.keystore.jks /opt/solr-8.11.1/server/etc/solr-ssl.keystore.jks
chown solr:solr /opt/solr-8.11.1/server/etc/solr-ssl.keystore.jks

# Prod
# SOLR_SSL_KEY_STORE=/etc/ssl/certs/dc2hs.jks
# SOLR_SSL_TRUST_STORE=/etc/ssl/certs/cacerts.jks

cat >> /etc/default/solr.in.sh <<EOF
SOLR_SSL_ENABLED=true
SOLR_SSL_KEY_STORE=/opt/solr-8.11.1/server/etc/solr-ssl.keystore.jks
SOLR_SSL_KEY_STORE_PASSWORD=secret
SOLR_SSL_TRUST_STORE=/opt/solr-8.11.1/server/etc/solr-ssl.keystore.jks
SOLR_SSL_TRUST_STORE_PASSWORD=secret
SOLR_SSL_NEED_CLIENT_AUTH=false
SOLR_SSL_WANT_CLIENT_AUTH=false
SOLR_SSL_CLIENT_HOSTNAME_VERIFICATION=false
SOLR_SSL_CHECK_PEER_NAME=true
SOLR_SSL_KEY_STORE_TYPE=JKS
SOLR_SSL_TRUST_STORE_TYPE=JKS
SOLR_OPTS="\$SOLR_OPTS -Dcom.redhat.fips=false"
EOF

curl -L -o /tmp/apache-log4j-2.17.2-bin.tar.gz https://dlcdn.apache.org/logging/log4j/2.17.2/apache-log4j-2.17.2-bin.tar.gz
tar -xvf /tmp/apache-log4j-2.17.2-bin.tar.gz --directory /tmp

curl -L -o /tmp/apache-log4j-2.17.2-bin/log4j-layout-template-json-2.17.2.jar https://repo1.maven.org/maven2/org/apache/logging/log4j/log4j-layout-template-json/2.17.2/log4j-layout-template-json-2.17.2.jar

LOG4J_PACKAGES=("log4j-1.2-api" "log4j-api" "log4j-core" "log4j-layout-template-json" "log4j-slf4j-impl" "log4j-web")

for package in ${LOG4J_PACKAGES[@]}; do
    if [ -f /tmp/apache-log4j-2.17.2-bin/$package-2.17.2.jar ]; then
        rm -rf /opt/solr/server/lib/ext/$package-2.16.0.jar
        cp -i /tmp/apache-log4j-2.17.2-bin/$package-2.17.2.jar /opt/solr/server/lib/ext/$package-2.17.2.jar
        chmod 644 /opt/solr/server/lib/ext/$package-2.17.2.jar
    fi
done

LOG4J_PACKAGES=("log4j-api" "log4j-core" "log4j-slf4j-impl")

for package in ${LOG4J_PACKAGES[@]}; do
    if [ -f /tmp/apache-log4j-2.17.2-bin/$package-2.17.2.jar ]; then
        rm -rf /opt/solr/contrib/prometheus-exporter/lib/$package-2.16.0.jar
        cp -i /tmp/apache-log4j-2.17.2-bin/$package-2.17.2.jar /opt/solr/contrib/prometheus-exporter/lib/$package-2.17.2.jar
        chmod 644 /opt/solr/contrib/prometheus-exporter/lib/$package-2.17.2.jar
    fi
done

restorecon -Rv $(readlink -f /opt/solr/server/lib/ext)
restorecon -Rv $(readlink -f /opt/solr/contrib/prometheus-exporter/lib)

rm /tmp/apache-log4j-2.17.2-bin.tar.gz
rm -rf /tmp/apache-log4j-2.17.2-bin

#echo '!/var/solr/data/search/data/' >> /etc/aide.conf