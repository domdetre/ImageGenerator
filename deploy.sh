#!/bin/bash

set -a
. ./.env.local
set +a

stage=${1-'dev'}

appVersion=`date +%F_%H-%M-%S`
filename="ImageConverter_${stage}_${appVersion}.zip"

zip \
	-x ".temp/*" \
	-x ".serverless/*" \
	-x ".git/*" \
	-x "node_modules/*" \
	-x "/var/*" \
	-x "docker/*" \
  -x "lambda-layer/*" \
  -x ".env.local" \
	-r .temp/$filename ./ \
		|| exit 1

aws s3 cp .temp/$filename s3://$S3_BUCKET/$filename \
	|| exit 1

# rm .temp/$filename

npx sls deploy --S3Bucket $S3_BUCKET --S3ApplicationVersionKey $filename --stage $stage