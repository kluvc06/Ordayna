FROM debian:stable

RUN apt-get --update full-upgrade
RUN apt-get install php-mysql 7zip composer -y

VOLUME /web_server

WORKDIR /web_server

CMD ["bash", "-c", "composer require lcobucci/jwt lcobucci/clock ramsey/uuid && php -S 0.0.0.0:8000 router.php"]
