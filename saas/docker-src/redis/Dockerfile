FROM redhat/ubi8-minimal

RUN microdnf install -y wget tar gzip gcc gcc-c++ make cmake nano nginx procps git python3-pip
RUN microdnf install pcre-devel zlib-devel gd-devel perl-ExtUtils-Embed libxslt-devel openssl-devel perl-Test-Simple curl-devel

WORKDIR /root

RUN wget http://download.redis.io/redis-stable.tar.gz
RUN tar xvzf redis-stable.tar.gz \
  && cd redis-stable \
  && make distclean \
  && make BUILD_TLS=yes