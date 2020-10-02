let AWS = require('aws-sdk')
let https = require('follow-redirects').https

/**
 * Tha handler function to grab a random image, put it in S3 and put a related entry into dynamodb
 * @param {object} event 
 * @param {object} context 
 * @returns null
 */
const imageGenerator = async (event, context) => {
  let ddb = new AWS.DynamoDB({apiVersion: '2012-08-10'})

  for (let record of event.Records) {
    const { body: filename } = record
    console.log('filename:', filename)

    await getRandomImage(filename)
    
    await ddb.putItem({
      TableName: process.env.DYNAMODB_TABLE,
      Item: {
        filename: {
          S: filename
        },
        extension: {
          S: 'jpg'
        },
      }
    }, function(err, data) {
      if (err) console.log("DynamoDB::putItem", err)
      else     console.log("DynamoDB::putItem", data)
    }).promise()
  }

  return {}
}

/**
 * The handler function to delete the image from S3 and remove the entry from dynamodb
 * @param {object} event 
 * @param {object} context 
 * @returns null
 */
const imageDeleter = async (event, context) => {
  for (let record of event.Records) {
    const { body: filename } = record
    console.log('filename:', filename)

    await Promise.all([
      deleteImageFromS3(filename),
      deleteImageFromDynamodb(filename)
    ])
  }

  return null
}

/**
 * Deletes an entry from dynamodb
 * @param {string} filename 
 */
const deleteImageFromDynamodb = async (filename) => {
  let ddb = new AWS.DynamoDB({apiVersion: '2012-08-10'})

  return ddb.deleteItem({
    TableName: process.env.DYNAMODB_TABLE,
    Key: {
      filename: {
        S: filename
      }
    }
  }, function(err, data) {
    if (err) console.log("Error: DynamoDB::deleteItem", err)
    else     console.log("Success: DynamoDB::deleteItem", data)
  }).promise()
}

/**
 * Deletes an image from S3
 * @param {string} filename 
 */
const deleteImageFromS3 = async (filename) => {
  let s3 = new AWS.S3()

  let params = {
    Bucket: process.env.S3_BUCKET_IMAGES,
    Key: filename + '.jpg'
  }  

  return s3.deleteObject(params, function(err, data) {
    if (err) console.log('ERROR: S3::deleteObject', err.stack)
    else     console.log('SUCCESS: S3::deleteObject', data)
  }).promise()
}

/**
 * Gets a random image from picsum.photos and stores it in S3
 * @param {string} filename 
 */
const getRandomImage = (filename) => {
  return new Promise((resolve, reject) => {
    const req = https.get(
      'https://picsum.photos/300/200.jpg', 
      (res) => {
        if (res.statusCode < 200 || res.statusCode >= 400) {
          return reject(new Error('statusCode=' + res.statusCode))
        }

        let s3 = new AWS.S3()
        let imageData = '' 
      
        res.setEncoding('binary')
    
        res.on('data', function(chunk){
          imageData += chunk
        })
    
        res.on('end', function(){
          let params = {
            Bucket: 'images-dfd',
            Key: filename + '.jpg',
            Body: Buffer.from(imageData, 'binary'),
            ACL: 'public-read'
          }    
    
          resolve(s3.putObject(params, function(err, data) {
            if (err) console.log('ERROR: S3::putObject', err.stack)
            else     console.log('SUCCESS: S3::putObject', data)
          }).promise())
        })
      }
    )

    req.on('ERROR: https.get', (e) => {
      reject(e.message)
    })

    req.end()
 })
}

module.exports = {
  imageGenerator,
  imageDeleter
}