












//
// main entry point of js
//
$(document).ready(function() {
  $( "#tabs" ).tabs(
    {
      // remember last selected Tab
      active: localStorage.getItem("currentTabIndex"),
      activate: function(event, ui) {
        localStorage.setItem("currentTabIndex", ui.newPanel[0].dataset.tabIndex);
      }
    }
  );

  setupSortableDivs();

  setupPropertyDialog();
  setupPropertySelection();

  setupHostDialog();
  setupHostSelection();

  setupDefaultValues();
  });
