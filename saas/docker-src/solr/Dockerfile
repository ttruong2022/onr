FROM redhat/ubi8-minimal

RUN microdnf install -y git wget unzip tar gzip nano procps

WORKDIR /root
RUN git clone https://github.com/apache/solr.git


RUN wget https://services.gradle.org/distributions/gradle-7.4.2-bin.zip \
  && unzip -d /opt/gradle gradle-7.4.2-bin.zip

ENV GRADLE_HOME=/opt/gradle/gradle-7.4.2
ENV PATH=$GRADLE_HOME/bin:$PATH

RUN microdnf install -y gcc gcc-c++ make cmake python3-pip

RUN microdnf install -y java-17-openjdk

RUN mkdir -p /usr/lib/jvm

RUN wget https://download.java.net/java/GA/jdk17.0.2/dfd4a8d0985749f896bed50d7138ee7f/8/GPL/openjdk-17.0.2_linux-x64_bin.tar.gz \
  && tar -xzf openjdk-17.0.2_linux-x64_bin.tar.gz -C /usr/lib/jvm/ \
  && ln -s /usr/lib/jvm/jdk-17.0.2 /usr/lib/jvm/jdk

ENV JAVA_HOME=/usr/lib/jvm/jdk

# RUN cd solr \
#   && ./gradlew assemble