jest.mock('./functions')

let f = require('./functions')
let h = require('./handlers')

describe('imageGenerator', () => {
  beforeAll(async () => {
    f.getRandomImage.mockReturnValue(Promise.resolve())
    f.putImageIntoDynamodb.mockReturnValue(Promise.resolve())
  })

  describe('GIVEN I have an event with 1 Record with a filename', () => {
    let filename = 'testfilename'
    let event = {
      Records: [{
        body: filename
      }]
    }

    describe('WHEN the function imageGenerator has been called with the event', () => {
      beforeAll(async () => {
        return h.imageGenerator(event)
      })

      test('THEN function getRandomImage should be called once', async () => {
        expect(f.getRandomImage).toHaveBeenCalledTimes(1)
      })

      test('THEN function getRandomImage should be called with the filename', async () => {
        expect(f.getRandomImage).toHaveBeenCalledWith(filename)
      })

      test('THEN function putImageIntoDynamodb should be called once', async () => {
        expect(f.putImageIntoDynamodb).toHaveBeenCalledTimes(1)
      })

      test('THEN function putImageIntoDynamodb should be called with the filename', () => {
        expect(f.putImageIntoDynamodb).toHaveBeenCalledWith(filename)
      })
    })
  })
})

describe('imageDeleter', () => {
  beforeAll(async () => {
    f.deleteImageFromS3.mockReturnValue(Promise.resolve())
    f.deleteImageFromDynamodb.mockReturnValue(Promise.resolve())
  })

  describe('GIVEN I have an event with 1 Record', () => {
    let event = {
      Records: [{
        body: 'testfilename'
      }]
    }

    describe('WHEN the function imageDeleter has been called', () => {
      beforeAll(async () => {
        return h.imageGenerator()
      })

      test('THEN function deleteImageFromS3 should be called once', async () => {
        expect(f.deleteImageFromS3).toHaveBeenCalledTimes(1)
      })

      test('THEN function deleteImageFromS3 should be called with the filename', async () => {
        expect(f.deleteImageFromS3).toHaveBeenCalledWith(filename)
      })

      test('THEN function putImageIntoDynamodb should be called once', async () => {
        expect(f.deleteImageFromDynamodb).toHaveBeenCalledTimes(1)
      })

      test('THEN function putImageIntoDynamodb should be called with the filename', async () => {
        expect(f.deleteImageFromDynamodb).toHaveBeenCalledWith(filename)
      })
    })
  })
})