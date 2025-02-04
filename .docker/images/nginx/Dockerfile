#
# For our web container image, we will use Nginx mainline as webserver.
#
FROM    nginx:mainline
LABEL   maintainer="Arnaud P <arnaud@xpressive.io>"

#
# Here we define some useful arguments and environments variable used throughout our image
#
ARG         USER_ID=1000
ARG         GROUP_ID=1000
ARG         WORKDIR=/var/www

#
# The same way as our application image, we synchronize the uid/gid of current user to the one that will run nginx process
# inside the container.
#
RUN         usermod -u $USER_ID www-data && groupmod -g $GROUP_ID www-data

#
# We copy scripts and config that will be used in our container such as entrypoint.
#
RUN         mkdir -p /.docker-scripts
COPY        .docker/images/nginx/scripts/entrypoint.sh /.docker-scripts/

RUN         mkdir -p /.docker-config
COPY        .docker/images/nginx/config /.docker-config

#
# We set the working directory for the container
#
VOLUME      $WORKDIR
WORKDIR     $WORKDIR

#
# Finally, we declare container entrypoints, command and healthcheck
#
HEALTHCHECK --interval=10s --timeout=5s --retries=3 \
    CMD ["service", "nginx", "status"]
ENTRYPOINT  ["/.docker-scripts/entrypoint.sh"]
CMD         ["nginx", "-g", "daemon off;"]
