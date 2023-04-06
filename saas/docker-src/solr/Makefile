.PHONY: all clean

build:
	docker build -t stevenlafl/rh8-solr --progress plain .

run:
	docker run -it -v $(shell pwd)/build:/root/solr/solr/packaging/distributions stevenlafl/rh8-solr bash