(() => {
  (function() {
    document.addEventListener("DOMContentLoaded", function() {
      const blocks = document.querySelectorAll(".beplus-advanced-reviews[data-product-id]");
      blocks.forEach(function(block) {
        initAdvancedReviews(block);
      });
    });
    function initAdvancedReviews(block) {
      const productId = parseInt(block.dataset.productId, 10);
      if (!productId)
        return;
      let currentPage = 1;
      let currentRating = 0;
      let currentHasImages = false;
      let currentSort = "newest";
      let totalPages = 1;
      const listContainer = block.querySelector(".beplus-advanced-reviews__list");
      const loadMoreBtn = block.querySelector(".beplus-advanced-reviews__load-more");
      const loadMoreWrapper = block.querySelector(".beplus-advanced-reviews__load-more-wrapper");
      const filterStars = block.querySelectorAll(".beplus-advanced-reviews__filter-star");
      const filterImagesCheckbox = block.querySelector(".beplus-advanced-reviews__filter-images-input");
      const sortSelect = block.querySelector(".beplus-advanced-reviews__sort-select");
      const distributionArea = block.querySelector(".beplus-advanced-reviews__distribution");
      loadInitialReviews();
      loadDistribution();
      function loadInitialReviews() {
        currentPage = 1;
        fetchReviews().then(function(data) {
          if (data && listContainer) {
            listContainer.innerHTML = buildReviewList(data.reviews);
            totalPages = data.pages;
            block.dataset.totalPages = data.pages;
            updateLoadMoreButton();
          }
        });
        block.classList.remove("beplus-advanced-reviews--loading");
        block.classList.add("beplus-advanced-reviews--ready");
      }
      function loadDistribution() {
        const url = new URL(bparData.restUrl + "reviews/distribution");
        url.searchParams.set("product_id", productId);
        fetch(url.toString()).then(function(res) {
          return res.json();
        }).then(function(data) {
          if (distributionArea) {
            distributionArea.innerHTML = buildDistribution(data);
          }
        }).catch(function() {
        });
      }
      function fetchReviews() {
        var url = new URL(bparData.restUrl + "reviews");
        url.searchParams.set("product_id", productId);
        url.searchParams.set("page", currentPage);
        url.searchParams.set("sort", currentSort);
        if (currentRating > 0) {
          url.searchParams.set("rating", currentRating);
        }
        if (currentHasImages) {
          url.searchParams.set("has_images", "1");
        }
        return fetch(url.toString()).then(function(res) {
          return res.json();
        }).catch(function() {
          return null;
        });
      }
      function buildReviewList(reviews) {
        if (!reviews || !reviews.length) {
          return '<p class="beplus-advanced-reviews__no-reviews">' + bparData.i18n.noReviews + "</p>";
        }
        var html = "";
        reviews.forEach(function(review) {
          html += '<article class="beplus-advanced-reviews__review-card">';
          if (block.dataset.showAvatar !== "0") {
            html += '<div class="beplus-advanced-reviews__review-avatar">';
            if (review.avatar) {
              html += '<img src="' + escAttr(review.avatar) + '" alt="' + escAttr(review.author) + '" width="40" height="40" loading="lazy">';
            } else {
              html += '<div class="beplus-advanced-reviews__review-avatar--fallback">' + escHtml((review.author || "").charAt(0).toUpperCase()) + "</div>";
            }
            html += "</div>";
          }
          html += '<div class="beplus-advanced-reviews__review-body">';
          html += '<div class="beplus-advanced-reviews__review-header">';
          html += '<span class="beplus-advanced-reviews__review-author">' + escHtml(review.author) + "</span>";
          html += '<span class="beplus-advanced-reviews__review-rating">' + renderStars(review.rating) + "</span>";
          html += "</div>";
          html += '<div class="beplus-advanced-reviews__review-content">' + review.content + "</div>";
          if (block.dataset.showImages !== "0" && review.has_images && review.images.length) {
            html += '<div class="beplus-advanced-reviews__review-images">';
            review.images.forEach(function(img) {
              if (img.mime_type && img.mime_type.indexOf("video/") === 0) {
                html += '<video src="' + escAttr(img.url) + '" controls width="320" class="beplus-advanced-reviews__review-video"></video>';
              } else {
                html += '<a href="' + escAttr(img.url) + '" class="beplus-advanced-reviews__review-image-link" target="_blank" rel="noopener">';
                html += '<img src="' + escAttr(img.thumbnail) + '" alt="" width="80" height="80" loading="lazy" class="beplus-advanced-reviews__review-image-thumb">';
                html += "</a>";
              }
            });
            html += "</div>";
          }
          html += '<div class="beplus-advanced-reviews__review-date">' + escHtml(review.date_human) + "</div>";
          html += "</div>";
          html += "</article>";
        });
        return html;
      }
      function escHtml(str) {
        var div = document.createElement("div");
        div.appendChild(document.createTextNode(str || ""));
        return div.innerHTML;
      }
      function escAttr(str) {
        return (str || "").replace(/"/g, "&quot;").replace(/'/g, "&#39;");
      }
      function updateLoadMoreButton() {
        if (loadMoreWrapper) {
          loadMoreWrapper.style.display = currentPage < totalPages ? "" : "none";
        }
      }
      function loadMore() {
        currentPage++;
        fetchReviews().then(function(data) {
          if (data && listContainer) {
            listContainer.innerHTML += buildReviewList(data.reviews);
            totalPages = data.pages;
            block.dataset.totalPages = data.pages;
            updateLoadMoreButton();
          }
        });
      }
      function applyFilter() {
        currentPage = 1;
        fetchReviews().then(function(data) {
          if (data && listContainer) {
            listContainer.innerHTML = buildReviewList(data.reviews);
            totalPages = data.pages;
            block.dataset.totalPages = data.pages;
            updateLoadMoreButton();
          }
        });
      }
      if (loadMoreBtn) {
        loadMoreBtn.addEventListener("click", loadMore);
      }
      filterStars.forEach(function(btn) {
        btn.addEventListener("click", function() {
          var rating = parseInt(btn.dataset.rating, 10);
          var isActive = btn.getAttribute("aria-pressed") === "true";
          filterStars.forEach(function(b) {
            b.setAttribute("aria-pressed", "false");
            b.classList.remove("beplus-advanced-reviews__filter-star--active");
          });
          if (isActive) {
            currentRating = 0;
          } else {
            currentRating = rating;
            btn.setAttribute("aria-pressed", "true");
            btn.classList.add("beplus-advanced-reviews__filter-star--active");
          }
          applyFilter();
        });
      });
      if (filterImagesCheckbox) {
        filterImagesCheckbox.addEventListener("change", function() {
          currentHasImages = filterImagesCheckbox.checked;
          applyFilter();
        });
      }
      if (sortSelect) {
        sortSelect.addEventListener("change", function() {
          currentSort = sortSelect.value;
          applyFilter();
        });
      }
      initReviewForm(block);
      initMediaUpload(block);
      initPasteSupport(block);
    }
    function initReviewForm(block) {
      var form = block.querySelector(".beplus-advanced-reviews__submit-form");
      if (!form)
        return;
      var ratingInputs = form.querySelectorAll(".beplus-advanced-reviews__star-input");
      var starLabels = form.querySelectorAll(".beplus-advanced-reviews__star-label");
      ratingInputs.forEach(function(input, index) {
        input.addEventListener("change", function() {
          starLabels.forEach(function(label, i) {
            var isActive = i >= starLabels.length - parseInt(input.value);
            label.classList.toggle("beplus-advanced-reviews__star-label--active", isActive);
          });
        });
      });
      form.addEventListener("submit", function(e) {
        e.preventDefault();
        var formData = new FormData(form);
        var rating = formData.get("rating");
        var content = formData.get("content");
        if (!rating) {
          showFormMessage(block, bparData.i18n.ratingRequired, "error");
          return;
        }
        if (!content || !content.trim()) {
          showFormMessage(block, bparData.i18n.contentRequired, "error");
          return;
        }
        var data = {
          rating,
          content,
          author: formData.get("author") || "",
          email: formData.get("email") || ""
        };
        submitReview(block, data, form);
      });
    }
    function initMediaUpload(block) {
      var form = block.querySelector(".beplus-advanced-reviews__submit-form");
      if (!form)
        return;
      var previewContainer = form.querySelector(".beplus-advanced-reviews__media-preview");
      var mediaFiles = [];
      block._bparMediaFiles = mediaFiles;
      var uploadZone = form.querySelector(".beplus-advanced-reviews__upload-zone");
      var fileInput = form.querySelector(".beplus-advanced-reviews__upload-zone-input");
      if (fileInput) {
        fileInput.addEventListener("change", function() {
          if (!fileInput.files)
            return;
          for (var i = 0; i < fileInput.files.length; i++) {
            var file = fileInput.files[i];
            var isImage = file.type && file.type.indexOf("image/") === 0;
            var isVideo = file.type && file.type.indexOf("video/") === 0;
            if (isImage || isVideo) {
              mediaFiles.push({ file, removed: false });
            }
          }
          fileInput.value = "";
          updatePreviews();
        });
      }
      if (uploadZone && fileInput) {
        uploadZone.addEventListener("click", function(e) {
          if (e.target === fileInput)
            return;
          fileInput.click();
        });
      }
      if (uploadZone) {
        uploadZone.addEventListener("dragover", function(e) {
          e.preventDefault();
          e.stopPropagation();
          uploadZone.classList.add("beplus-advanced-reviews__upload-zone--dragover");
        });
        uploadZone.addEventListener("dragleave", function(e) {
          e.preventDefault();
          e.stopPropagation();
          uploadZone.classList.remove("beplus-advanced-reviews__upload-zone--dragover");
        });
        uploadZone.addEventListener("drop", function(e) {
          e.preventDefault();
          e.stopPropagation();
          uploadZone.classList.remove("beplus-advanced-reviews__upload-zone--dragover");
          var files = e.dataTransfer.files;
          if (!files || !files.length)
            return;
          for (var i = 0; i < files.length; i++) {
            var file = files[i];
            var isImage = file.type && file.type.indexOf("image/") === 0;
            var isVideo = file.type && file.type.indexOf("video/") === 0;
            if (isImage || isVideo) {
              mediaFiles.push({ file, removed: false });
            }
          }
          updatePreviews();
        });
      }
      function removeFile(index) {
        if (mediaFiles[index]) {
          mediaFiles[index].removed = true;
          updatePreviews();
        }
      }
      function updatePreviews() {
        if (!previewContainer)
          return;
        var activeFiles = mediaFiles.filter(function(m) {
          return !m.removed;
        });
        if (!activeFiles.length) {
          previewContainer.style.display = "none";
          previewContainer.innerHTML = "";
          return;
        }
        previewContainer.style.display = "";
        var html = "";
        var actualIndex = 0;
        mediaFiles.forEach(function(item, idx) {
          if (item.removed)
            return;
          var file = item.file;
          var isVideo = file.type && file.type.indexOf("video/") === 0;
          html += '<div class="beplus-advanced-reviews__media-preview-item">';
          if (isVideo) {
            var videoUrl = URL.createObjectURL(file);
            html += '<video src="' + videoUrl + '" width="120" height="68" controls class="beplus-advanced-reviews__media-preview-video"></video>';
          } else {
            var imgUrl = URL.createObjectURL(file);
            html += '<img src="' + imgUrl + '" alt="' + escapeAttr(file.name) + '" width="80" height="80" class="beplus-advanced-reviews__media-preview-img">';
          }
          html += '<span class="beplus-advanced-reviews__media-preview-name">' + escapeHtml(file.name) + "</span>";
          html += '<button type="button" class="beplus-advanced-reviews__media-preview-remove" data-index="' + idx + '" aria-label="Remove">&#10005;</button>';
          html += "</div>";
        });
        previewContainer.innerHTML = html;
        var removeButtons = previewContainer.querySelectorAll(".beplus-advanced-reviews__media-preview-remove");
        removeButtons.forEach(function(btn) {
          btn.addEventListener("click", function() {
            var idx = parseInt(btn.dataset.index, 10);
            removeFile(idx);
          });
        });
      }
      function escapeHtml(str) {
        var div = document.createElement("div");
        div.appendChild(document.createTextNode(str || ""));
        return div.innerHTML;
      }
      function escapeAttr(str) {
        return (str || "").replace(/"/g, "&quot;").replace(/'/g, "&#39;");
      }
    }
    function submitReview(block, data, form) {
      var productId = parseInt(block.dataset.productId, 10);
      var formData = new FormData();
      formData.append("product_id", productId);
      formData.append("rating", data.rating);
      formData.append("content", data.content);
      if (data.author) {
        formData.append("author", data.author);
      }
      if (data.email) {
        formData.append("email", data.email);
      }
      var mediaFiles = block._bparMediaFiles || [];
      var hasSizeError = false;
      mediaFiles.forEach(function(item) {
        if (item.removed)
          return;
        var file = item.file;
        var isVideo = file.type && file.type.indexOf("video/") === 0;
        var maxSize = isVideo ? bparData.maxVideoSize || 20971520 : bparData.maxUploadSize || 2097152;
        if (file.size > maxSize) {
          showFormMessage(
            block,
            isVideo ? bparData.i18n.videoTooLarge || "Video too large." : bparData.i18n.imageTooLarge || "Image too large.",
            "error"
          );
          hasSizeError = true;
          return;
        }
        formData.append("media[]", file);
      });
      if (hasSizeError)
        return;
      var pasteInput = form.querySelector(".beplus-advanced-reviews__paste-input");
      if (pasteInput && pasteInput.value) {
        formData.append("paste_image", pasteInput.value);
      }
      fetch(bparData.restUrl + "reviews", {
        method: "POST",
        headers: {
          "X-WP-Nonce": bparData.nonce
        },
        body: formData
      }).then(function(res) {
        return res.json();
      }).then(function(result) {
        if (result.success) {
          showFormMessage(block, result.message || bparData.i18n.submitSuccess, "success");
          form.reset();
          var starLabels = form.querySelectorAll(".beplus-advanced-reviews__star-label");
          starLabels.forEach(function(label) {
            label.classList.remove("beplus-advanced-reviews__star-label--active");
          });
          block._bparMediaFiles = [];
          var mediaPreview = form.querySelector(".beplus-advanced-reviews__media-preview");
          if (mediaPreview) {
            mediaPreview.style.display = "none";
            mediaPreview.innerHTML = "";
          }
          var pasteInput2 = form.querySelector(".beplus-advanced-reviews__paste-input");
          if (pasteInput2) {
            pasteInput2.value = "";
          }
          setTimeout(function() {
            var list = block.querySelector(".beplus-advanced-reviews__list");
            if (list && result.review) {
              var noReviewsEl = list.querySelector(".beplus-advanced-reviews__no-reviews");
              if (noReviewsEl) {
                noReviewsEl.remove();
              }
              list.insertAdjacentHTML("afterbegin", buildReviewCard(result.review, block));
            }
            var productId2 = parseInt(block.dataset.productId, 10);
            if (productId2) {
              var distUrl = new URL(bparData.restUrl + "reviews/distribution");
              distUrl.searchParams.set("product_id", productId2);
              fetch(distUrl.toString()).then(function(res) {
                return res.json();
              }).then(function(data2) {
                var distArea = block.querySelector(".beplus-advanced-reviews__distribution");
                if (distArea) {
                  distArea.innerHTML = buildDistribution(data2);
                }
                var perPage = parseInt(block.dataset.perPage, 10) || 10;
                var total = data2.total || 0;
                var pages = Math.ceil(total / perPage);
                block.dataset.totalPages = pages;
                var loadMoreWrapper = block.querySelector(".beplus-advanced-reviews__load-more-wrapper");
                if (loadMoreWrapper) {
                  loadMoreWrapper.style.display = pages > 1 ? "" : "none";
                }
              }).catch(function() {
              });
            }
          }, 500);
        } else {
          showFormMessage(block, result.message || bparData.i18n.submitError, "error");
        }
      }).catch(function() {
        showFormMessage(block, bparData.i18n.submitError, "error");
      });
    }
    function renderStars(rating, size) {
      rating = Math.max(1, Math.min(5, rating || 0));
      size = size || 1;
      var stars = "";
      for (var i = 1; i <= 5; i++) {
        var filled = i <= rating ? " beplus-advanced-reviews__star--filled" : " beplus-advanced-reviews__star--empty";
        stars += '<span class="beplus-advanced-reviews__star' + filled + '" aria-hidden="true" style="font-size:' + size + 'em;">&#9733;</span>';
      }
      return '<span class="beplus-advanced-reviews__stars" aria-label="' + rating + ' out of 5 stars">' + stars + "</span>";
    }
    function buildDistribution(data) {
      if (!data || !data.total) {
        return '<p class="beplus-advanced-reviews__no-reviews">' + bparData.i18n.noReviews + "</p>";
      }
      var html = '<div class="beplus-advanced-reviews__distribution-header">';
      html += '<div class="beplus-advanced-reviews__average">';
      html += '<span class="beplus-advanced-reviews__average-value">' + (data.average || 0) + "</span>";
      html += '<span class="beplus-advanced-reviews__average-stars">' + renderStars(Math.round(data.average || 0), 1.2) + "</span>";
      html += '<span class="beplus-advanced-reviews__total">' + data.total + " " + (data.total === 1 ? "review" : "reviews") + "</span>";
      html += "</div>";
      html += '<div class="beplus-advanced-reviews__distribution-bars">';
      for (var s = 5; s >= 1; s--) {
        var count = data.stars[s.toString()] || 0;
        var percent = data.total > 0 ? count / data.total * 100 : 0;
        html += '<div class="beplus-advanced-reviews__distribution-bar-row">';
        html += '<span class="beplus-advanced-reviews__distribution-bar-label">' + s + " \u2605</span>";
        html += '<div class="beplus-advanced-reviews__distribution-bar-track">';
        html += '<div class="beplus-advanced-reviews__distribution-bar-fill" style="width:' + percent + '%" role="progressbar" aria-valuenow="' + count + '" aria-valuemin="0" aria-valuemax="' + data.total + '"></div>';
        html += "</div>";
        html += '<span class="beplus-advanced-reviews__distribution-bar-count">' + count + "</span>";
        html += "</div>";
      }
      html += "</div></div>";
      return html;
    }
    function buildReviewCard(review, block) {
      function escHtml(str) {
        var div = document.createElement("div");
        div.appendChild(document.createTextNode(str || ""));
        return div.innerHTML;
      }
      function escAttr(str) {
        return (str || "").replace(/"/g, "&quot;").replace(/'/g, "&#39;");
      }
      var html = '<article class="beplus-advanced-reviews__review-card">';
      if (block.dataset.showAvatar !== "0") {
        html += '<div class="beplus-advanced-reviews__review-avatar">';
        if (review.avatar) {
          html += '<img src="' + escAttr(review.avatar) + '" alt="' + escAttr(review.author) + '" width="40" height="40" loading="lazy">';
        } else {
          html += '<div class="beplus-advanced-reviews__review-avatar--fallback">' + escHtml((review.author || "").charAt(0).toUpperCase()) + "</div>";
        }
        html += "</div>";
      }
      html += '<div class="beplus-advanced-reviews__review-body">';
      html += '<div class="beplus-advanced-reviews__review-header">';
      html += '<span class="beplus-advanced-reviews__review-author">' + escHtml(review.author) + "</span>";
      html += '<span class="beplus-advanced-reviews__review-rating">' + renderStars(review.rating) + "</span>";
      html += "</div>";
      html += '<div class="beplus-advanced-reviews__review-content">' + review.content + "</div>";
      if (block.dataset.showImages !== "0" && review.has_images && review.images.length) {
        html += '<div class="beplus-advanced-reviews__review-images">';
        review.images.forEach(function(img) {
          if (img.mime_type && img.mime_type.indexOf("video/") === 0) {
            html += '<video src="' + escAttr(img.url) + '" controls width="320" class="beplus-advanced-reviews__review-video"></video>';
          } else {
            html += '<a href="' + escAttr(img.url) + '" class="beplus-advanced-reviews__review-image-link" target="_blank" rel="noopener">';
            html += '<img src="' + escAttr(img.thumbnail) + '" alt="" width="80" height="80" loading="lazy" class="beplus-advanced-reviews__review-image-thumb">';
            html += "</a>";
          }
        });
        html += "</div>";
      }
      html += '<div class="beplus-advanced-reviews__review-date">' + escHtml(review.date_human) + "</div>";
      html += "</div></article>";
      return html;
    }
    function initPasteSupport(block) {
      if (!bparData.pasteEnabled)
        return;
      var uploadZone = block.querySelector(".beplus-advanced-reviews__upload-zone");
      if (!uploadZone)
        return;
      var pasteInput = uploadZone.querySelector(".beplus-advanced-reviews__paste-input");
      uploadZone.addEventListener("paste", function(e) {
        var items = e.clipboardData && e.clipboardData.items;
        if (!items)
          return;
        for (var i = 0; i < items.length; i++) {
          if (items[i].type.indexOf("image") === 0) {
            e.preventDefault();
            var blob = items[i].getAsFile();
            var maxSize = bparData.maxUploadSize || 2097152;
            if (blob.size > maxSize) {
              showFormMessage(block, bparData.i18n.imageTooLarge || "Image too large.", "error");
              break;
            }
            var reader = new FileReader();
            reader.onload = function(event) {
              if (pasteInput) {
                pasteInput.value = event.target.result;
              }
              showPastePreview(block, event.target.result);
            };
            reader.readAsDataURL(blob);
            break;
          }
        }
      });
      function showPastePreview(block2, dataUrl) {
        var form = block2.querySelector(".beplus-advanced-reviews__submit-form");
        if (!form)
          return;
        var previewContainer = form.querySelector(".beplus-advanced-reviews__media-preview");
        if (!previewContainer)
          return;
        var existing = previewContainer.querySelector(".beplus-advanced-reviews__media-preview-item--paste");
        if (existing) {
          existing.remove();
        }
        previewContainer.style.display = "";
        var item = document.createElement("div");
        item.className = "beplus-advanced-reviews__media-preview-item beplus-advanced-reviews__media-preview-item--paste";
        item.innerHTML = '<img src="' + dataUrl + '" alt="Pasted image" width="80" height="80" class="beplus-advanced-reviews__media-preview-img"><span class="beplus-advanced-reviews__media-preview-name">Pasted image</span><button type="button" class="beplus-advanced-reviews__media-preview-remove" aria-label="Remove">&#10005;</button>';
        var removeBtn = item.querySelector(".beplus-advanced-reviews__media-preview-remove");
        removeBtn.addEventListener("click", function() {
          item.remove();
          pasteInput.value = "";
          if (!previewContainer.querySelector(".beplus-advanced-reviews__media-preview-item")) {
            previewContainer.style.display = "none";
          }
        });
        previewContainer.appendChild(item);
      }
    }
    function showFormMessage(block, message, type) {
      var formWrapper = block.querySelector(".beplus-advanced-reviews__submit-form-wrapper");
      if (!formWrapper)
        return;
      var existing = formWrapper.querySelector(".beplus-advanced-reviews__form-message");
      if (existing) {
        existing.remove();
      }
      var msg = document.createElement("div");
      msg.className = "beplus-advanced-reviews__form-message beplus-advanced-reviews__form-message--" + type;
      msg.textContent = message;
      msg.setAttribute("role", "alert");
      formWrapper.insertBefore(msg, formWrapper.firstChild);
      setTimeout(function() {
        msg.remove();
      }, 5e3);
    }
  })();
})();
//# sourceMappingURL=view.js.map
