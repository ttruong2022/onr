#!/bin/bash

# Those not covered under Amazon RHEL STIG.
# Assumes code is located in /tmp/code

#V-230223, V-230254
# fips-mode-setup --enable

# V-230225 and V-230227
# rm -rf /etc/ssh/sshd_config
# rm -rf /etc/issue
# cp /tmp/code/saas/services/base/config/sshd_config /etc/ssh/sshd_config
# cp /tmp/code/saas/services/base/config/issue /etc/issue

#V-230229
# exported with openssl pkcs7 -in Certificates_PKCS7_v5.9_DoD.pem.p7b -print_certs -out DoD_CAs.pem
cp /tmp/code/saas/services/base/certs/DoD_CAs.pem /etc/sssd/pki/sssd_auth_ca_db.pem

# #V-230233
# echo "SHA_CRYPT_MIN_ROUNDS 5000" >> /etc/login.defs

# V-230245
# chmod 0640 /var/log/messages

# V-230251, V-230252, V-230253
# cat > /etc/crypto-policies/back-ends/opensshserver.config <<EOF
# CRYPTO_POLICY='-oCiphers=aes256-ctr,aes192-ctr,aes128-ctr -oMACs=hmac-sha2-512,hmac-sha2-256 -oGSSAPIKexAlgorithms=gss-curve25519-sha256-,gss-nistp256-sha256-,gss-group14-sha256-,gss-group16-sha512-,gss-gex-sha1-,gss-group14-sha1- -oKexAlgorithms=curve25519-sha256,curve25519-sha256@libssh.org,ecdh-sha2-nistp256,ecdh-sha2-nistp384,ecdh-sha2-nistp521,diffie-hellman-group-exchange-sha256,diffie-hellman-group14-sha256,diffie-hellman-group16-sha512,diffie-hellman-group18-sha512,diffie-hellman-group-exchange-sha1,diffie-hellman-group14-sha1 -oHostKeyAlgorithms=ecdsa-sha2-nistp256,ecdsa-sha2-nistp256-cert-v01@openssh.com,ecdsa-sha2-nistp384,ecdsa-sha2-nistp384-cert-v01@openssh.com,ecdsa-sha2-nistp521,ecdsa-sha2-nistp521-cert-v01@openssh.com,ssh-ed25519,ssh-ed25519-cert-v01@openssh.com,rsa-sha2-256,rsa-sha2-256-cert-v01@openssh.com,rsa-sha2-512,rsa-sha2-512-cert-v01@openssh.com,ssh-rsa,ssh-rsa-cert-v01@openssh.com -oPubkeyAcceptedKeyTypes=ecdsa-sha2-nistp256,ecdsa-sha2-nistp256-cert-v01@openssh.com,ecdsa-sha2-nistp384,ecdsa-sha2-nistp384-cert-v01@openssh.com,ecdsa-sha2-nistp521,ecdsa-sha2-nistp521-cert-v01@openssh.com,ssh-ed25519,ssh-ed25519-cert-v01@openssh.com,rsa-sha2-256,rsa-sha2-256-cert-v01@openssh.com,rsa-sha2-512,rsa-sha2-512-cert-v01@openssh.com,ssh-rsa,ssh-rsa-cert-v01@openssh.com -oCASignatureAlgorithms=ecdsa-sha2-nistp256,ecdsa-sha2-nistp384,ecdsa-sha2-nistp521,ssh-ed25519,rsa-sha2-256,rsa-sha2-512,ssh-rsa'
# SSH_USE_STRONG_RNG=32
# EOF

# V-230257
# removes dangling symlinks
# find . -xtype l -exec rm {} \;

# V-230260
# find -L /lib /lib64 /usr/lib /usr/lib64 -perm /022 -type f -exec chmod 755 {} \;

# # V-230263
# cat > /etc/cron.daily/aide <<EOF
# #!/bin/bash

# /usr/sbin/aide --check | /bin/mail -s "$HOSTNAME - Daily aide integrity check run" sysadmin@mobomo.com
# EOF

# V-230274, V-230355
# cat > /etc/sssd/sssd.conf <<EOF
# [sssd]
# config_file_version = 2
# services = pam, sudo, ssh
# domains = testing.test
# certificate_verification = ocsp_dgst=sha1

# [pam]
# pam_cert_auth = True

# [domain/testing.test]
# id_provider = ldap

# [certmap/testing.test/rule_name]
# matchrule =<SAN>.*EDIPI@mil
# maprule = (userCertificate;binary={cert!bin})
# domains = testing.test
# EOF
cat > /etc/sssd/sssd.conf <<EOF
[sssd]
config_file_version = 2
services = pam, sudo, ssh
domains = testing.test
certificate_verification = ocsp_dgst=sha1

