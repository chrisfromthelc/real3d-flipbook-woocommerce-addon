/*
author http://codecanyon.net/user/creativeinteractivemedia
*/
// document.addEventListener("DOMContentLoaded", function () {
//   const flipbookThumbnails = document.querySelectorAll(
//     ".r3d-thumbs-wrapper .r3d-thumb"
//   );

//   function updateSelection(thumb) {
//     const selectedId = thumb.getAttribute("data-id");
//     const isPreview = thumb
//       .closest(".r3d-thumbs-wrapper")
//       .classList.contains("preview-flipbook");

//     thumb
//       .closest(".r3d-thumbs-wrapper")
//       .querySelectorAll(".r3d-thumb")
//       .forEach((t) => t.classList.remove("r3d-thumb-selected"));

//     thumb.classList.add("r3d-thumb-selected");

//     if (isPreview) {
//       document.getElementById("r3d_preview_flipbook_id").value = selectedId;
//     } else {
//       document.getElementById("r3d_flipbook_id").value = selectedId;
//     }
//   }

//   flipbookThumbnails.forEach((thumb) => {
//     thumb.addEventListener("click", function () {
//       updateSelection(this);
//     });
//   });

//   const selectedThumbnail = document.querySelector(".r3d-thumb-selected");
//   if (selectedThumbnail) {
//     selectedThumbnail.scrollIntoView();
//   }
// });

document.addEventListener("DOMContentLoaded", function () {
  const flipbookThumbnails = document.querySelectorAll(
    ".r3d-thumbs-wrapper .r3d-thumb"
  );

  function updateSelection(thumb) {
    const selectedId = thumb.getAttribute("data-id");
    const isPreview = thumb
      .closest(".r3d-thumbs-wrapper")
      .classList.contains("preview-flipbook");

    if (thumb.classList.contains("r3d-thumb-selected")) {
      thumb.classList.remove("r3d-thumb-selected");
    } else {
      thumb.classList.add("r3d-thumb-selected");
    }

    const selectedThumbs = Array.from(
      thumb
        .closest(".r3d-thumbs-wrapper")
        .querySelectorAll(".r3d-thumb-selected")
    ).map((t) => t.getAttribute("data-id"));

    const selectedValues = selectedThumbs.join(";");

    if (isPreview) {
      document.getElementById("r3d_preview_flipbook_id").value = selectedValues;
    } else {
      document.getElementById("r3d_flipbook_id").value = selectedValues;
    }
  }

  flipbookThumbnails.forEach((thumb) => {
    thumb.addEventListener("click", function () {
      updateSelection(this);
    });
  });

  const selectedThumbnail = document.querySelector(".r3d-thumb-selected");
  if (selectedThumbnail) {
    selectedThumbnail.scrollIntoView();
  }
});
