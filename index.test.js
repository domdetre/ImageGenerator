jest.mock('follow-redirects')

let https = require('follow-redirects').https
//import { https } from 'follow-redirects'
let index = require('./index')

describe('getImage', () => {
  beforeAll(async () => {
    https.get.mockReturnValue('fail')
  })

  test('test1', async () => {
    let result = await index.getImage()
    expect(result).to.be(0)
  })
})

describe('imageGenerator', () => {
  beforeAll(async () => {
    https.get.mockReturnValue('fail')
  })

  test('test1', async () => {
    let result = await index.getImage()
    expect(result).to.be(0)
  })
})