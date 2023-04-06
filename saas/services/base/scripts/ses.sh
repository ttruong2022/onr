#!/bin/bash

if [ "$#" -ne 2 ]; then
    echo "illegal number of parameters"
    exit 1;
fi

export AWS_DEFAULT_REGION=$1
SECRET_STRING=`aws secretsmanager get-secret-value --secret-id "$2" --query SecretString --output text`
USERNAME=`echo $SECRET_STRING | grep -Po '(?<="SESUserKeyID":")[^"]+(?=")'`
PASSWORD=`echo $SECRET_STRING | grep -Po '(?<="SESUserPassword":")[^"]+(?=")'`

postconf -e "relayhost = [email-smtp.$AWS_DEFAULT_REGION.amazonaws.com]:587" \
"smtp_sasl_auth_enable = yes" \
"smtp_sasl_security_options = noanonymous" \
"smtp_sasl_password_maps = hash:/etc/postfix/sasl_passwd" \
"smtp_use_tls = yes" \
"smtp_tls_security_level = encrypt" \
"smtp_tls_note_starttls_offer = yes" \
"smtp_tls_fingerprint_digest = sha256" \
"myhostname = onr-research.com" 

echo "[email-smtp.$AWS_DEFAULT_REGION.amazonaws.com]:587 $USERNAME:$PASSWORD" >  /etc/postfix/sasl_passwd

postmap hash:/etc/postfix/sasl_passwd

chown root:root /etc/postfix/sasl_passwd /etc/postfix/sasl_passwd.db
chmod 0600 /etc/postfix/sasl_passwd /etc/postfix/sasl_passwd.db

postconf -e 'smtp_tls_CAfile = /etc/ssl/certs/ca-bundle.crt'

postconf -e 'smtpd_client_restrictions = permit_mynetworks,reject'
postconf -e 'mynetworks_style = host'

# Set root email to sysadmin@mobomo.com
sed -i 's/system.administrator@mail.mil/sysadmin@mobomo.com/g' /etc/aliases
newaliases

postfix start
postfix reload
postfix flush