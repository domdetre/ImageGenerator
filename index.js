let AWS = require('aws-sdk')
let https = require('follow-redirects').https

const imageGenerator = async (event, context) => {
  let s3 = new AWS.S3()
  let ddb = new AWS.DynamoDB({apiVersion: '2012-08-10'})

  for (let record of event.Records) {
    const { filename: body } = record
    console.log('body:', filename)

    await getImage(filename)
    
    await ddb.putItem({
      TableName: 'ImageList',
      Item: {
        filename: filename,
        extension: 'jpg',
      }
    }, function(err, data) {
      if (err) {
        console.log("Error", err)
      } else {
        console.log("Success", data)
      }
    }).promise()
  }

  return {}
}

function getImage(filename) {
  return new Promise((resolve, reject) => {
    const req = https.get(
      'https://picsum.photos/200/300.jpg', 
      (res) => {
        if (res.statusCode < 200 || res.statusCode >= 400) {
          return reject(new Error('statusCode=' + res.statusCode))
        }

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
            if (err) console.log(err, err.stack)
            else     console.log(data)
            console.log('s3 finished')
          }).promise())
        })
      }
    )

    req.on('error', (e) => {
      reject(e.message)
    })

    req.end()
 })
}

module.exports = {
  imageGenerator
}