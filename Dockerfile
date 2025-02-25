FROM ubuntu:24.04
LABEL authors="Peter Nearing"


RUN apt-get update
RUN apt-get upgrade -y

WORKDIR /tmp/
COPY blobs/xi-2024R1.3.3.tar.gz ./
RUN tar -xvzf xi-2024R1.3.3.tar.gz
WORKDIR /tmp/nagiosxi/
RUN 00


ENTRYPOINT ["top", "-b"]