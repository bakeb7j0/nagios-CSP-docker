FROM ubuntu:24.04
LABEL authors="Peter Nearing"


RUN apt-get update
RUN apt-get upgrade -y




ENTRYPOINT ["top", "-b"]