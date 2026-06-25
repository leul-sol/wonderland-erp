FROM alpine:3.20

RUN apk add --no-cache bash curl mysql-client tzdata

COPY check-stack.sh monitor-loop.sh /opt/monitor/
RUN chmod +x /opt/monitor/*.sh

WORKDIR /opt/monitor

CMD ["/bin/sh", "/opt/monitor/monitor-loop.sh"]
