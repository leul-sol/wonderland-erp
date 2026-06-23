FROM mysql:8.0

COPY mysql-init/01-create-databases.sh /docker-entrypoint-initdb.d/01-create-databases.sh
RUN sed -i 's/\r$//' /docker-entrypoint-initdb.d/01-create-databases.sh \
    && chmod +x /docker-entrypoint-initdb.d/01-create-databases.sh
