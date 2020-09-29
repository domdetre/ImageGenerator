# Demo Application for generating random images

The user interface uses PHP with Symfony 5.1 where you can supply a number to generate an amount of random images.

The random images going to be "generated" in the background by lambda functions triggered by SQS queue messages, then the filenames will be stored in dynamodb.

The user inerface can fetch the list from dynamodb and cache the new items in memcached.

### TODO

- Utilize memchaced between the dynamodb and application
- Write docblocks
- Lazy load generated images
- phpunit testing
- jest testing