FROM    redis:7.4
LABEL   maintainer="Arnaud P <arnaud@xpressive.io>"

RUN     mkdir -p /.docker-scripts
COPY    .docker/images/redis/scripts /.docker-scripts/

HEALTHCHECK --interval=5s --timeout=1s --retries=3 \
    CMD     ["/.docker-scripts/healthcheck.sh"]
