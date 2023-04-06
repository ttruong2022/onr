# Drupal S3FS/RDS/ElastiCache backed by PHP, MySQL and Redis with STIG'ed EC2s

Cost Estimate: https://calculator.aws/#/estimate?id=56c2b32b13fb97cc2f95eb027d5bef38e1084844

### AWS layer

Operating Systems:
- Red Hat Enterprise Linux 8.5 x86
  - arn:aws-us-gov:imagebuilder:us-gov-east-1:aws:image/red-hat-enterprise-linux-8-x86/2022.2.1
  - ami-019599717e2dd5baa

AWS Managed:
- elasticsearch 7.10
- mariadb 10.5.12
- redis 6.2.5

Infrastructure
- db.t4g.micro
- cache.t4g.micro
- 4x t3a.medium

Software
- php 8.0.19
- nginx 1.14.1
- solr 8.11.1 (remediated log4j to 2.17.2)

## Pre-reqs:
- AWS CLI https://docs.aws.amazon.com/cli/latest/userguide/getting-started-install.html
- AWS SSM https://docs.aws.amazon.com/systems-manager/latest/userguide/session-manager-working-with-install-plugin.html

## Setup
- Create a Route53 Zone and 'toolchain' S3 bucket
- Create an `.env` file with

```
DOMAIN_NAME="onr-research.com"
CF_BUCKET="onr-stacks"
TOOLCHAIN_NAME="toolchain"
APP_VERSION="1.6.1"
BASE_TAG="1.6.0"
IMAGE_TAG="1.6.1"
SOLR_TAG="1.6.0"
ENVIRONMENT = "stage"
```

- Run the toolchain template to create SES

```
make toolchain-deploy
```

- Build the RHEL AMIs with the code packaged

```
make imagebuilder-prepare
make imagebuilder-deploy
```

- To test the templates, update the s3 bucket with the templates, and deploy the cloudformation stack (respectively)

```
make test
make update
aws iam create-service-linked-role --aws-service-name es.amazonaws.com
make deploy
```