[pam]
pam_cert_auth = True
EOF

chmod 0600 /etc/sssd/sssd.conf 

# V-230287
chmod 0600 /etc/ssh/ssh_host*key
systemctl restart sshd.service

# V-230332, V-230333, V-230334, V-230356, V-230368
# cat >> /etc/pam.d/system-auth <<EOF
# auth required pam_faillock.so preauth dir=/var/log/faillock silent audit deny=3 even_deny_root fail_interval=900 unlock_time=0
# auth required pam_faillock.so authfail dir=/var/log/faillock unlock_time=0
# auth sufficient pam_sss.so try_cert_auth
# auth [success=done authinfo_unavail=ignore ignore=ignore default=die] pam_sss.so try_cert_auth
# EOF

# cat >> /etc/pam.d/password-auth <<EOF
# auth required pam_faillock.so preauth dir=/var/log/faillock silent audit deny=3 even_deny_root fail_interval=900 unlock_time=0
# auth required pam_faillock.so authfail dir=/var/log/faillock unlock_time=0
# auth sufficient pam_sss.so try_cert_auth
# account required pam_faillock.so
# password required pam_pwquality.so
# password required pam_pwhistory.so use_authtok remember=5 retry=3
# EOF



cat >> /etc/pam.d/system-auth <<EOF
auth sufficient pam_sss.so try_cert_auth
EOF

cat >> /etc/pam.d/password-auth <<EOF
auth [success=done authinfo_unavail=ignore ignore=ignore default=die] pam_sss.so try_cert_auth
EOF

# V-230325
chmod 0600 /home/ec2-user/.bash*
chmod 0600 /home/ssm-user/.bash*

# V-230339
echo "dir = /var/log/faillock" >> /etc/security/faillock.conf

# V-230341
sed -i 's/# silent/silent/g' /etc/security/faillock.conf

# V-230343
sed -i 's/# audit/audit/g' /etc/security/faillock.conf

# V-230350
sed -i '/tmux/d' /etc/shells

# # V-230366
# sed -i '/PASS_MAX_DAYS\t99999/c PASS_MAX_DAYS 60' /etc/login.defs

# V-230367
# chage -M 60 fapolicyd

# # V-230373
# useradd -D -f 35

# # V-230379 remove unused accounts
userdel -r games
userdel -r ftp

# # V-230381
# sed -i '/pam_lastlog.so silent/d' /etc/pam.d/postlogin

# # V-230382
# sed -i '/#PrintLastLog yes/c PrintLastLog yes' /etc/ssh/sshd_config

# # V-230385
# sed -i -e '/umask 022/c umask 077' -e '/umask 002/c umask 077' /etc/bashrc
# sed -i -e '/umask 022/c umask 077' -e '/umask 002/c umask 077' /etc/csh.cshrc
# sed -i -e '/umask 022/c umask 077' -e '/umask 002/c umask 077' /etc/profile

# V-230466
echo "-w /var/log/faillock -p wa -k logins" >> /etc/audit/rules.d/audit.rules

# V-230475
# cat >> /etc/aide.conf <<EOF
# # Audit Tools
# /usr/sbin/auditctl p+i+n+u+g+s+b+acl+xattrs+sha512
# /usr/sbin/auditd p+i+n+u+g+s+b+acl+xattrs+sha512
# /usr/sbin/ausearch p+i+n+u+g+s+b+acl+xattrs+sha512
# /usr/sbin/aureport p+i+n+u+g+s+b+acl+xattrs+sha512
# /usr/sbin/autrace p+i+n+u+g+s+b+acl+xattrs+sha512
# /usr/sbin/rsyslogd p+i+n+u+g+s+b+acl+xattrs+sha512
# /usr/sbin/augenrules p+i+n+u+g+s+b+acl+xattrs+sha512
# EOF

# V-230484
# sed -i '/prefer/c server 0.us.pool.ntp.mil prefer iburst minpoll 4 maxpoll 4' /etc/chrony.conf

# # V-230493 through V-230499, V-230503
# cat >> /etc/modprobe.d/blacklist.conf <<EOF
# install uvcvideo /bin/true
# blacklist uvcvideo
# install atm /bin/true
# blacklist atm
# install can /bin/true
# blacklist can
# install sctp /bin/true
# blacklist sctp
# install tipc /bin/true
# blacklist tipc
# install cramfs /bin/true
# blacklist cramfs
# install firewire-core /bin/true
# blacklist firewire-core
# install usb-storage /bin/true
# blacklist usb-storage
# EOF

