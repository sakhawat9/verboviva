(function ($) {
  "use strict";

  /**
   * Table of contents
   * -----------------------------------
   * 01. PAGINATION
   * 02. FILTER
   * -----------------------------------
   */

  // =======================
  // 01. PAGINATION
  // =======================
  function loadActivities(page, instance) {
  const $section = $("#activities-section-" + instance);
  const taxonomy = $section.data("taxonomy") || "";
  const termId = $section.data("term") || 0;
  const postsPerPage = $section.data("posts-per-page") || 6;

  var $results = $("#styled-activity-results-" + instance);

  $.ajax({
    url: verboviva_activities.ajaxurl,
    type: "POST",
    data: {
      action: "styled_filter_activities",
      taxonomy: taxonomy,
      term_id: termId,
      posts_per_page: postsPerPage,
      instance: instance,
      pagination: true,
      page: page,
      _ajax_nonce: verboviva_activities.nonce,
    },
    beforeSend: function () {
      $results.find(".activities-grid").addClass("preloader");
    },
    success: function (response) {
      $results.fadeOut(150, function () {
        $results.html(response).fadeIn(150);
        $results.find(".activities-grid").removeClass("preloader");
      });
    },
    error: function () {
      $results.find(".activities-grid").removeClass("preloader");
      console.error("Error loading activities.");
    }
  });
}



  $(document).on("click", ".pagination-btn", function () {
    const instance = $(this).closest(".styled-pagination").data("instance");
    const page = $(this).data("page");
    loadActivities(page, instance);
  });

  // =======================
  // 02. FILTER
  // =======================

  // Update URL query string based on filters
  function updateActivitiesURL(filters) {
    let params = new URLSearchParams(window.location.search);
    ["level", "domain", "part-of-speech"].forEach((t) => params.delete(t));
    for (let key in filters) {
      if (filters[key]) params.set(key, filters[key]);
    }
    let newUrl =
      window.location.pathname +
      (params.toString() ? "?" + params.toString() : "");
    window.history.pushState({}, "", newUrl);
  }

  // Get current selected filters from selects
  function getFiltersFromSelects(wrapper) {
    let filters = {};
    wrapper.find("select.activity-filter").each(function () {
      let taxonomy = $(this).data("taxonomy");
      let termId = $(this).val();
      if (termId) filters[taxonomy] = termId;
    });
    return filters;
  }

  // Fetch AJAX results
  function fetchActivities(wrapper, filters, page = 1) {
    let instance = wrapper.data("instance");
    let postsPerPage = $("#activities-section-" + instance).data(
      "posts-per-page"
    );
    $.ajax({
      url: verboviva_activities.ajaxurl,
      type: "POST",
      data: {
        action: "styled_filter_activities",
        posts_per_page: postsPerPage,
        filters: filters,
        instance: instance,
        pagination: true,
        page: page,
        filter_bar: wrapper.data("filter-bar"),
        _ajax_nonce: verboviva_activities.nonce,
      },
      success: function (response) {
        $("#styled-activity-results-" + instance).html(response);
      },
    });
  }

  // On filter change
  $(document).on("change", ".activity-filter", function () {
    let wrapper = $(this).closest(".filter_wrapper");
    let filters = getFiltersFromSelects(wrapper);
    updateActivitiesURL(filters);
    fetchActivities(wrapper, filters, 1);
  });

  // On page load: preselect from URL and hide/show Reset button
  $(document).ready(function () {
    let params = new URLSearchParams(window.location.search);
    $(".filter_wrapper").each(function () {
      let wrapper = $(this);
      let filters = {};
      wrapper.find("select.activity-filter").each(function () {
        let taxonomy = $(this).data("taxonomy");
        if (params.has(taxonomy)) {
          let val = params.get(taxonomy);
          $(this).val(val);
          filters[taxonomy] = val;
        }
      });
      if (Object.keys(filters).length > 0) fetchActivities(wrapper, filters, 1);
    });
  });

  // Handle AJAX pagination buttons
  $(document).on("click", ".pagination-btn", function () {
    let page = $(this).data("page");
    let wrapper = $(this)
      .closest(".activities-section")
      .find(".filter_wrapper");
    let filters = getFiltersFromSelects(wrapper);
    fetchActivities(wrapper, filters, page);
  });
})(jQuery);


jQuery(document).ready(function($){
  $('.activities-preloader').hide(); // ensure hidden once posts rendered 
});
