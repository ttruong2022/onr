include .env
export

RepositoryUri = $(shell aws cloudformation list-exports  --query "Exports[?Name==\`$(TOOLCHAIN_NAME)-RepositoryUri\`].Value" --output text)
SESSecret = $(shell aws cloudformation list-exports  --query "Exports[?Name==\`$(TOOLCHAIN_NAME)-SESSecret\`].Value" --output text)
# SESUserKeyID = $(shell aws cloudformation list-exports  --query "Exports[?Name==\`$(TOOLCHAIN_NAME)-SESUserKeyID\`].Value" --output text)
# SESUserSecretKey = $(shell aws cloudformation list-exports  --query "Exports[?Name==\`$(TOOLCHAIN_NAME)-SESUserSecretKey\`].Value" --output text)

DefaultVPC = $(aws ec2 describe-vpcs \
    --filters Name=isDefault,Values=true \
    --query 'Vpcs[*].VpcId' \
    --output text)

toolchain-test:
	aws cloudformation validate-template --template-body file://toolchain/toolchain.yaml

toolchain-deploy: update
	aws cloudformation deploy --template-file toolchain/toolchain.yaml --stack-name "$(TOOLCHAIN_NAME)" --parameter-overrides DomainName="$(DOMAIN_NAME)" TemplateBucket="$(CF_BUCKET)" --capabilities CAPABILITY_IAM CAPABILITY_NAMED_IAM

imagebuilder-prepare:
	docker run -it \
        -v $(shell pwd)/../:/var/www \
        --workdir /var/www \
        -e APP_VERSION=$(APP_VERSION) \
        -e AWS_ACCESS_KEY_ID=`cat ~/.aws-builder` \
        -e AWS_SECRET_ACCESS_KEY=`cat ~/.aws-builder2` \
        --entrypoint sh \
        mobomo/drupalstand-ci:php-8-redhat \
        -c ./scripts/prepare.sh

imagebuilder-build:
	aws codebuild

imagebuilder-deploy: update
	aws cloudformation deploy --template-file toolchain/imagebuilder.yaml --stack-name "$(TOOLCHAIN_NAME)-imagebuilder" --parameter-overrides ToolchainStack="$(TOOLCHAIN_NAME)" DomainName="$(DOMAIN_NAME)" SESSecret="$(SESSecret)" ImageTag="${IMAGE_TAG}" SolrTag="${SOLR_TAG}" BaseTag="${BASE_TAG}" --capabilities CAPABILITY_IAM CAPABILITY_NAMED_IAM

# imagebuilder-deploy2: update
# 	aws cloudformation deploy --template-file toolchain/imagebuilder2.yaml --stack-name "$(TOOLCHAIN_NAME)-imagebuilder2" --parameter-overrides ToolchainStack="$(TOOLCHAIN_NAME)" AppVersion="${APP_VERSION}" BaseTag="${BASE_TAG}" --capabilities CAPABILITY_IAM CAPABILITY_NAMED_IAM

drupal-ecr-login:
	aws ecr get-login-password \
        --region us-east-1 | docker login \
        --username AWS \
        --password-stdin \
        $(RepositoryUri)

drupal-pull:
	git -C services/drupal/src/drupal pull

drupal-build:
	docker build \
        --progress plain \
        -f ./services/drupal/src/Dockerfile \
        -t ecr/drupal ../

drupal-test:
	docker run -it \
        -p 80:80 \
        ecr/drupal

drupal-push:
	docker tag \
        ecr/drupal \
        $(RepositoryUri):latest
	docker tag \
        ecr/drupal \
        $(RepositoryUri):${APP_VERSION}
	docker push \
        $(RepositoryUri):latest
	docker push \
		$(RepositoryUri):${APP_VERSION}

update:
	aws s3 sync infrastructure s3://${CF_BUCKET}/infrastructure --delete
	aws s3 sync services s3://${CF_BUCKET}/services --exclude "*" --include "*.yaml" --delete
	
test:
	./scripts/validate-templates.sh

lambda-build:
	./scripts/lambdas.sh

deploy: lambda-build update
	aws cloudformation deploy --template-file master.yaml --stack-name "${ENVIRONMENT}" --parameter-overrides ToolchainStack="$(TOOLCHAIN_NAME)" DomainName="$(DOMAIN_NAME)" TemplateBucket="$(CF_BUCKET)" ImageTag="${IMAGE_TAG}" SolrTag="${SOLR_TAG}" BaseTag="${BASE_TAG}" SESUserSecretKey="$(SESUserKeyID)" SESUserSecretKey="$(SESUserSecretKey)" --capabilities CAPABILITY_IAM CAPABILITY_NAMED_IAM

cf-test:
	aws cloudformation deploy --template-file test.yaml --stack-name test --parameter-overrides ToolchainStack="$(TOOLCHAIN_NAME)" --capabilities CAPABILITY_IAM CAPABILITY_NAMED_IAM

run-drush-commands-stage:
	./scripts/exec.sh "stage-web" 1 "drush sql-dump --gzip --result-file=/root/stage-$(APP_VERSION).sql"
	./scripts/exec.sh "stage-web" 1 "drush cr"
	./scripts/exec.sh "stage-web" 1 "drush sset environment_indicator.current_release $(APP_VERSION)"
	./scripts/exec.sh "stage-web" 1 "drush updb --no-cache-clear --yes"
	./scripts/exec.sh "stage-web" 1 "drush cim --yes"
	./scripts/exec.sh "stage-web" 1 "drush cim --yes"
	./scripts/exec.sh "stage-web" 1 "drush php-eval 'node_access_rebuild();' -y"
	./scripts/exec.sh "stage-web" 1 "drush cr"
	./scripts/exec.sh "stage-web" 1 "drush simple-sitemap:generate"
	./scripts/exec.sh "stage-web" 1 "drush search-api-clear"
	./scripts/exec.sh "stage-web" 1 "drush search-api-index"
	./scripts/exec.sh "stage-web" 1 "flush-drupal-cache"
	./scripts/exec.sh "stage-web" 1 "flush-redis-cache"
	./scripts/exec.sh "stage-web" all "flush-nginx-cache"

run-drush-commands-dev:
	./scripts/exec.sh "dev-web" 1 "drush sql-dump --gzip --result-file=/root/dev-$(APP_VERSION).sql"
	./scripts/exec.sh "dev-web" 1 "drush cr"
	./scripts/exec.sh "dev-web" 1 "drush sset environment_indicator.current_release $(APP_VERSION)"
	./scripts/exec.sh "dev-web" 1 "drush updb --no-cache-clear --yes"
	./scripts/exec.sh "dev-web" 1 "drush cim --yes"
	./scripts/exec.sh "dev-web" 1 "drush cim --yes"
	./scripts/exec.sh "dev-web" 1 "drush php-eval 'node_access_rebuild();' -y"
	./scripts/exec.sh "dev-web" 1 "drush cr"
	./scripts/exec.sh "dev-web" 1 "drush simple-sitemap:generate"
	./scripts/exec.sh "dev-web" 1 "drush search-api-clear"
	./scripts/exec.sh "dev-web" 1 "drush search-api-index"
	./scripts/exec.sh "dev-web" 1 "flush-drupal-cache"
	./scripts/exec.sh "dev-web" 1 "flush-redis-cache"
	./scripts/exec.sh "dev-web" all "flush-nginx-cache"