# # V-230504
cp /usr/lib/firewalld/zones/drop.xml /etc/firewalld/zones/restricted.xml
firewall-cmd --reload
firewall-cmd --permanent --zone=restricted --add-service=ssh
firewall-cmd --reload
firewall-cmd --set-default-zone=restricted

# V-230524
usbguard generate-policy > /etc/usbguard/rules.conf

# # V-230527
# sed -i '/RekeyLimit/c RekeyLimit 1G 1h' /etc/ssh/sshd_config

# # V-230544
# echo "net.ipv6.conf.all.accept_redirects = 0" >> /etc/sysctl.d/99-sysctl.conf

# # V-230551
# sed -i '/CONTENT = sha512+ftype/c CONTENT = sha512+ftype+xattrs+acl' /etc/aide.conf

# # V-230555
# sed -i '/X11Forwarding/c X11Forwarding no' /etc/ssh/sshd_config

# # V-230556
# sed -i '/X11UseLocalhost/c X11Forwarding yes' /etc/ssh/sshd_config

# V-244524
sed -i '/ClientAliveCountMax/c ClientAliveCountMax 0' /etc/ssh/sshd_config

# V-244525
sed -i '/ClientAliveInterval/c ClientAliveInterval 600' /etc/ssh/sshd_config

# # V-244528
# sed -i '/GSSAPIAuthentication/c GSSAPIAuthentication no' /etc/ssh/sshd_config

# V-244540
# sed -i 's/ nullok//g' /etc/pam.d/system-auth

# # V-244541
# sed -i 's/ nullok//g' /etc/pam.d/password-auth

# V-244543
# sed -i 's/space_left_action = SYSLOG/space_left_action = email/g' /etc/audit/auditd.conf

# # V-244546
echo "deny perm=any all : all" >> /etc/fapolicyd/fapolicyd.rules

# V-250315
mkdir /var/log/faillock
semanage fcontext -a -t faillog_t "/var/log/faillock(/.*)?"
restorecon -R -v /var/log/faillock

# sed -i 's/pam_faillock.so preauth/pam_faillock.so preauth dir=\/var\/log\/faillock/g' /etc/pam.d/password-auth
# sed -i 's/pam_faillock.so authfail/pam_faillock.so authfail dir=\/var\/log\/faillock/g' /etc/pam.d/password-auth

# V-250317
echo "net.ipv4.conf.all.forwarding = 0" >> /etc/sysctl.d/99-sysctl.conf

# V-251716
# echo "retry = 3" >> /etc/security/pwquality.conf
# sed -i 's/pam_pwquality.so retry=3/pam_pwquality.so/g' /etc/pam.d/system-auth
# sed -i 's/pam_pwquality.so retry=3/pam_pwquality.so/g' /etc/pam.d/password-auth

# Set up aide scanning default db
# https://www.digitalocean.com/community/tutorials/how-to-install-aide-on-a-digitalocean-vps
# aide --init
# mv /var/lib/aide/aide.db.new.gz /var/lib/aide/aide.db.gz

# Related to ClamAV. Use McAfee and skip this if building for production.
freshclam

# fix perms for clamd.
# freshclam service will set the correct perms, but freshclam is run above because
#   we want to be sure it has run fully before the image is finished building
chmod 0644 /var/lib/clamav/*

systemctl enable clamav-freshclam
systemctl start clamav-freshclam

mkdir /var/log/clamav
cp /tmp/code/saas/services/base/scripts/clamscan_daily.sh /etc/cron.daily/clamscan_daily
chmod 0700 /etc/cron.daily/clamscan_daily

# aide ignore cloudwatch log bins
# echo '!/opt/aws/amazon-cloudwatch-agent/logs/' >> /etc/aide.conf

# aide database reset at boot
# cat << EOF > /root/aide.sh
#   /sbin/aide --init
#   rm -rf /var/lib/aide/aide.db.gz
#   mv /var/lib/aide/aide.db.new.gz /var/lib/aide/aide.db.gz
# EOF

# chmod 0700 /root/aide.sh

# cat << EOF >> /etc/crontab
# @reboot /root/aide.sh
# EOF

# rotate audit logs
cp /usr/share/doc/audit/auditd.cron /etc/cron.daily/auditd.cron
chmod +x /etc/cron.daily/auditd.cron

systemctl restart crond

# required to get imagebuilder to clean files after the stig is in place
# because they decided to use sudo as root user
# not necessary for building manually with EC2
sed -i '/Allow root to run any commands anywhere/!b;n;croot    ALL=(ALL)       NOPASSWD: ALL' /etc/sudoers