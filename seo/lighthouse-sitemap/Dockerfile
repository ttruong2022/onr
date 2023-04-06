FROM node:gallium-alpine

RUN apk add chromium --repository=http://dl-cdn.alpinelinux.org/alpine/v3.15/main
RUN apk add jq parallel
RUN npm install -g lighthouse

VOLUME /app
WORKDIR /app