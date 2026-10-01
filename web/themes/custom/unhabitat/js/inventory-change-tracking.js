/**
 * ICTS Inventory device edit tracking.
 *
 * Records only fields that the user actually changes on the
 * ICTS asset edit form.
 */
(function (Drupal, once) {
  Drupal.behaviors.ictsAssetChangeTracking = {
    attach: function (context) {
      once('icts-asset-change-tracking', 'form.icts-asset-creation-form', context).forEach(function (form) {
        var changedFields = new Set();
        var hidden = form.querySelector('#icts-changed-fields');

        if (!hidden) {
          return;
        }

        function getFieldName(element) {
          var name = element.getAttribute('name') || '';
          var match = name.match(/^(field_[a-z0-9_]+)\[/);

          if (match) {
            return match[1];
          }

          var selector = element.getAttribute('data-drupal-selector') || '';
          match = selector.match(/^edit-(field-[a-z0-9-]+)/);

          if (match) {
            return match[1].replace(/-/g, '_');
          }

          return null;
        }

        form.querySelectorAll('input, select, textarea').forEach(function (element) {
          if (element === hidden) {
            return;
          }

          var fieldName = getFieldName(element);

          if (!fieldName) {
            return;
          }

          element.addEventListener('change', function () {
            changedFields.add(fieldName);
          });

          element.addEventListener('input', function () {
            changedFields.add(fieldName);
          });
        });

        form.addEventListener('submit', function () {
          hidden.value = Array.from(changedFields).join(',');
        });
      });
    }
  };
})(Drupal, once);

