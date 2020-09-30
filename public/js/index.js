/**
 * Grab the list of images as JSON and append the new images into the container as cards
 */
const fetchList = () => {
  $.getJSON("images", (imageList) => {
    $.each(imageList, (key, imageItem) => {
      if (!$(`#image-card-${imageItem.filename}`).length) {
        let imageCard = `<div class="card" style="width: 18rem;" id="image-card-${imageItem.filename}">
            <img class="card-img-top" src="https://images-dfd.s3-eu-west-1.amazonaws.com/${imageItem.filename}.${imageItem.extension}" alt="Card image" loading="lazy">
            <div class="card-body">
              <h5 class="card-title">${imageItem.filename}</h5>
              <p class="card-text">ctime: ${imageItem.ctime}</p>
            </div>
          </div>`
        
        $(imageCard).appendTo("#image-list-container")
      }
    })

    setTimeout(fetchList, 2000)
  })
}

$(function() {
  fetchList()
})