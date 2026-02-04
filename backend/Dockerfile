FROM debian:stable

RUN apt-get update
RUN apt-get install php-mysql -y
RUN apt-get install 7zip -y
RUN apt-get install composer -y

VOLUME /backend

WORKDIR /backend

CMD ["bash", "-c", "composer require lcobucci/jwt lcobucci/clock && openssl rand -out secret.key -base64 128 && php -S 0.0.0.0:8000"]
