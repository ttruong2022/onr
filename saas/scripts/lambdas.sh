#/bin/bash

# https://docs.aws.amazon.com/lambda/latest/dg/python-package.html
# https://docs.aws.amazon.com/lambda/latest/dg/configuration-layers.html#configuration-layers-path

if [ ! -f infrastructure/lambda/layers/pymysql.layer.zip ]; then
    mkdir -p infrastructure/lambda/layers
    mkdir -p build/python/pymysql/python

    #pip3 install --system --target build/python/pymysql/python pymysql
    docker run --rm -v $(pwd)/build/python/pymysql:/var/task:z lambci/lambda:build-python3.6 python3.6 -m pip --isolated install -t "python" pymysql

    cd build/python/pymysql
    zip -r ../../../infrastructure/lambda/layers/pymysql.layer.zip .
fi