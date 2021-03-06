/**
 * Grab the list of images as JSON and append the new images into the container as cards
 */
const fetchList = () => {
  $.getJSON("index.php/api/images", (imageList) => {
    // mark all for ddeletion
    $('.card').attr('delete', 'true')

    $.each(imageList, (key, imageItem) => {
      let card = $(`#image-card-${imageItem.filename}`)
      if (!card.length) {
      // if not found create it
        let imageCard = `
          <li>
            <div class="card" style="width: 18rem;" id="image-card-${imageItem.filename}" filename="${imageItem.filename}">
              <img class="card-img-top" src="https://images-dfd.s3-eu-west-1.amazonaws.com/${imageItem.filename}.${imageItem.extension}" alt="Card image" loading="lazy">
              <div class="card-body">
                <h5 class="card-title">${imageItem.filename}</h5>
                <p class="card-text">ctime: ${imageItem.ctime}</p>
              </div>
            </div>
          </li>
        `
        
        $(imageCard).appendTo("#image-list-container")
      } else {
      // if found unmark for deletion
        card.removeAttr('delete')
      }
    })

    // delete marked ones
    $('li:has(.card[delete])').remove()

    setTimeout(fetchList, 2000)
  })
}

$(function() {
  fetchList()

  $("#image_generator_Generate").click(() => {
    $.post(
      'index.php/api/images', 
      {
        numberOfImages: $('#image_generator_NumberOfImages').val()
      }
    )
  })
  
  $("#image_generator_DeleteAll").click(() => {
    $.ajax(
      'index.php/api/images',
      {
        method: 'DELETE'
      }
    )
  })
})