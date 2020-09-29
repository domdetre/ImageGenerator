#!/bin/bash

aws dynamodb create-table \
  --table-name ImageList \
  --attribute-definitions AttributeName=filename,AttributeType=S \
  --key-schema AttributeName=filename,KeyType=HASH \
  --billing-mode PAY_PER_REQUEST \
  --endpoint-url http://dynamodb:8000

aws dynamodb put-item \
  --table-name ImageList \
  --item '{"filename": {"S": "TEST1"}}' \
  --endpoint-url http://dynamodb:8000

aws dynamodb put-item \
  --table-name ImageList \
  --item '{"filename": {"S": "TEST2"}}' \
  --endpoint-url http://dynamodb:8000

aws dynamodb scan \
  --table-name ImageList \
  --endpoint-url http://dynamodb:8000