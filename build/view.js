(() => {
  var __defProp = Object.defineProperty;
  var __getOwnPropSymbols = Object.getOwnPropertySymbols;
  var __hasOwnProp = Object.prototype.hasOwnProperty;
  var __propIsEnum = Object.prototype.propertyIsEnumerable;
  var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
  var __spreadValues = (a, b) => {
    for (var prop in b || (b = {}))
      if (__hasOwnProp.call(b, prop))
        __defNormalProp(a, prop, b[prop]);
    if (__getOwnPropSymbols)
      for (var prop of __getOwnPropSymbols(b)) {
        if (__propIsEnum.call(b, prop))
          __defNormalProp(a, prop, b[prop]);
      }
    return a;
  };
  (function() {
    var hashRedirectInitialized = false;
    document.addEventListener("DOMContentLoaded", function() {
      const blocks = document.querySelectorAll(".beplus-advanced-reviews-for-woocommerce[data-product-id]");
      blocks.forEach(function(block) {
        initAdvancedReviews(block);
      });
    });
    function initAdvancedReviews(block) {
      const productId = parseInt(block.dataset.productId, 10);
      if (!productId)
        return;
      let currentPage = 1;
      let totalPages = 1;
      let listRequest;
      let distributionRequest;
      let filterSpinnerTimer;
      const listContainer = block.querySelector(".beplus-advanced-reviews-for-woocommerce__list");
      const loadMoreBtn = block.querySelector(".beplus-advanced-reviews-for-woocommerce__load-more");
      const loadMoreWrapper = block.querySelector(".beplus-advanced-reviews-for-woocommerce__load-more-wrapper");
      const filterRatingSelect = block.querySelector(".beplus-advanced-reviews-for-woocommerce__filter-rating-select");
      const filterImagesChip = block.querySelector('.beplus-advanced-reviews-for-woocommerce__filter-chip[data-filter="images"]');
      const sortSelect = block.querySelector(".beplus-advanced-reviews-for-woocommerce__sort-select");
      const verifiedChip = block.querySelector('.beplus-advanced-reviews-for-woocommerce__filter-chip[data-filter="verified"]');
      const filterControls = block.querySelector(".beplus-advanced-reviews-for-woocommerce__filter-controls");
      const filterSpinner = filterControls && (filterRatingSelect || sortSelect || filterImagesChip || verifiedChip) ? document.createElement("span") : null;
      if (filterSpinner) {
        filterSpinner.className = "beplus-advanced-reviews-for-woocommerce__request-spinner beplus-advanced-reviews-for-woocommerce__filter-spinner";
        filterSpinner.setAttribute("aria-hidden", "true");
        filterSpinner.hidden = true;
        filterControls.append(filterSpinner);
      }
      const distributionArea = block.querySelector(".beplus-advanced-reviews-for-woocommerce__distribution");
      const labels = bparfwData.i18n;
      const helpfulButtonSelector = ".beplus-advanced-reviews-for-woocommerce__helpful-button";
      const pendingVotes = /* @__PURE__ */ new Set();
      const voteUpdates = /* @__PURE__ */ new Map();
      let voteRevision = 0;
      let rankingRevision = 0;
      let canVoteHelpful = Boolean(bparfwData.canVoteHelpful);
      const listStatus = createRequestStatus(listContainer);
      const distributionStatus = createRequestStatus(distributionArea);
      let appliedFilters = readFilters();
      refreshReviews();
      loadDistribution();
      function readFilters() {
        return {
          rating: (filterRatingSelect == null ? void 0 : filterRatingSelect.value) || "0",
          images: (filterImagesChip == null ? void 0 : filterImagesChip.getAttribute("aria-pressed")) === "true",
          verified: (verifiedChip == null ? void 0 : verifiedChip.getAttribute("aria-pressed")) === "true",
          sort: (sortSelect == null ? void 0 : sortSelect.value) || "newest"
        };
      }
      function showFilters(filters) {
        if (filterRatingSelect)
          filterRatingSelect.value = filters.rating;
        if (sortSelect)
          sortSelect.value = filters.sort;
        filterImagesChip == null ? void 0 : filterImagesChip.setAttribute("aria-pressed", String(filters.images));
        verifiedChip == null ? void 0 : verifiedChip.setAttribute("aria-pressed", String(filters.verified));
      }
      function reviewUrl(endpoint) {
        const url = new URL(bparfwData.restUrl + endpoint);
        url.searchParams.set("product_id", productId);
        return url;
      }
      async function loadDistribution() {
        if (!distributionArea)
          return;
        distributionRequest == null ? void 0 : distributionRequest.abort();
        const request = new AbortController();
        distributionRequest = request;
        distributionStatus(true, labels.updatingDistribution);
        try {
          const data = await getReviewData(reviewUrl("reviews/distribution"), request.signal);
          if (distributionRequest !== request)
            return;
          if (!Number.isFinite(data.total) || !data.stars)
            throw new Error();
          distributionArea.innerHTML = buildDistribution(data);
          distributionStatus(false);
        } catch (error) {
          if (distributionRequest !== request)
            return;
          distributionStatus(false, labels.distributionLoadError, loadDistribution);
        } finally {
          if (distributionRequest === request)
            distributionRequest = null;
        }
      }
      function refreshReviews() {
        return requestReviews(readFilters(), 1, false);
      }
      function updateFilteredReviews() {
        return requestReviews(readFilters(), 1, false, true);
      }
      function hideFilterSpinner() {
        clearTimeout(filterSpinnerTimer);
        if (filterSpinner)
          filterSpinner.hidden = true;
      }
      function needsHelpfulRefresh() {
        return appliedFilters.sort === "helpful" && rankingRevision !== voteRevision;
      }
      function updateLoadMore() {
        const label = loadMoreBtn == null ? void 0 : loadMoreBtn.querySelector("[data-request-label]");
        const refresh = needsHelpfulRefresh();
        if (label)
          label.textContent = refresh ? labels.refreshHelpful : label.dataset.requestLabel;
        if (loadMoreWrapper)
          loadMoreWrapper.hidden = !refresh && currentPage >= totalPages;
      }
      async function requestReviews(filters, page, append, filterUpdate = false) {
        if (!listContainer)
          return;
        listRequest == null ? void 0 : listRequest.abort();
        const request = new AbortController();
        listRequest = request;
        showFilters(filters);
        listStatus(true, append ? labels.loadingMoreReviews : labels.updatingReviews, null, append || filterUpdate);
        hideFilterSpinner();
        if (filterUpdate && filterSpinner) {
          filterSpinnerTimer = setTimeout(() => {
            if (listRequest === request)
              filterSpinner.hidden = false;
          }, 200);
        }
        let skeleton = listContainer.querySelector(".beplus-advanced-reviews-for-woocommerce__skeleton");
        if (!listContainer.children.length)
          skeleton = showSkeletonCards();
        if (loadMoreBtn) {
          loadMoreBtn.disabled = true;
          setButtonLoading(loadMoreBtn, append, labels.loadingMoreReviews);
        }
        const url = reviewUrl("reviews");
        const requestVoteRevision = voteRevision;
        url.searchParams.set("page", page);
        url.searchParams.set("per_page", parseInt(block.dataset.perPage, 10) || 10);
        url.searchParams.set("sort", filters.sort);
        if (Number(filters.rating) > 0)
          url.searchParams.set("rating", filters.rating);
        if (filters.images)
          url.searchParams.set("has_images", "1");
        if (filters.verified)
          url.searchParams.set("verified_only", "1");
        try {
          const headers = canVoteHelpful ? { "X-WP-Nonce": bparfwData.nonce } : void 0;
          let data;
          try {
            data = await getReviewData(url, request.signal, headers);
          } catch (error) {
            if (!headers || error.status !== 401 && error.status !== 403 || listRequest !== request)
              throw error;
            canVoteHelpful = false;
            data = await getReviewData(url, request.signal);
          }
          if (listRequest !== request)
            return;
          if (!Array.isArray(data.reviews) || !Number.isInteger(data.pages))
            throw new Error();
          if (append && filters.sort === "helpful" && needsHelpfulRefresh()) {
            listStatus(false);
            return;
          }
          const reviews = data.reviews.map((review) => {
            const update = voteUpdates.get(String(review.id));
            return update && update.revision > requestVoteRevision ? __spreadValues(__spreadValues({}, review), update.data) : review;
          });
          if (!append) {
            listContainer.innerHTML = buildReviewList(reviews, filters);
            rankingRevision = requestVoteRevision;
          } else {
            const displayed = new Set(Array.from(listContainer.querySelectorAll(helpfulButtonSelector), (button) => button.dataset.reviewId));
            const newReviews = reviews.filter((review) => !displayed.has(String(review.id)));
            if (newReviews.length)
              listContainer.insertAdjacentHTML("beforeend", buildReviewList(newReviews, filters));
          }
          appliedFilters = filters;
          currentPage = page;
          totalPages = data.pages;
          block.dataset.totalPages = totalPages;
          listStatus(false);
        } catch (error) {
          if (listRequest !== request)
            return;
          skeleton == null ? void 0 : skeleton.remove();
          showFilters(appliedFilters);
          listStatus(false, labels.reviewsLoadError, () => requestReviews(filters, page, append, filterUpdate));
        } finally {
          if (listRequest === request) {
            hideFilterSpinner();
            listRequest = null;
            if (loadMoreBtn) {
              loadMoreBtn.disabled = false;
              setButtonLoading(loadMoreBtn, false);
            }
            updateLoadMore();
          }
        }
      }
      function showSkeletonCards() {
        var perPage = parseInt(block.dataset.perPage, 10) || 5;
        var skeletonCount = Math.min(perPage, 3);
        var html = '<div class="beplus-advanced-reviews-for-woocommerce__skeleton" aria-hidden="true">';
        for (var i = 0; i < skeletonCount; i++) {
          html += '<div class="beplus-advanced-reviews-for-woocommerce__skeleton-card">';
          html += '<div class="beplus-advanced-reviews-for-woocommerce__skeleton-avatar"></div>';
          html += '<div class="beplus-advanced-reviews-for-woocommerce__skeleton-body">';
          html += '<div class="beplus-advanced-reviews-for-woocommerce__skeleton-line beplus-advanced-reviews-for-woocommerce__skeleton-line--short"></div>';
          html += '<div class="beplus-advanced-reviews-for-woocommerce__skeleton-line beplus-advanced-reviews-for-woocommerce__skeleton-line--medium"></div>';
          html += '<div class="beplus-advanced-reviews-for-woocommerce__skeleton-line beplus-advanced-reviews-for-woocommerce__skeleton-line--long"></div>';
          html += "</div></div>";
        }
        html += "</div>";
        listContainer.innerHTML = html;
        return listContainer.firstElementChild;
      }
      function buildReviewList(reviews, filters) {
        if (!reviews || !reviews.length) {
          const hasFilters = Number(filters.rating) > 0 || filters.images || filters.verified;
          const message = hasFilters ? labels.noMatchingReviews : labels.noReviews;
          return '<p class="beplus-advanced-reviews-for-woocommerce__no-reviews">' + escHtml(message) + "</p>";
        }
        var html = "";
        reviews.forEach(function(review) {
          html += '<article class="beplus-advanced-reviews-for-woocommerce__review-card">';
          if (block.dataset.showAvatar !== "0") {
            html += '<div class="beplus-advanced-reviews-for-woocommerce__review-avatar">';
            if (review.avatar) {
              html += '<img src="' + escAttr(review.avatar) + '" alt="' + escAttr(review.author) + '" width="40" height="40" loading="lazy">';
            } else {
              html += '<div class="beplus-advanced-reviews-for-woocommerce__review-avatar--fallback">' + escHtml((review.author || "").charAt(0).toUpperCase()) + "</div>";
            }
            html += "</div>";
          }
          html += '<div class="beplus-advanced-reviews-for-woocommerce__review-body">';
          html += '<div class="beplus-advanced-reviews-for-woocommerce__review-header">';
          html += '<div class="beplus-advanced-reviews-for-woocommerce__review-author-group">';
          html += '<span class="beplus-advanced-reviews-for-woocommerce__review-author">' + escHtml(review.author) + "</span>";
          if (review.is_verified) {
            html += '<span class="beplus-advanced-reviews-for-woocommerce__verified-badge" aria-label="' + escAttr(bparfwData.i18n.verifiedPurchase || "Verified Purchase") + '">';
            html += '<span class="beplus-advanced-reviews-for-woocommerce__verified-badge-icon" aria-hidden="true">';
            html += '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>';
            html += "</span> ";
            html += escHtml(bparfwData.i18n.verifiedPurchase || "Verified Purchase");
            html += "</span>";
          }
          html += "</div>";
          html += '<span class="beplus-advanced-reviews-for-woocommerce__review-rating">' + renderStars(review.rating) + "</span>";
          html += "</div>";
          html += '<div class="beplus-advanced-reviews-for-woocommerce__review-content">' + review.content + "</div>";
          if (block.dataset.showMedia !== "0" && review.has_images && review.images.length) {
            var mediaData = review.images.map(function(m) {
              return { url: m.url, thumbnail: m.thumbnail || m.url, mime_type: m.mime_type || "" };
            });
            html += '<div class="beplus-advanced-reviews-for-woocommerce__review-images" data-review-media="' + escAttr(JSON.stringify(mediaData)) + '">';
            review.images.forEach(function(img, idx) {
              if (img.mime_type && img.mime_type.indexOf("video/") === 0) {
                html += '<button type="button" class="beplus-advanced-reviews-for-woocommerce__review-media-btn beplus-advanced-reviews-for-woocommerce__review-media-btn--video" data-media-index="' + idx + '" data-media-type="video" aria-label="' + escAttr(bparfwData.i18n.viewMedia || "View media") + '">';
                html += '<video src="' + escAttr(img.url) + '" width="80" height="80" class="beplus-advanced-reviews-for-woocommerce__review-video" muted preload="metadata"></video>';
                html += "</button>";
              } else {
                html += '<button type="button" class="beplus-advanced-reviews-for-woocommerce__review-media-btn" data-media-index="' + idx + '" data-media-type="image" aria-label="' + escAttr(bparfwData.i18n.viewMedia || "View media") + '">';
                html += '<img src="' + escAttr(img.thumbnail) + '" alt="" width="80" height="80" loading="lazy" class="beplus-advanced-reviews-for-woocommerce__review-image-thumb">';
                html += "</button>";
              }
            });
            html += "</div>";
          }
          html += '<div class="beplus-advanced-reviews-for-woocommerce__review-date">' + escHtml(review.date_human) + "</div>";
          const reviewId = String(review.id);
          if (/^[1-9]\d*$/.test(reviewId) && Number.isInteger(review.helpful_count)) {
            const pressed = typeof review.viewer_has_voted === "boolean" ? ' aria-pressed="' + review.viewer_has_voted + '"' : "";
            const busy = pendingVotes.has(reviewId) ? ' aria-disabled="true" aria-busy="true"' : "";
            html += '<div class="beplus-advanced-reviews-for-woocommerce__helpful">';
            html += '<button type="button" class="beplus-advanced-reviews-for-woocommerce__helpful-button" data-review-id="' + reviewId + '"' + pressed + busy + ">";
            html += escHtml(labels.helpful) + " (<span data-helpful-count>" + (Number(review.helpful_count) || 0) + "</span>)</button>";
            html += '<div class="beplus-advanced-reviews-for-woocommerce__helpful-status" role="status"></div></div>';
          }
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
      listContainer == null ? void 0 : listContainer.addEventListener("click", async function(event) {
        const button = event.target.closest(helpfulButtonSelector);
        if (!button)
          return;
        const id = button.dataset.reviewId;
        if (!/^[1-9]\d*$/.test(id) || pendingVotes.has(id))
          return;
        const status = button.nextElementSibling;
        if (!canVoteHelpful) {
          status.textContent = labels.helpfulSignIn;
          return;
        }
        if (!button.hasAttribute("aria-pressed")) {
          status.textContent = listRequest ? labels.helpfulLoading : labels.helpfulError;
          if (!listRequest)
            refreshReviews();
          return;
        }
        const selector = helpfulButtonSelector + '[data-review-id="' + id + '"]';
        const voted = button.getAttribute("aria-pressed") !== "true";
        pendingVotes.add(id);
        status.textContent = "";
        button.setAttribute("aria-disabled", "true");
        button.setAttribute("aria-busy", "true");
        try {
          const response = await fetch(bparfwData.restUrl + "reviews/" + id + "/helpful", {
            method: "PUT",
            headers: { "Content-Type": "application/json", "X-WP-Nonce": bparfwData.nonce },
            body: JSON.stringify({ voted })
          });
          if (response.status === 401 || response.status === 403) {
            canVoteHelpful = false;
            const current = listContainer.querySelector(selector);
            if (current)
              current.nextElementSibling.textContent = labels.helpfulSignIn;
            return;
          }
          if (!response.ok)
            throw new Error();
          const data = await response.json();
          if (!Number.isInteger(data.helpful_count) || typeof data.viewer_has_voted !== "boolean")
            throw new Error();
          voteUpdates.set(id, { data, revision: ++voteRevision });
          if (!listRequest)
            updateLoadMore();
          listContainer.querySelectorAll(selector).forEach((current) => {
            current.setAttribute("aria-pressed", String(data.viewer_has_voted));
            current.querySelector("[data-helpful-count]").textContent = data.helpful_count;
            current.nextElementSibling.textContent = data.viewer_has_voted ? labels.helpfulAdded : labels.helpfulRemoved;
          });
        } catch (error) {
          const current = listContainer.querySelector(selector);
          if (current)
            current.nextElementSibling.textContent = labels.helpfulError;
        } finally {
          pendingVotes.delete(id);
          listContainer.querySelectorAll(selector).forEach((current) => {
            current.removeAttribute("aria-disabled");
            current.removeAttribute("aria-busy");
          });
        }
      });
      loadMoreBtn == null ? void 0 : loadMoreBtn.addEventListener("click", function() {
        if (listRequest)
          return;
        if (needsHelpfulRefresh()) {
          requestReviews(appliedFilters, 1, false);
        } else if (currentPage < totalPages) {
          requestReviews(appliedFilters, currentPage + 1, true);
        }
      });
      filterRatingSelect == null ? void 0 : filterRatingSelect.addEventListener("change", updateFilteredReviews);
      sortSelect == null ? void 0 : sortSelect.addEventListener("change", updateFilteredReviews);
      [filterImagesChip, verifiedChip].forEach(function(chip) {
        chip == null ? void 0 : chip.addEventListener("click", function() {
          chip.setAttribute("aria-pressed", String(chip.getAttribute("aria-pressed") !== "true"));
          updateFilteredReviews();
        });
      });
      initReviewForm(block, function() {
        refreshReviews();
        loadDistribution();
      });
      initMediaUpload(block);
      initLightbox(block);
      initHashRedirect(block);
    }
    async function getReviewData(url, signal, headers) {
      const response = await fetch(url.toString(), { cache: "no-store", signal, headers });
      if (!response.ok)
        throw Object.assign(new Error(), { status: response.status });
      return response.json();
    }
    function createRequestStatus(region) {
      if (!region)
        return () => {
        };
      const prefix = "beplus-advanced-reviews-for-woocommerce__";
      const container = document.createElement("div");
      const message = document.createElement("span");
      const spinner = document.createElement("span");
      const retry = document.createElement("button");
      container.className = prefix + "request-status";
      message.setAttribute("role", "status");
      message.tabIndex = -1;
      spinner.className = prefix + "request-spinner";
      spinner.setAttribute("aria-hidden", "true");
      spinner.hidden = true;
      retry.type = "button";
      retry.className = prefix + "request-retry";
      retry.textContent = bparfwData.i18n.retryRequest;
      retry.hidden = true;
      container.append(spinner, message, retry);
      region.before(container);
      return function(busy, text = "", retryRequest = null, announceOnly = false) {
        region.setAttribute("aria-busy", String(busy));
        container.classList.toggle("screen-reader-text", announceOnly);
        spinner.hidden = !busy || announceOnly;
        message.textContent = text;
        if (busy && document.activeElement === retry)
          message.focus();
        retry.hidden = !retryRequest;
        retry.onclick = retryRequest;
      };
    }
    function setButtonLoading(button, busy, loadingLabel) {
      const label = button.querySelector("[data-request-label]");
      if (label)
        label.textContent = busy ? loadingLabel : label.dataset.requestLabel;
      button.classList.toggle("is-loading", busy);
      button.setAttribute("aria-busy", String(busy));
    }
    function initReviewForm(block, refresh) {
      var form = block.querySelector(".beplus-advanced-reviews-for-woocommerce__submit-form");
      if (!form)
        return;
      var submissionStatus = createRequestStatus(form);
      var ratingInputs = form.querySelectorAll(".beplus-advanced-reviews-for-woocommerce__star-input");
      var starLabels = form.querySelectorAll(".beplus-advanced-reviews-for-woocommerce__star-label");
      ratingInputs.forEach(function(input, index) {
        input.addEventListener("change", function() {
          starLabels.forEach(function(label, i) {
            var isActive = i >= starLabels.length - parseInt(input.value);
            label.classList.toggle("beplus-advanced-reviews-for-woocommerce__star-label--active", isActive);
          });
        });
      });
      form.addEventListener("submit", function(e) {
        e.preventDefault();
        var formData = new FormData(form);
        var rating = formData.get("rating");
        var content = formData.get("content");
        if (!rating) {
          showFormMessage(block, bparfwData.i18n.ratingRequired, "error");
          return;
        }
        if (!content || !content.trim()) {
          showFormMessage(block, bparfwData.i18n.contentRequired, "error");
          return;
        }
        var emailInput = form.querySelector("#bpar-email");
        var email = emailInput ? (emailInput.value || "").trim() : "";
        if (emailInput) {
          if (!email) {
            showFormMessage(block, bparfwData.i18n.emailRequired, "error");
            return;
          }
          if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showFormMessage(block, bparfwData.i18n.invalidEmail, "error");
            return;
          }
        }
        var data = {
          rating,
          content,
          author: formData.get("author") || "",
          email
        };
        submitReview(block, data, form, refresh, submissionStatus);
      });
    }
    function initMediaUpload(block) {
      var form = block.querySelector(".beplus-advanced-reviews-for-woocommerce__submit-form");
      if (!form)
        return;
      var prefix = "beplus-advanced-reviews-for-woocommerce__";
      var preview = form.querySelector("." + prefix + "media-preview");
      var zone = form.querySelector("." + prefix + "upload-zone");
      var input = form.querySelector("." + prefix + "upload-zone-input");
      if (!preview || !zone || !input)
        return;
      var attachments = form.querySelector("." + prefix + "attachments");
      var count = form.querySelector("." + prefix + "attachments-count");
      var status = document.createElement("p");
      status.className = "screen-reader-text";
      status.setAttribute("aria-live", "polite");
      status.setAttribute("aria-atomic", "true");
      (attachments || preview.parentNode).append(status);
      preview.hidden = true;
      preview.style.removeProperty("display");
      var labels = bparfwData.i18n;
      var items = [];
      var dragged = null;
      var dropTarget = null;
      var rtl = getComputedStyle(preview).direction === "rtl";
      block._bparMediaFiles = items;
      block._bparClearMedia = function() {
        items.forEach(function(item) {
          URL.revokeObjectURL(item.url);
        });
        items.length = 0;
        preview.replaceChildren();
        status.textContent = "";
        updateControls();
      };
      function updateControls() {
        preview.hidden = items.length === 0;
        if (attachments)
          attachments.classList.toggle("has-attachments", items.length > 0);
        if (count) {
          count.hidden = items.length === 0;
          count.textContent = items.length;
        }
        items.forEach(function(item, index) {
          item.up.disabled = index === 0;
          item.down.disabled = index === items.length - 1;
        });
      }
      function setDropTarget(node, edge) {
        if (dropTarget && dropTarget !== node)
          delete dropTarget.dataset.dropEdge;
        dropTarget = node;
        if (node)
          node.dataset.dropEdge = edge;
      }
      function clearDrag() {
        if (dragged)
          dragged.node.classList.remove("is-dragging");
        dragged = null;
        setDropTarget(null);
      }
      function dropEdge(event, node) {
        var rect = node.getBoundingClientRect();
        var leftHalf = event.clientX < rect.left + rect.width / 2;
        return leftHalf !== rtl ? "before" : "after";
      }
      function move(item, index, control) {
        var _a;
        var previous = items.indexOf(item);
        if (block._bparSubmitting || previous < 0 || previous === index || index < 0 || index >= items.length)
          return;
        items.splice(previous, 1);
        items.splice(index, 0, item);
        preview.insertBefore(item.node, ((_a = items[index + 1]) == null ? void 0 : _a.node) || null);
        updateControls();
        control = control && !control.disabled ? control : item.up.disabled ? item.down : item.up;
        control.focus();
        status.textContent = labels.mediaMoved.replace(/%([123])\$[sd]/g, function(match, position) {
          return [item.name, index + 1, items.length][Number(position) - 1];
        });
      }
      function add(file, pasted) {
        if (block._bparSubmitting)
          return;
        var video = file.type.indexOf("video/") === 0;
        if (!file.type.startsWith("image/") && !video)
          return;
        if (video ? !bparfwData.videosEnabled : !bparfwData.imagesEnabled) {
          showFormMessage(block, video ? labels.videoNotAllowed : labels.imageNotAllowed, "error");
          return;
        }
        if (!(video ? bparfwData.videoTypes : bparfwData.allowedTypes).includes(file.type)) {
          showFormMessage(block, labels.mediaTypeNotAllowed, "error");
          return;
        }
        if (file.size > Number(video ? bparfwData.maxVideoSize : bparfwData.maxUploadSize)) {
          showFormMessage(block, video ? labels.videoTooLarge : labels.imageTooLarge, "error");
          return;
        }
        var item = {
          file,
          pasted,
          name: pasted ? labels.pastedImage : file.name,
          url: URL.createObjectURL(file),
          node: document.createElement(preview.tagName === "OL" ? "li" : "div")
        };
        item.node.className = prefix + "media-preview-item";
        var frame = document.createElement("div");
        frame.className = prefix + "media-preview-frame";
        var media = document.createElement(video ? "video" : "img");
        media.className = prefix + (video ? "media-preview-video" : "media-preview-img");
        media.src = item.url;
        if (video) {
          media.preload = "auto";
          media.playsInline = true;
        } else {
          media.alt = item.name;
          media.draggable = false;
        }
        var name = document.createElement("span");
        name.className = prefix + "media-preview-name";
        name.textContent = item.name;
        name.title = item.name;
        var controls = document.createElement("div");
        controls.className = prefix + "media-preview-controls";
        var handle = document.createElement("span");
        handle.className = prefix + "media-preview-handle";
        handle.draggable = true;
        handle.setAttribute("aria-hidden", "true");
        handle.textContent = "\u283F";
        handle.addEventListener("dragstart", function(event) {
          if (block._bparSubmitting) {
            event.preventDefault();
            return;
          }
          dragged = item;
          item.node.classList.add("is-dragging");
          event.dataTransfer.effectAllowed = "move";
          event.dataTransfer.setData("text/plain", item.name);
        });
        handle.addEventListener("dragend", clearDrag);
        item.node.addEventListener("dragover", function(event) {
          if (!dragged || dragged === item)
            return;
          event.preventDefault();
          event.dataTransfer.dropEffect = "move";
          setDropTarget(item.node, dropEdge(event, item.node));
        });
        item.node.addEventListener("dragleave", function(event) {
          if (dropTarget === item.node && !item.node.contains(event.relatedTarget))
            setDropTarget(null);
        });
        item.node.addEventListener("drop", function(event) {
          if (!dragged || dragged === item)
            return;
          event.preventDefault();
          var insertion = items.indexOf(item) + (dropEdge(event, item.node) === "after" ? 1 : 0);
          var previous = items.indexOf(dragged);
          move(dragged, insertion - (previous < insertion ? 1 : 0));
          clearDrag();
        });
        function button(kind, symbol, label, action) {
          var element = document.createElement("button");
          element.type = "button";
          element.className = prefix + "media-preview-" + kind;
          element.textContent = symbol;
          element.setAttribute("aria-label", label.replace("%s", function() {
            return item.name;
          }));
          element.addEventListener("click", action);
          return element;
        }
        item.up = button("move", rtl ? "\u2192" : "\u2190", labels.moveMediaEarlier, function() {
          move(item, items.indexOf(item) - 1, item.up);
        });
        item.down = button("move", rtl ? "\u2190" : "\u2192", labels.moveMediaLater, function() {
          move(item, items.indexOf(item) + 1, item.down);
        });
        item.remove = button("remove", "\xD7", labels.removeMedia, function() {
          if (block._bparSubmitting)
            return;
          var index = items.indexOf(item);
          items.splice(index, 1);
          item.node.remove();
          URL.revokeObjectURL(item.url);
          updateControls();
          var neighbor = items[index] || items[index - 1];
          (neighbor ? neighbor.remove : zone).focus();
        });
        frame.append(media);
        if (video) {
          let updatePlayer2 = function() {
            play.textContent = media.paused ? "\u25B6" : "\u2161";
            play.setAttribute("aria-label", (media.paused ? labels.playVideoPreview : labels.pauseVideoPreview).replace("%s", function() {
              return item.name;
            }));
          };
          var updatePlayer = updatePlayer2;
          var play = button("play", "\u25B6", labels.playVideoPreview, function() {
            if (media.paused) {
              media.play().catch(function() {
              });
            } else {
              media.pause();
            }
          });
          media.addEventListener("play", updatePlayer2);
          media.addEventListener("pause", updatePlayer2);
          media.addEventListener("ended", updatePlayer2);
          frame.append(play);
        }
        frame.append(item.remove);
        controls.append(handle, item.up, item.down);
        item.node.append(frame, name, controls);
        var existing = pasted ? items.findIndex(function(entry) {
          return entry.pasted;
        }) : -1;
        if (existing >= 0) {
          URL.revokeObjectURL(items[existing].url);
          items[existing].node.replaceWith(item.node);
          items[existing] = item;
        } else {
          items.push(item);
          preview.append(item.node);
        }
        updateControls();
      }
      input.addEventListener("change", function() {
        Array.from(input.files).forEach(function(file) {
          add(file, false);
        });
        input.value = "";
      });
      zone.addEventListener("click", function(event) {
        if (event.target !== input && !block._bparSubmitting)
          input.click();
      });
      zone.addEventListener("dragover", function(event) {
        if (!event.dataTransfer.types.includes("Files"))
          return;
        event.preventDefault();
        zone.classList.add(prefix + "upload-zone--dragover");
      });
      zone.addEventListener("dragleave", function() {
        zone.classList.remove(prefix + "upload-zone--dragover");
      });
      zone.addEventListener("drop", function(event) {
        event.preventDefault();
        zone.classList.remove(prefix + "upload-zone--dragover");
        Array.from(event.dataTransfer.files).forEach(function(file) {
          add(file, false);
        });
      });
      if (bparfwData.pasteEnabled) {
        zone.addEventListener("paste", function(event) {
          var _a;
          var image = (_a = Array.from(event.clipboardData.items).find(function(entry) {
            return entry.kind === "file" && entry.type.startsWith("image/");
          })) == null ? void 0 : _a.getAsFile();
          if (!image)
            return;
          event.preventDefault();
          add(image, true);
        });
      }
    }
    async function submitReview(block, data, form, refresh, submissionStatus) {
      if (block._bparSubmitting)
        return;
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
      var pastePosition = mediaFiles.findIndex(function(item) {
        return item.pasted;
      });
      mediaFiles.forEach(function(item) {
        if (!item.pasted)
          formData.append("media[]", item.file);
      });
      var submit = form.querySelector('[type="submit"]');
      block._bparSubmitting = true;
      submit.disabled = true;
      submissionStatus(true, bparfwData.i18n.submittingReview, null, true);
      setButtonLoading(submit, true, bparfwData.i18n.submittingReview);
      try {
        if (pastePosition >= 0) {
          var pasteImage = await new Promise(function(resolve, reject) {
            var reader = new FileReader();
            reader.onload = function() {
              resolve(reader.result);
            };
            reader.onerror = reject;
            reader.readAsDataURL(mediaFiles[pastePosition].file);
          });
          formData.append("paste_image", pasteImage);
          formData.append("paste_position", pastePosition);
        }
        var response = await fetch(bparfwData.restUrl + "reviews", {
          method: "POST",
          headers: { "X-WP-Nonce": bparfwData.nonce },
          body: formData
        });
        var result = await response.json();
        if (response.ok && result.success) {
          showFormMessage(block, result.message || bparfwData.i18n.submitSuccess, result.media_failed_count ? "warning" : "success");
          form.reset();
          var starLabels = form.querySelectorAll(".beplus-advanced-reviews-for-woocommerce__star-label");
          starLabels.forEach(function(label) {
            label.classList.remove("beplus-advanced-reviews-for-woocommerce__star-label--active");
          });
          if (block._bparClearMedia)
            block._bparClearMedia();
          refresh();
        } else {
          showFormMessage(block, result.message || bparfwData.i18n.submitError, "error");
        }
      } catch (error) {
        showFormMessage(block, bparfwData.i18n.submitError, "error");
      } finally {
        block._bparSubmitting = false;
        submit.disabled = false;
        submissionStatus(false);
        setButtonLoading(submit, false);
      }
    }
    var starSvgIcon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
    function renderStars(rating, size) {
      rating = Math.max(1, Math.min(5, rating || 0));
      size = size || 1;
      var pxSize = 16 * size;
      var stars = "";
      for (var i = 1; i <= 5; i++) {
        var filled = i <= rating ? " beplus-advanced-reviews-for-woocommerce__star--filled" : " beplus-advanced-reviews-for-woocommerce__star--empty";
        stars += '<span class="beplus-advanced-reviews-for-woocommerce__star' + filled + '" aria-hidden="true" style="width:' + size + "em;height:" + size + 'em;"><svg width="' + pxSize + '" height="' + pxSize + '" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></span>';
      }
      return '<span class="beplus-advanced-reviews-for-woocommerce__stars" aria-label="' + rating + ' out of 5 stars">' + stars + "</span>";
    }
    function renderPartialStars(rating, size) {
      rating = Math.max(0, Math.min(5, rating || 0));
      size = size || 1;
      var pxSize = 16 * size;
      var stars = "";
      var svg = '<svg width="' + pxSize + '" height="' + pxSize + '" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
      for (var i = 1; i <= 5; i++) {
        if (rating >= i) {
          stars += '<span class="beplus-advanced-reviews-for-woocommerce__star beplus-advanced-reviews-for-woocommerce__star--filled" aria-hidden="true" style="width:' + size + "em;height:" + size + 'em;">' + svg + "</span>";
        } else if (rating > i - 1) {
          var pct = Math.round((rating - (i - 1)) * 100);
          var right = 100 - pct;
          stars += '<span class="beplus-advanced-reviews-for-woocommerce__star beplus-advanced-reviews-for-woocommerce__star--partial" aria-hidden="true" style="width:' + size + "em;height:" + size + 'em;">';
          stars += '<span class="beplus-advanced-reviews-for-woocommerce__star__empty-base">' + svg + "</span>";
          stars += '<span class="beplus-advanced-reviews-for-woocommerce__star__filled-clip" style="clip-path:inset(0 ' + right + '% 0 0)">' + svg + "</span>";
          stars += "</span>";
        } else {
          stars += '<span class="beplus-advanced-reviews-for-woocommerce__star beplus-advanced-reviews-for-woocommerce__star--empty" aria-hidden="true" style="width:' + size + "em;height:" + size + 'em;">' + svg + "</span>";
        }
      }
      return '<span class="beplus-advanced-reviews-for-woocommerce__stars" aria-label="' + rating.toFixed(1) + ' out of 5 stars">' + stars + "</span>";
    }
    function buildDistribution(data) {
      if (!data || !data.total) {
        return '<p class="beplus-advanced-reviews-for-woocommerce__no-reviews">' + bparfwData.i18n.noReviews + "</p>";
      }
      var html = '<div class="beplus-advanced-reviews-for-woocommerce__distribution-header">';
      html += '<div class="beplus-advanced-reviews-for-woocommerce__average">';
      html += '<span class="beplus-advanced-reviews-for-woocommerce__average-value">' + (data.average || 0) + "</span>";
      html += '<span class="beplus-advanced-reviews-for-woocommerce__average-stars">' + renderPartialStars(data.average || 0, 1) + "</span>";
      html += '<span class="beplus-advanced-reviews-for-woocommerce__total">' + data.total + " " + (data.total === 1 ? "review" : "reviews") + "</span>";
      html += "</div>";
      html += '<div class="beplus-advanced-reviews-for-woocommerce__distribution-bars">';
      for (var s = 5; s >= 1; s--) {
        var count = data.stars[s.toString()] || 0;
        var percent = data.total > 0 ? count / data.total * 100 : 0;
        html += '<div class="beplus-advanced-reviews-for-woocommerce__distribution-bar-row">';
        html += '<span class="beplus-advanced-reviews-for-woocommerce__distribution-bar-label">' + s + " " + starSvgIcon + "</span>";
        html += '<div class="beplus-advanced-reviews-for-woocommerce__distribution-bar-track">';
        html += '<div class="beplus-advanced-reviews-for-woocommerce__distribution-bar-fill" style="width:' + percent + '%" role="progressbar" aria-valuenow="' + count + '" aria-valuemin="0" aria-valuemax="' + data.total + '"></div>';
        html += "</div>";
        html += '<span class="beplus-advanced-reviews-for-woocommerce__distribution-bar-count">' + count + "</span>";
        html += "</div>";
      }
      html += "</div></div>";
      return html;
    }
    function initLightbox(block) {
      var listContainer = block.querySelector(".beplus-advanced-reviews-for-woocommerce__list");
      if (!listContainer)
        return;
      var lightbox = null;
      var currentMedia = [];
      var currentIndex = 0;
      var lastFocusedEl = null;
      var currentVideoEl = null;
      var LIGHTBOX_ID = "bpar-lightbox";
      listContainer.addEventListener("click", function(e) {
        var mediaBtn = e.target.closest(".beplus-advanced-reviews-for-woocommerce__review-media-btn");
        if (!mediaBtn)
          return;
        e.preventDefault();
        var imagesContainer = mediaBtn.closest(".beplus-advanced-reviews-for-woocommerce__review-images");
        if (!imagesContainer)
          return;
        var rawMedia = imagesContainer.getAttribute("data-review-media");
        if (!rawMedia)
          return;
        try {
          currentMedia = JSON.parse(rawMedia);
        } catch (err) {
          return;
        }
        if (!currentMedia.length)
          return;
        currentIndex = parseInt(mediaBtn.getAttribute("data-media-index"), 10) || 0;
        if (currentIndex < 0)
          currentIndex = 0;
        if (currentIndex >= currentMedia.length)
          currentIndex = currentMedia.length - 1;
        lastFocusedEl = mediaBtn;
        openLightbox();
      });
      function openLightbox() {
        if (lightbox)
          return;
        lightbox = document.createElement("div");
        lightbox.id = LIGHTBOX_ID;
        lightbox.className = "beplus-advanced-reviews-for-woocommerce__lightbox";
        lightbox.setAttribute("role", "dialog");
        lightbox.setAttribute("aria-modal", "true");
        lightbox.setAttribute("aria-label", "Media viewer");
        var overlay = document.createElement("div");
        overlay.className = "beplus-advanced-reviews-for-woocommerce__lightbox-overlay";
        var closeBtn = document.createElement("button");
        closeBtn.type = "button";
        closeBtn.className = "beplus-advanced-reviews-for-woocommerce__lightbox-close";
        closeBtn.setAttribute("aria-label", "Close");
        closeBtn.innerHTML = "&#10005;";
        closeBtn.addEventListener("click", closeLightbox);
        var content = document.createElement("div");
        content.className = "beplus-advanced-reviews-for-woocommerce__lightbox-content";
        var prevBtn = null;
        var nextBtn = null;
        if (currentMedia.length > 1) {
          prevBtn = document.createElement("button");
          prevBtn.type = "button";
          prevBtn.className = "beplus-advanced-reviews-for-woocommerce__lightbox-nav beplus-advanced-reviews-for-woocommerce__lightbox-prev";
          prevBtn.setAttribute("aria-label", "Previous");
          prevBtn.innerHTML = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>';
          prevBtn.addEventListener("click", function() {
            navigate(-1);
          });
          nextBtn = document.createElement("button");
          nextBtn.type = "button";
          nextBtn.className = "beplus-advanced-reviews-for-woocommerce__lightbox-nav beplus-advanced-reviews-for-woocommerce__lightbox-next";
          nextBtn.setAttribute("aria-label", "Next");
          nextBtn.innerHTML = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
          nextBtn.addEventListener("click", function() {
            navigate(1);
          });
        }
        var counter = document.createElement("div");
        counter.className = "beplus-advanced-reviews-for-woocommerce__lightbox-counter";
        counter.setAttribute("aria-live", "polite");
        lightbox.appendChild(overlay);
        lightbox.appendChild(closeBtn);
        lightbox.appendChild(content);
        if (prevBtn)
          lightbox.appendChild(prevBtn);
        if (nextBtn)
          lightbox.appendChild(nextBtn);
        lightbox.appendChild(counter);
        document.body.appendChild(lightbox);
        overlay.addEventListener("click", closeLightbox);
        document.addEventListener("keydown", onKeyDown);
        document.body.style.overflow = "hidden";
        requestAnimationFrame(function() {
          lightbox.classList.add("beplus-advanced-reviews-for-woocommerce__lightbox--open");
        });
        renderCurrentMedia(true);
        trapFocus();
      }
      function closeLightbox() {
        if (!lightbox)
          return;
        destroyVideo();
        document.removeEventListener("keydown", onKeyDown);
        document.body.style.overflow = "";
        lightbox.classList.remove("beplus-advanced-reviews-for-woocommerce__lightbox--open");
        var onTransitionEnd = function() {
          if (lightbox && lightbox.parentNode) {
            lightbox.parentNode.removeChild(lightbox);
          }
          lightbox = null;
          currentMedia = [];
          currentIndex = 0;
          currentVideoEl = null;
          if (lastFocusedEl) {
            lastFocusedEl.focus();
            lastFocusedEl = null;
          }
        };
        if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
          onTransitionEnd();
        } else {
          lightbox.addEventListener("transitionend", onTransitionEnd, { once: true });
          setTimeout(onTransitionEnd, 350);
        }
      }
      function renderCurrentMedia(playOnOpen) {
        if (!lightbox)
          return;
        var content = lightbox.querySelector(".beplus-advanced-reviews-for-woocommerce__lightbox-content");
        var counter = lightbox.querySelector(".beplus-advanced-reviews-for-woocommerce__lightbox-counter");
        if (!content)
          return;
        destroyVideo();
        var media = currentMedia[currentIndex];
        if (!media)
          return;
        var isVideo = media.mime_type && media.mime_type.indexOf("video/") === 0;
        content.innerHTML = "";
        if (isVideo) {
          var video = document.createElement("video");
          video.src = media.url;
          video.className = "beplus-advanced-reviews-for-woocommerce__lightbox-media beplus-advanced-reviews-for-woocommerce__lightbox-media--video";
          video.controls = true;
          video.setAttribute("playsinline", "");
          content.appendChild(video);
          currentVideoEl = video;
          if (playOnOpen) {
            video.play().catch(function() {
            });
          }
        } else {
          var img = document.createElement("img");
          img.src = media.url;
          img.className = "beplus-advanced-reviews-for-woocommerce__lightbox-media";
          img.alt = "";
          content.appendChild(img);
        }
        if (counter) {
          counter.textContent = currentIndex + 1 + " / " + currentMedia.length;
        }
        updateNavVisibility();
      }
      function navigate(direction) {
        if (!lightbox || !currentMedia.length)
          return;
        var newIndex = currentIndex + direction;
        if (newIndex < 0 || newIndex >= currentMedia.length)
          return;
        currentIndex = newIndex;
        renderCurrentMedia();
      }
      function updateNavVisibility() {
        var prevBtn = lightbox.querySelector(".beplus-advanced-reviews-for-woocommerce__lightbox-prev");
        var nextBtn = lightbox.querySelector(".beplus-advanced-reviews-for-woocommerce__lightbox-next");
        if (prevBtn) {
          prevBtn.style.visibility = currentIndex > 0 ? "" : "hidden";
        }
        if (nextBtn) {
          nextBtn.style.visibility = currentIndex < currentMedia.length - 1 ? "" : "hidden";
        }
      }
      function destroyVideo() {
        if (currentVideoEl) {
          currentVideoEl.pause();
          currentVideoEl.removeAttribute("src");
          currentVideoEl.load();
          currentVideoEl = null;
        }
      }
      function onKeyDown(e) {
        if (e.key === "Escape" || e.key === "Esc") {
          e.preventDefault();
          closeLightbox();
          return;
        }
        if (e.key === "ArrowLeft") {
          e.preventDefault();
          navigate(-1);
          return;
        }
        if (e.key === "ArrowRight") {
          e.preventDefault();
          navigate(1);
          return;
        }
        if (e.key === "Tab") {
          trapFocus(e);
        }
      }
      function trapFocus(e) {
        if (!lightbox)
          return;
        var focusable = lightbox.querySelectorAll(
          'button:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );
        if (!focusable.length)
          return;
        var first = focusable[0];
        var last = focusable[focusable.length - 1];
        if (e) {
          if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
          } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
          }
          return;
        }
        first.focus();
      }
    }
    function showFormMessage(block, message, type) {
      var formWrapper = block.querySelector(".beplus-advanced-reviews-for-woocommerce__submit-form-wrapper");
      if (!formWrapper)
        return;
      var existing = formWrapper.querySelector(".beplus-advanced-reviews-for-woocommerce__form-message");
      if (existing) {
        existing.remove();
      }
      var msg = document.createElement("div");
      msg.className = "beplus-advanced-reviews-for-woocommerce__form-message beplus-advanced-reviews-for-woocommerce__form-message--" + type;
      msg.textContent = message;
      msg.setAttribute("role", "alert");
      formWrapper.insertBefore(msg, formWrapper.firstChild);
      if (type !== "warning") {
        setTimeout(function() {
          msg.remove();
        }, 5e3);
      }
    }
    function initHashRedirect(block) {
      function getPluginTabAnchor() {
        var li = document.getElementById("tab-title-advanced_reviews");
        if (!li)
          return null;
        return li.querySelector("a");
      }
      var hash = window.location.hash;
      if (hash === "#reviews" || hash === "#tab-reviews") {
        var tabAnchor = getPluginTabAnchor();
        if (tabAnchor) {
          tabAnchor.click();
          setTimeout(function() {
            var container = document.getElementById("tab-advanced_reviews") || block;
            container.scrollIntoView({ behavior: "smooth", block: "start" });
          }, 200);
        } else {
          block.scrollIntoView({ behavior: "smooth", block: "start" });
        }
      }
      if (!hashRedirectInitialized) {
        hashRedirectInitialized = true;
        document.addEventListener("click", function(e) {
          var link = e.target.closest("a");
          if (!link)
            return;
          var href = link.getAttribute("href");
          if (!href)
            return;
          var isReviewsHash = href === "#reviews" || href === "#tab-reviews" || href.endsWith("#reviews") || href.endsWith("#tab-reviews");
          if (!isReviewsHash)
            return;
          e.preventDefault();
          e.stopPropagation();
          e.stopImmediatePropagation();
          var tabAnchor2 = getPluginTabAnchor();
          if (tabAnchor2) {
            tabAnchor2.click();
            setTimeout(function() {
              var container = document.getElementById("tab-advanced_reviews");
              if (container) {
                container.scrollIntoView({ behavior: "smooth", block: "start" });
              }
            }, 200);
          } else {
            var firstBlock = document.querySelector(".beplus-advanced-reviews-for-woocommerce[data-product-id]");
            if (firstBlock) {
              firstBlock.scrollIntoView({ behavior: "smooth", block: "start" });
            }
          }
        }, true);
      }
    }
  })();
})();
//# sourceMappingURL=view.js.map
