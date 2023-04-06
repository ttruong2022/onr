#!/bin/bash

parted --script /dev/nvme1n1 \ mklabel gpt \
mkpart vartmp ext4 2MB 5% \
mkpart swap linux-swap 5% 10% \
mkpart home ext4 10% 15% \
mkpart usr ext4 15% 50% \
mkpart varlogaudit ext4 50% 60% \
mkpart varlog ext4 60% 70% \
mkpart boot ext4 70% 75% \
mkpart var ext4 75% 100%

for I in 1 3 4 5 6 7 8; do mkfs.ext4 /dev/nvme1n1p${I}; done
mkswap /dev/nvme1n1p2

mkdir -p /mnt/vartmp /mnt/home /mnt/usr /mnt/varlogaudit /mnt/varlog /mnt/var /mnt/boot
mount /dev/nvme1n1p1 /mnt/vartmp
mount /dev/nvme1n1p3 /mnt/home
mount /dev/nvme1n1p4 /mnt/usr
mount /dev/nvme1n1p5 /mnt/varlogaudit
mount /dev/nvme1n1p6 /mnt/varlog
mount /dev/nvme1n1p7 /mnt/boot
mount /dev/nvme1n1p8 /mnt/var

rsync -av /var/tmp/ /mnt/vartmp/
rsync -av /home/ /mnt/home/
rsync -av /usr/ /mnt/usr/
rsync -av /var/log/audit/ /mnt/varlogaudit/
rsync -av --exclude=audit /var/log/ /mnt/varlog/
rsync -av --exclude=log --exclude=tmp /var/ /mnt/var/
rsync -av /boot/ /mnt/boot/
mkdir /mnt/var/log
mkdir /mnt/var/tmp
mkdir /mnt/var/log/audit
mkdir /mnt/varlog/audit
chmod 755 /mnt/var/log
chmod 755 /mnt/var/tmp
chmod 755 /mnt/var/log/audit
chmod 755 /mnt/varlog/audit
chmod 555 /mnt/boot

systemctl unmask tmp.mount
systemctl enable tmp.mount

cat >/etc/systemd/system/local-fs.target.wants/tmp.mount <<EOF
[Mount]
What=tmpfs
Where=/tmp
Type=tmpfs
Options=mode=1777,strictatime,noexec,nodev,nosuid
EOF

readarray -t MOUNT <<< `blkid | grep -Eo '/dev/.*: UUID="([a-Z]|[0-9]|-)*\"' | grep -Eo '([a-Z]|[0-9]|-){36}'`

ROOTUUID=`cat /etc/fstab | grep -Eo 'UUID=([a-Z]|[0-9]|-)*' | grep -Eo '([a-Z]|[0-9]|-){36}'`

# Imagebuilder
cat >>/etc/fstab <<EOF
UUID=${MOUNT[6]} /boot                    ext4    defaults,nosuid,nodev                                0 2
UUID=${MOUNT[2]} /home                   ext4    defaults,noatime,acl,user_xattr,nodev,nosuid,noexec   0 2
UUID=${MOUNT[3]} /usr                    ext4    defaults,noatime,nodev,errors=remount-ro              0 2
UUID=${MOUNT[4]} /var/log/audit          ext4    defaults,noatime,nodev,nosuid,noexec                  0 2
UUID=${MOUNT[5]} /var/log                ext4    defaults,noatime,nodev,nosuid,noexec                  0 2
UUID=${MOUNT[7]} /var                    ext4    defaults,noatime,nodev,nosuid                         0 2
UUID=${MOUNT[1]} swap                    swap    defaults                                              0 0
UUID=${MOUNT[0]} /var/tmp                ext4    defaults,noatime,nodev,nosuid,noexec                  0 0
tmpfs                                     /dev/shm                tmpfs   defaults,nodev,nosuid,noexec                    0 0
tmpfs                                     /tmp                    tmpfs   defaults,noatime,nodev,noexec,nosuid,size=256m  0 0
EOF

# # for building manually with EC2
# cat >>/etc/fstab <<EOF
# UUID=${MOUNT[7]} /boot                   ext4    defaults,nosuid                                       0 0
# UUID=${MOUNT[3]} /home                   ext4    defaults,noatime,acl,user_xattr,nodev,nosuid,noexec   0 2
# UUID=${MOUNT[4]} /usr                    ext4    defaults,noatime,nodev,errors=remount-ro              0 2
# UUID=${MOUNT[5]} /var/log/audit          ext4    defaults,noatime,nodev,nosuid,noexec                  0 2
# UUID=${MOUNT[6]} /var/log                ext4    defaults,noatime,nodev,nosuid,noexec                  0 2
# UUID=${MOUNT[8]} /var                    ext4    defaults,noatime,nodev,nosuid                         0 2
# UUID=${MOUNT[2]} swap                    swap    defaults                                              0 0
# UUID=${MOUNT[1]} /var/tmp                ext4    defaults,noatime,nodev,nosuid,noexec                  0 0
# tmpfs                                     /dev/shm                tmpfs   defaults,nodev,nosuid,noexec                    0 0
# tmpfs                                     /tmp                    tmpfs   defaults,noatime,nodev,noexec,nosuid,size=256m  0 0
# EOF

touch /.autorelabel