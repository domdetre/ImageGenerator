jest.mock('aws-sdk')
jest.mock('follow-redirects')

let f = require('./functions')

describe('deleteImageFromDynamodb', () => {
  beforeAll(() => {
    DynamoDB.deleteItem.mockReturnValue(Promise.resolve())
  })

  describe('GIVEN I have a filename', () => {
    let filename = 'test'

    describe('WHEN deleteImageFromDynamodb called with it', () => {
      beforeAll(async () => {
        return f.deleteImageFromDynamodb(filname)
      })

      test('THEN DynamoDB.deleteItem should be called once', () => {
        expect(DynamoDB.deleteItem).toHaveBeenCalledTimes(1)
      })

      test('THEN DynamoDB.deleteItem should be called with the filename', () => {
        expect(DynamoDB.deleteItem).toHaveBeenCalledWith(filename)
      })
    })
  })
})

describe('putImageIntoDynamodb', () => {
  beforeAll(() => {
    DynamoDB.putItem.mockReturnValue(Promise.resolve())
  })

  describe('GIVEN I have a filename', () => {
    let filename = 'test'

    describe('WHEN putImageIntoDynamodb called with it', () => {
      beforeAll(async () => {
        return f.putImageIntoDynamodb(filname)
      })

      test('THEN DynamoDB.putItem should be called once', () => {
        expect(DynamoDB.putItem).toHaveBeenCalledTimes(1)
      })

      test('THEN DynamoDB.putItem should be called with the filename', () => {
        expect(DynamoDB.putItem).toHaveBeenCalledWith(filename)
      })
    })
  })
})

describe('deleteImageFromS3', () => {
  beforeAll(() => {
    DynamoDB.deleteObject.mockReturnValue(Promise.resolve())
  })

  describe('GIVEN I have a filename', () => {
    let filename = 'test'

    describe('WHEN deleteImageFromS3 called with it', () => {
      beforeAll(async () => {
        return f.deleteImageFromS3(filname)
      })

      test('THEN s3.deleteObject should be called once', () => {
        expect(s3.deleteObject).toHaveBeenCalledTimes(1)
      })

      test('THEN s3.deleteObject should be called with the filename', () => {
        expect(s3.deleteObject).toHaveBeenCalledWith(filename)
      })
    })
  })
})

describe('getRandomImage', () => {
  beforeAll(() => {
    https.get.mockReturnValue(Promise.resolve())
  })

  describe('GIVEN I have a filename', () => {
    let filename = 'test'

    describe('WHEN getRandomImage called with it', () => {
      beforeAll(async () => {
        return f.getRandomImage(filname)
      })

      test('THEN https.get should be called once', () => {
        expect(https.get).toHaveBeenCalledTimes(1)
      })
    })
  })
})