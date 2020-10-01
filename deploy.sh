#!/bin/bash

source alias.sh

set -a
. ./.env.local
set +a

stage=${1-'dev'}

appVersion=`date +%F_%H-%M-%S`
filename="ImageConverter_${stage}_${appVersion}.zip"

zip \
	-r .temp/$filename \
    .ebextensions \
    bin/console \
    config \
    public \
    src \
    templates \
    .env \
    composer* \
    vendor \
	|| exit 1

aws s3 cp .temp/$filename s3://$S3_BUCKET/$filename \
	|| exit 1

# rm .temp/$filename

npx sls deploy --S3Bucket $S3_BUCKET --S3ApplicationVersionKey $filename --stage $stage