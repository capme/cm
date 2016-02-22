#!/bin/sh
docker run -ti --rm mongo mongo 172.17.0.1:27019/channelbridge -u channelbridgeuser -p channelbridge123! --authenticationDatabase admin

