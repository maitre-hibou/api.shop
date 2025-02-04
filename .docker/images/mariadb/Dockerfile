#
# We will use MariaDB as our database.
#
FROM        mariadb:lts
LABEL       maintainer="Arnaud Ponel <arnaud@xpressive.io>"

ARG         USER_ID=1000
ARG         GROUP_ID=1000

#
# Same as all others containers, we will synchronize UID and GID of mysql user to the one of our current host.
#
RUN         usermod -u $USER_ID mysql && groupmod -g $GROUP_ID mysql
RUN         chown -R mysql: /var/lib/mysql

#
# Setting up healtcheck for container as described here : https://mariadb.com/kb/en/using-healthcheck-sh/
#
HEALTHCHECK --interval=10s --timeout=5s --retries=3 \
    CMD ["healthcheck.sh", "--connect", "--innodb_initialized"]
