let f = require('./functions')

/**
 * Tha handler function to grab a random image, put it in S3 and put a related entry into dynamodb
 * @param {object} event 
 * @param {object} context 
 * @returns null
 */
const imageGenerator = async (event, context) => {
  for (let record of event.Records) {
    const { body: filename } = record
    console.log('filename:', filename)

    await f.getRandomImage(filename)
    await f.putImageIntoDynamodb(filename)
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
      f.deleteImageFromS3(filename),
      f.deleteImageFromDynamodb(filename)
    ])
  }

  return null
}

module.exports = {
  imageGenerator,
  imageDeleter
}