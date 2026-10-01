/**
 * @file
 * UN-Habitat global utilities.
 */

(function (Drupal) {
  'use strict';

  Drupal.behaviors.unhabitatPrint = {
    attach: function (context) {
      var buttons = context.querySelectorAll
        ? context.querySelectorAll('a.unh-inventory-print-button, .ops-dashboard-print')
        : [];

      buttons.forEach(function (button) {
        if (button.dataset.unhPrintBound === '1') {
          return;
        }

        button.dataset.unhPrintBound = '1';

        button.addEventListener('click', function (event) {
          event.preventDefault();
          event.stopPropagation();
          window.print();
        });
      });
    }
  };

})(Drupal);


/* ICTS OPS PRINT BUTTON */
(function () {
  function addOpsPrintButton() {
    const path = window.location.pathname;

    if (path !== '/ops/projects' && path !== '/ops/tasks' && path !== '/ops/activity' && path !== '/ops/activity/') {
      return;
    }

    if (document.querySelector('.ops-direct-print-header')) {
      return;
    }

    const main = document.querySelector('main');
    if (!main) {
      return;
    }

    if (path === '/ops/dashboard') {
      return;
    }

    const header = document.createElement('div');
    header.className = 'unh-inventory-page-header ops-direct-print-header';

    const title = path === '/ops/projects' ? 'ICTS Projects' : path === '/ops/tasks' ? 'ICTS Tasks' : 'ICTS Operations Activity';
    const description = path === '/ops/projects'
      ? 'View and manage ICT projects, status, priorities and target completion dates.'
      : path === '/ops/tasks'
        ? 'View and manage operational tasks, priorities, assignments and due dates.'
        : 'Review recent ICTS operational activity, updates and changes.';

    header.innerHTML =
      '<div>' +
        '<span class="unh-inventory-page-kicker">UN-HABITAT</span>' +
        '<h1>' + title + '</h1>' +
        '<p>' + description + '</p>' +
      '</div>' +
      '<a href="#" class="unh-inventory-print-button ops-dashboard-print" onclick="window.print(); return false;">' +
        '<span class="unh-print-icon">🖨</span><span>Print</span>' +
      '</a>';

    const view = main.querySelector('.view-icts-ops-projects, .view-icts-ops-tasks, .view-icts-ops-activity');

    if (view) {
      view.parentNode.insertBefore(header, view);
    } else {
      main.insertBefore(header, main.firstChild);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', addOpsPrintButton);
  } else {
    addOpsPrintButton();
  }
})();

/* ICTS OPS ACTIVITY BUTTON */
(function () {
  function addOpsActivityButtons() {
    const path = window.location.pathname;

    if (path !== '/ops/tasks' && path !== '/ops/projects') {
      return;
    }

    document.querySelectorAll('main .views-row').forEach(function (row) {
      if (row.querySelector('.ops-view-activity-direct')) {
        return;
      }

      const editLink = row.querySelector('a[href*="/node/"][href*="/edit"]');
      if (!editLink) {
        return;
      }

      const match = editLink.getAttribute('href').match(/\/node\/(\d+)\/edit/);
      if (!match) {
        return;
      }

      const nid = match[1];

      const activityWrap = document.createElement('div');
      activityWrap.className = 'ops-view-activity-wrap';

      const activityLink = document.createElement('a');
      activityLink.href = '/ops/activity/' + nid;
      activityLink.className = 'ops-view-activity-direct';
      activityLink.textContent = 'View Activity';

      activityWrap.appendChild(activityLink);
      row.appendChild(activityWrap);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', addOpsActivityButtons);
  } else {
    addOpsActivityButtons();
  }
})();

/* Identify the Inventory Audit section for styling only */
document.addEventListener('DOMContentLoaded', function () {
  const inventory = document.querySelector('.unh-inventory');
  if (!inventory) return;

  const link = inventory.querySelector('a[href*="/inventory/activity"]');
  if (!link) return;

  let el = link.parentElement;

  while (el && el !== inventory) {
    const text = el.textContent.replace(/\s+/g, ' ').trim();

    if (
      text.includes('Activity Logs') &&
      text.includes('Track inventory additions, changes, assignments, transfers, returns and report activity')
    ) {
      el.classList.add('unh-audit-target');
      break;
    }

    el = el.parentElement;
  }
});

/* Inventory Activity header: identify the real rendered wrapper only */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.body.classList.contains('path-inventory-activity')) return;

  const heading = Array.from(document.querySelectorAll('h1')).find(function (el) {
    return el.textContent.trim().includes('Inventory Activity Logs');
  });

  const printLink = Array.from(document.querySelectorAll('a')).find(function (el) {
    return el.textContent.trim().toLowerCase().includes('print');
  });

  if (!heading || !printLink) return;

  let wrapper = heading.parentElement;

  while (wrapper && wrapper !== document.body) {
    if (wrapper.contains(printLink)) {
      wrapper.classList.add('unh-activity-header-target');
      break;
    }
    wrapper = wrapper.parentElement;
  }
});

/* Inventory Activity visual targeting only */
document.addEventListener('DOMContentLoaded', function () {
  const header = document.querySelector('.unh-inventory-page-header');
  if (!header) return;

  document.body.classList.add('inventory-activity-visual');

  document.querySelectorAll('a').forEach(function (link) {
    const text = link.textContent.replace(/\s+/g, ' ').trim();

    if (text === '← Back to Inventory' || text === 'Back to Inventory') {
      link.classList.add('inventory-back-button');
    }

    if (text === 'Print' || text.includes('Print')) {
      link.classList.add('inventory-print-button');
    }
  });

  document.querySelectorAll('button, input[type="submit"]').forEach(function (el) {
    const text = (el.textContent || el.value || '').replace(/\s+/g, ' ').trim();

    if (text === 'Print' || text.includes('Print')) {
      el.classList.add('inventory-print-button');
    }
  });

  const table = document.querySelector('.unh-inventory-table, table');
  if (table) {
    table.classList.add('inventory-activity-table');
  }
});

/* Inventory Activity exact visual targets */
document.addEventListener('DOMContentLoaded', function () {
  const header = document.querySelector('.unh-inventory-page-header');
  if (!header) return;

  document.querySelectorAll('a').forEach(function (link) {
    const text = link.textContent.replace(/\s+/g, ' ').trim();

    if (text === '← Back to Inventory' || text === 'Back to Inventory') {
      link.classList.add('unh-inventory-back-link');
    }
  });
});


/* ICTS INVENTORY PRINT ENGINE */
(function () {
  if (window.__unhInventoryPrintEngine) {
    return;
  }

  window.__unhInventoryPrintEngine = true;

  let savedStyles = new Map();

  function isInventoryPage() {
    const path = window.location.pathname;
    return path === '/inventory' || path.indexOf('/inventory/') === 0;
  }

  function saveStyle(el) {
    if (!savedStyles.has(el)) {
      savedStyles.set(el, el.getAttribute('style'));
    }
  }

  function setImportant(el, property, value) {
    saveStyle(el);
    el.style.setProperty(property, value, 'important');
  }

  function preparePrint() {
    if (!isInventoryPage() || savedStyles.size) {
      return;
    }

    const hideSelectors = [
      '.unh-inventory-print-button',
      '.inventory-print-button',
      '.unh-inventory-print-row',
      '.unh-inventory-back-row',
      '.unh-inventory-back-button',
      '.inventory-back-button',
      '.unh-print-icon',
      '.unh-inventory-actions',
      '.unh-inventory-assignment-actions',
      '.form-actions',
      '.unh-site-header',
      '.unh-footer',
      '.unh-sidebar',
      '.sidebar_first',
      '.sidebar_second',
      'aside',
      '#toolbar-administration',
      '.toolbar',
      'nav'
    ];

    document.querySelectorAll(hideSelectors.join(',')).forEach(function (el) {
      setImportant(el, 'display', 'none');
      setImportant(el, 'visibility', 'hidden');
    });

    const layoutSelectors = [
      'html',
      'body',
      'main',
      '.unh-main',
      '.unh-content-layout',
      '.unh-content',
      '.main-content',
      '.region-content',
      '.unh-inventory-page',
      '.container',
      '.container-fluid'
    ];

    document.querySelectorAll(layoutSelectors.join(',')).forEach(function (el) {
      setImportant(el, 'display', 'block');
      setImportant(el, 'position', 'static');
      setImportant(el, 'float', 'none');
      setImportant(el, 'width', '100%');
      setImportant(el, 'max-width', 'none');
      setImportant(el, 'min-width', '0');
      setImportant(el, 'margin-left', '0');
      setImportant(el, 'margin-right', '0');
      setImportant(el, 'grid-template-columns', 'none');
      setImportant(el, 'grid-template-rows', 'none');
      setImportant(el, 'grid-column', 'auto');
      setImportant(el, 'grid-row', 'auto');
      setImportant(el, 'transform', 'none');
      setImportant(el, 'left', 'auto');
      setImportant(el, 'right', 'auto');
      setImportant(el, 'overflow', 'visible');
    });

    const path = window.location.pathname;

    let tableFont = '9px';

    if (path.indexOf('/inventory/devices') === 0) {
      tableFont = '7.5px';
    }
    else if (path.indexOf('/inventory/activity') === 0) {
      tableFont = '8.5px';
    }

    const tables = document.querySelectorAll(
      'table.unh-inventory-table, table.unh-inventory-report-table, .path-inventory table'
    );

    tables.forEach(function (table) {
      setImportant(table, 'display', 'table');
      setImportant(table, 'width', '100%');
      setImportant(table, 'max-width', 'none');
      setImportant(table, 'min-width', '0');
      setImportant(table, 'table-layout', 'auto');
      setImportant(table, 'border-collapse', 'collapse');
      setImportant(table, 'border-spacing', '0');
      setImportant(table, 'margin-left', '0');
      setImportant(table, 'margin-right', '0');
      setImportant(table, 'overflow', 'visible');
      setImportant(table, 'font-size', tableFont);
      setImportant(table, 'line-height', '1.2');
      setImportant(table, 'print-color-adjust', 'exact');
      setImportant(table, '-webkit-print-color-adjust', 'exact');
    });

    document.querySelectorAll(
      'table.unh-inventory-table th, table.unh-inventory-table td, ' +
      'table.unh-inventory-report-table th, table.unh-inventory-report-table td, ' +
      '.path-inventory table th, .path-inventory table td'
    ).forEach(function (cell) {
      setImportant(cell, 'display', 'table-cell');
      setImportant(cell, 'min-width', '0');
      setImportant(cell, 'max-width', 'none');
      setImportant(cell, 'white-space', 'normal');
      setImportant(cell, 'overflow', 'visible');
      setImportant(cell, 'overflow-wrap', 'anywhere');
      setImportant(cell, 'word-break', 'normal');
      setImportant(cell, 'vertical-align', 'middle');
      setImportant(cell, 'padding', '6px');
      setImportant(cell, 'border', '1px solid #8798a1');
      setImportant(cell, 'print-color-adjust', 'exact');
      setImportant(cell, '-webkit-print-color-adjust', 'exact');
    });

    document.documentElement.style.setProperty(
      '-webkit-print-color-adjust',
      'exact'
    );

    document.body.style.setProperty(
      '-webkit-print-color-adjust',
      'exact'
    );

    document.documentElement.style.setProperty(
      'print-color-adjust',
      'exact'
    );

    document.body.style.setProperty(
      'print-color-adjust',
      'exact'
    );
  }

  function restorePrint() {
    savedStyles.forEach(function (style, el) {
      if (style === null) {
        el.removeAttribute('style');
      }
      else {
        el.setAttribute('style', style);
      }
    });

    savedStyles.clear();
  }

  window.addEventListener('beforeprint', preparePrint);
  window.addEventListener('afterprint', restorePrint);

  document.addEventListener('click', function (event) {
    if (!isInventoryPage()) {
      return;
    }

    const printControl = event.target.closest(
      '.unh-inventory-print-button, .inventory-print-button'
    );

    if (printControl) {
      preparePrint();
    }
  }, true);
})();


/* ICTS DEVICES PRINT: HIDE ACTIONS COLUMN */
(function () {
  if (window.__unhDevicesActionsPrintFix) return;
  window.__unhDevicesActionsPrintFix = true;

  const saved = [];

  function isDevicesPage() {
    return window.location.pathname === '/inventory/devices';
  }

  function save(el) {
    saved.push({
      el: el,
      style: el.getAttribute('style')
    });
  }

  function hideActionsColumn() {
    if (!isDevicesPage()) return;

    document.querySelectorAll('.path-inventory-devices table.unh-inventory-table').forEach(function (table) {
      const headers = Array.from(table.querySelectorAll('thead th'));

      headers.forEach(function (th, index) {
        const text = (th.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();

        if (text === 'actions') {
          table.querySelectorAll('tr').forEach(function (row) {
            const cell = row.children[index];

            if (cell) {
              save(cell);
              cell.style.setProperty('display', 'none', 'important');
            }
          });
        }
      });

      table.querySelectorAll('th, td').forEach(function (cell) {
        save(cell);
        cell.style.setProperty('text-shadow', 'none', 'important');
        cell.style.setProperty('-webkit-text-stroke', '0', 'important');
        cell.style.setProperty('letter-spacing', 'normal', 'important');
        cell.style.setProperty('transform', 'none', 'important');
      });
    });
  }

  function restoreActionsColumn() {
    saved.forEach(function (item) {
      if (item.style === null) {
        item.el.removeAttribute('style');
      } else {
        item.el.setAttribute('style', item.style);
      }
    });

    saved.length = 0;
  }

  window.addEventListener('beforeprint', hideActionsColumn);
  window.addEventListener('afterprint', restoreActionsColumn);

  document.addEventListener('click', function (event) {
    if (!isDevicesPage()) return;

    const printControl = event.target.closest(
      '.unh-inventory-print-button, .inventory-print-button'
    );

    if (printControl) {
      hideActionsColumn();
    }
  }, true);
})();



/* =========================================================
   ICTS DEVICES PRINT FINAL FIX
   ========================================================= */
(function () {
  if (window.__unhDevicesPrintFinalFix) return;
  window.__unhDevicesPrintFinalFix = true;

  const saved = [];

  function isDevicesPage() {
    return window.location.pathname === '/inventory/devices';
  }

  function prepareDevicesPrint() {
    if (!isDevicesPage()) return;

    document.querySelectorAll('table.unh-inventory-table').forEach(function (table) {

      /* Remove Actions from the printed table.
         Actions is the final column. */
      table.querySelectorAll('thead tr > :last-child, tbody tr > :last-child').forEach(function (cell) {
        saved.push({
          el: cell,
          style: cell.getAttribute('style')
        });

        cell.style.setProperty('display', 'none', 'important');
      });

      /* Remove the doubled-character effect from printed text. */
      table.querySelectorAll('*').forEach(function (el) {
        saved.push({
          el: el,
          style: el.getAttribute('style')
        });

        el.style.setProperty('text-shadow', 'none', 'important');
        el.style.setProperty('filter', 'none', 'important');
        el.style.setProperty('-webkit-filter', 'none', 'important');
        el.style.setProperty('-webkit-text-stroke', '0', 'important');
        el.style.setProperty('letter-spacing', 'normal', 'important');
      });
    });
  }

  function restoreDevicesPrint() {
    saved.forEach(function (item) {
      if (item.style === null) {
        item.el.removeAttribute('style');
      } else {
        item.el.setAttribute('style', item.style);
      }
    });

    saved.length = 0;
  }

  window.addEventListener('beforeprint', prepareDevicesPrint);
  window.addEventListener('afterprint', restoreDevicesPrint);

  document.addEventListener('click', function (event) {
    if (!isDevicesPage()) return;

    const control = event.target.closest(
      '.unh-inventory-print-button, .inventory-print-button'
    );

    if (control) {
      prepareDevicesPrint();
    }
  }, true);
})();

/* =========================================================
   ICTS DEVICES PRINT: HIDE COLLAPSE SIDEBAR
   ========================================================= */
(function () {
  if (window.__unhHideCollapseSidebarPrint) return;
  window.__unhHideCollapseSidebarPrint = true;

  const saved = [];

  function isDevicesPage() {
    return window.location.pathname === '/inventory/devices';
  }

  function hideCollapseSidebar() {
    if (!isDevicesPage()) return;

    document.querySelectorAll('button, a, [role="button"]').forEach(function (el) {
      const text = (el.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();

      if (text === 'collapse sidebar') {
        saved.push({
          el: el,
          style: el.getAttribute('style')
        });

        el.style.setProperty('display', 'none', 'important');
      }
    });
  }

  function restoreCollapseSidebar() {
    saved.forEach(function (item) {
      if (item.style === null) {
        item.el.removeAttribute('style');
      } else {
        item.el.setAttribute('style', item.style);
      }
    });

    saved.length = 0;
  }

  window.addEventListener('beforeprint', hideCollapseSidebar);
  window.addEventListener('afterprint', restoreCollapseSidebar);

  document.addEventListener('click', function (event) {
    if (!isDevicesPage()) return;

    const control = event.target.closest(
      '.unh-inventory-print-button, .inventory-print-button'
    );

    if (control) {
      hideCollapseSidebar();
    }
  }, true);
})();

/* =========================================================
   ICTS DEVICE ACTIVITY: DEVICE IDENTIFIER
   ========================================================= */
(function () {
  if (window.__unhDeviceIdentifierStyle) return;
  window.__unhDeviceIdentifierStyle = true;

  function markDeviceIdentifier() {
    if (!document.body.classList.contains('inventory-activity-visual')) return;

    document.querySelectorAll('body *').forEach(function (el) {
      if (el.children.length > 0) return;

      const text = (el.textContent || '').replace(/\s+/g, ' ').trim();

      if (/^Device:\s*ICTS-/i.test(text)) {
        el.classList.add('inventory-device-identifier');
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', markDeviceIdentifier);
  } else {
    markDeviceIdentifier();
  }
})();

/* =========================================================
   ICTS DEVICE ACTIVITY: DIRECT DEVICE LABEL HIGHLIGHT
   ========================================================= */
(function () {
  if (window.__unhDirectDeviceLabelHighlight) return;
  window.__unhDirectDeviceLabelHighlight = true;

  function highlightDeviceLabel() {
    if (!/^\/inventory\/devices\/\d+\/activity\/?$/.test(window.location.pathname)) {
      return;
    }

    document.querySelectorAll('p, div, span, strong, h1, h2, h3').forEach(function (el) {
      if (el.children.length !== 0) return;

      const text = (el.textContent || '').replace(/\s+/g, ' ').trim();

      if (/^Device:\s*ICTS-\S+/i.test(text)) {
        el.style.setProperty('display', 'inline-block', 'important');
        el.style.setProperty('background', '#17324d', 'important');
        el.style.setProperty('background-color', '#17324d', 'important');
        el.style.setProperty('color', '#ffffff', 'important');
        el.style.setProperty('padding', '10px 18px', 'important');
        el.style.setProperty('margin', '12px 0 18px', 'important');
        el.style.setProperty('border', '2px solid #0f263a', 'important');
        el.style.setProperty('border-left', '7px solid #169c68', 'important');
        el.style.setProperty('border-radius', '8px', 'important');
        el.style.setProperty('font-family', 'Arial, Helvetica, sans-serif', 'important');
        el.style.setProperty('font-size', '20px', 'important');
        el.style.setProperty('font-weight', '900', 'important');
        el.style.setProperty('line-height', '1.25', 'important');
        el.style.setProperty('letter-spacing', '0', 'important');
        el.style.setProperty('text-shadow', 'none', 'important');
        el.style.setProperty('box-shadow', '0 3px 10px rgba(0,0,0,.16)', 'important');
      }
    });
  }

  document.addEventListener('DOMContentLoaded', highlightDeviceLabel);
  window.addEventListener('load', highlightDeviceLabel);
  setTimeout(highlightDeviceLabel, 300);
  setTimeout(highlightDeviceLabel, 1000);
})();

/* =========================================================
   ICTS DEVICES: VISUAL MARKER
   ========================================================= */
(function () {
  if (window.__unhDevicesVisualMarker) return;
  window.__unhDevicesVisualMarker = true;

  if (window.location.pathname === '/inventory/devices') {
    document.body.classList.add('inventory-devices-visual');
  }
})();

/* =========================================================
   ICTS INVENTORY ACTIVITY: VISUAL MARKER
   ========================================================= */
(function () {
  if (window.__unhInventoryActivityVisualMarker) return;
  window.__unhInventoryActivityVisualMarker = true;

  if (window.location.pathname === '/inventory/activity') {
    document.body.classList.add('inventory-main-activity-visual');
  }
})();

/* =========================================================
   ICTS INVENTORY DASHBOARD: VISUAL MARKER
   ========================================================= */
(function () {
  if (window.__unhInventoryDashboardVisualMarker) return;
  window.__unhInventoryDashboardVisualMarker = true;

  if (window.location.pathname === '/inventory') {
    document.body.classList.add('inventory-dashboard-visual');
  }
})();

/* =========================================================
   ICTS INVENTORY MAIN HEADER: DIRECT VISUAL OVERRIDE
   ========================================================= */
(function () {
  if (window.__unhInventoryMainHeaderDirectFix) return;
  window.__unhInventoryMainHeaderDirectFix = true;

  function applyInventoryHeader() {
    if (window.location.pathname !== '/inventory') return;

    document.querySelectorAll('h1').forEach(function (heading) {
      if ((heading.textContent || '').trim() !== 'ICTS Inventory') return;

      var header = heading.closest('.unh-inventory-page-header');

      if (!header) {
        header = heading.parentElement;
      }

      if (!header) return;

      header.style.setProperty(
        'background',
        'linear-gradient(110deg, #374147 0%, #087a4b 48%, #009edb 100%)',
        'important'
      );

      header.style.setProperty(
        'background-color',
        '#4f5b62',
        'important'
      );

      header.style.setProperty('background-image',
        'radial-gradient(circle at 10% 50%, rgba(79,91,98,1) 0%, rgba(79,91,98,.92) 24%, transparent 60%),' +
        'radial-gradient(circle at 48% 50%, rgba(8,122,75,1) 0%, rgba(8,122,75,.86) 27%, transparent 62%),' +
        'radial-gradient(circle at 88% 50%, rgba(0,158,219,1) 0%, rgba(0,158,219,.86) 27%, transparent 62%),' +
        'linear-gradient(110deg, #374147 0%, #087a4b 48%, #009edb 100%)',
        'important'
      );

      header.style.setProperty('border', '0', 'important');
      header.style.setProperty('border-left', '9px solid #374147', 'important');
      header.style.setProperty('border-radius', '12px', 'important');
      header.style.setProperty(
        'box-shadow',
        '0 8px 24px rgba(0,0,0,.22)',
        'important'
      );

      header.querySelectorAll('h1, p, .unh-inventory-page-kicker').forEach(function (el) {
        el.style.setProperty('color', '#ffffff', 'important');
        el.style.setProperty('opacity', '1', 'important');
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applyInventoryHeader);
  } else {
    applyInventoryHeader();
  }
})();

/* =========================================================
   ICTS INVENTORY REPORTS TITLE SECTION
   DIRECT BLENDED HEADER
   ========================================================= */
(function () {
  if (window.__unhInventoryReportsTitleBlend) return;
  window.__unhInventoryReportsTitleBlend = true;

  function applyReportsTitleBlend() {
    if (window.location.pathname !== '/inventory/reports') return;

    document.querySelectorAll('h1').forEach(function (heading) {
      if ((heading.textContent || '').trim() !== 'ICTS Inventory Reports') {
        return;
      }

      var section = heading.closest('.unh-inventory-page-header');

      if (!section) {
        section = heading.parentElement;
      }

      if (!section) return;

      section.style.setProperty(
        'background-image',
        'linear-gradient(110deg, #374147 0%, #087a4b 48%, #009edb 100%)',
        'important'
      );

      section.style.setProperty(
        'background-color',
        '#4f5b62',
        'important'
      );

      section.style.setProperty('color', '#ffffff', 'important');
      section.style.setProperty('border', '0', 'important');
      section.style.setProperty('border-radius', '12px', 'important');
      section.style.setProperty(
        'box-shadow',
        '0 8px 24px rgba(0,0,0,.22)',
        'important'
      );

      section.querySelectorAll('h1, p').forEach(function (el) {
        el.style.setProperty('color', '#ffffff', 'important');
        el.style.setProperty('opacity', '1', 'important');
        el.style.setProperty('text-shadow', '0 2px 4px rgba(0,0,0,.25)', 'important');
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applyReportsTitleBlend);
  } else {
    applyReportsTitleBlend();
  }
})();

/* =========================================================
   ICTS INVENTORY ACTIVITY TABLE
   SHARP, CLEAN, READABLE TYPOGRAPHY
   ========================================================= */
(function () {
  if (window.__unhInventoryActivityTableTextFix) return;
  window.__unhInventoryActivityTableTextFix = true;

  function applyActivityTableText() {
    if (window.location.pathname !== '/inventory/activity') return;

    document.querySelectorAll('table').forEach(function (table) {
      var headers = Array.from(table.querySelectorAll('thead th'))
        .map(function (th) {
          return (th.textContent || '').trim();
        });

      if (!headers.includes('Action') ||
          !headers.includes('Device / Item') ||
          !headers.includes('Reason / Note')) {
        return;
      }

      table.style.setProperty('font-family', 'Arial, Helvetica, sans-serif', 'important');
      table.style.setProperty('font-size', '14px', 'important');
      table.style.setProperty('line-height', '1.35', 'important');
      table.style.setProperty('color', '#17324d', 'important');

      table.querySelectorAll('thead th').forEach(function (cell) {
        cell.style.setProperty('font-family', 'Arial, Helvetica, sans-serif', 'important');
        cell.style.setProperty('font-size', '15px', 'important');
        cell.style.setProperty('font-weight', '900', 'important');
        cell.style.setProperty('line-height', '1.25', 'important');
        cell.style.setProperty('letter-spacing', '0', 'important');
        cell.style.setProperty('padding', '12px 10px', 'important');
        cell.style.setProperty('vertical-align', 'middle', 'important');
        cell.style.setProperty('white-space', 'normal', 'important');
      });

      table.querySelectorAll('tbody td').forEach(function (cell) {
        cell.style.setProperty('font-family', 'Arial, Helvetica, sans-serif', 'important');
        cell.style.setProperty('font-size', '14px', 'important');
        cell.style.setProperty('font-weight', '600', 'important');
        cell.style.setProperty('line-height', '1.4', 'important');
        cell.style.setProperty('letter-spacing', '0', 'important');
        cell.style.setProperty('padding', '11px 10px', 'important');
        cell.style.setProperty('vertical-align', 'middle', 'important');
        cell.style.setProperty('color', '#17324d', 'important');
        cell.style.setProperty('text-shadow', 'none', 'important');
      });

      table.querySelectorAll('tbody td:first-child').forEach(function (cell) {
        cell.style.setProperty('font-weight', '900', 'important');
      });

      table.querySelectorAll('tbody tr').forEach(function (row) {
        row.style.setProperty('min-height', '46px', 'important');
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applyActivityTableText);
  } else {
    applyActivityTableText();
  }
})();

/* =========================================================
   ICTS INVENTORY ACTIVITY PRINT
   HIDE COLLAPSE SIDEBAR
   ========================================================= */
(function () {
  if (window.__unhInventoryActivityHideCollapseSidebar) return;
  window.__unhInventoryActivityHideCollapseSidebar = true;

  const saved = [];

  function isActivityPage() {
    return window.location.pathname === '/inventory/activity';
  }

  function hideCollapseSidebar() {
    if (!isActivityPage()) return;

    document.querySelectorAll('button, a, [role="button"]').forEach(function (el) {
      const text = (el.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();

      if (text === 'collapse sidebar') {
        saved.push({
          el: el,
          style: el.getAttribute('style')
        });

        el.style.setProperty('display', 'none', 'important');
      }
    });
  }

  function restoreCollapseSidebar() {
    saved.forEach(function (item) {
      if (item.style === null) {
        item.el.removeAttribute('style');
      } else {
        item.el.setAttribute('style', item.style);
      }
    });

    saved.length = 0;
  }

  window.addEventListener('beforeprint', hideCollapseSidebar);
  window.addEventListener('afterprint', restoreCollapseSidebar);

  document.addEventListener('click', function (event) {
    if (!isActivityPage()) return;

    const control = event.target.closest(
      '.unh-inventory-print-button, .inventory-print-button'
    );

    if (control) {
      hideCollapseSidebar();
    }
  }, true);
})();

/* =========================================================
   ICTS INVENTORY USERS PRINT
   HIDE COLLAPSE SIDEBAR
   ========================================================= */
(function () {
  if (window.__unhInventoryUsersHideCollapseSidebar) return;
  window.__unhInventoryUsersHideCollapseSidebar = true;

  const saved = [];

  function isUsersPage() {
    return window.location.pathname === '/inventory/users';
  }

  function hideCollapseSidebar() {
    if (!isUsersPage()) return;

    document.querySelectorAll('button, a, [role="button"]').forEach(function (el) {
      const text = (el.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();

      if (text === 'collapse sidebar') {
        saved.push({
          el: el,
          style: el.getAttribute('style')
        });

        el.style.setProperty('display', 'none', 'important');
      }
    });
  }

  function restoreCollapseSidebar() {
    saved.forEach(function (item) {
      if (item.style === null) {
        item.el.removeAttribute('style');
      } else {
        item.el.setAttribute('style', item.style);
      }
    });

    saved.length = 0;
  }

  window.addEventListener('beforeprint', hideCollapseSidebar);
  window.addEventListener('afterprint', restoreCollapseSidebar);

  document.addEventListener('click', function (event) {
    if (!isUsersPage()) return;

    const control = event.target.closest(
      '.unh-inventory-print-button, .inventory-print-button'
    );

    if (control) {
      hideCollapseSidebar();
    }
  }, true);
})();

/* =========================================================
   ICTS INVENTORY REPORTS PRINT
   HIDE COLLAPSE SIDEBAR
   ========================================================= */
(function () {
  if (window.__unhInventoryReportsHideCollapseSidebar) return;
  window.__unhInventoryReportsHideCollapseSidebar = true;

  const saved = [];

  function isReportsPage() {
    return window.location.pathname === '/inventory/reports';
  }

  function hideCollapseSidebar() {
    if (!isReportsPage()) return;

    document.querySelectorAll('button, a, [role="button"]').forEach(function (el) {
      const text = (el.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();

      if (text === 'collapse sidebar') {
        saved.push({
          el: el,
          style: el.getAttribute('style')
        });

        el.style.setProperty('display', 'none', 'important');
      }
    });
  }

  function restoreCollapseSidebar() {
    saved.forEach(function (item) {
      if (item.style === null) {
        item.el.removeAttribute('style');
      } else {
        item.el.setAttribute('style', item.style);
      }
    });

    saved.length = 0;
  }

  window.addEventListener('beforeprint', hideCollapseSidebar);
  window.addEventListener('afterprint', restoreCollapseSidebar);

  document.addEventListener('click', function (event) {
    if (!isReportsPage()) return;

    const control = event.target.closest(
      '.unh-inventory-print-button, .inventory-print-button'
    );

    if (control) {
      hideCollapseSidebar();
    }
  }, true);
})();

/* =========================================================
   ICTS INVENTORY PRINT BRANDING
   GLOBAL FOR ALL INVENTORY MODULES
   ========================================================= */
(function () {
  if (window.__unhInventoryPrintBranding) return;
  window.__unhInventoryPrintBranding = true;

  var brand = null;
  var originalParent = null;
  var originalNextSibling = null;

  function isInventoryPage() {
    return window.location.pathname === '/inventory' ||
      window.location.pathname.indexOf('/inventory/') === 0;
  }

  function addPrintBranding() {
    if (!isInventoryPage() || brand) return;

    brand = document.createElement('div');
    brand.className = 'unh-inventory-print-brand';

    brand.innerHTML =
      '<div class="unh-inventory-print-brand-name">UN-HABITAT</div>' +
      '<div class="unh-inventory-print-brand-title">ICTS Inventory</div>';

    var target =
      document.querySelector('.unh-inventory-page') ||
      document.querySelector('main') ||
      document.body;

    originalParent = target;
    originalNextSibling = target.firstChild;

    target.insertBefore(brand, originalNextSibling);
  }

  function removePrintBranding() {
    if (!brand) return;

    brand.remove();
    brand = null;
    originalParent = null;
    originalNextSibling = null;
  }

  window.addEventListener('beforeprint', addPrintBranding);
  window.addEventListener('afterprint', removePrintBranding);

  document.addEventListener('click', function (event) {
    if (!isInventoryPage()) return;

    var control = event.target.closest(
      '.unh-inventory-print-button, .inventory-print-button'
    );

    if (control) {
      addPrintBranding();
    }
  }, true);
})();

/* =========================================================
   ICTS INVENTORY PRINT BRANDING
   FULL WIDTH TOP BANNER
   ========================================================= */
(function () {
  if (window.__unhInventoryPrintBrandingFullWidth) return;
  window.__unhInventoryPrintBrandingFullWidth = true;

  function isInventoryPage() {
    return window.location.pathname === '/inventory' ||
      window.location.pathname.indexOf('/inventory/') === 0;
  }

  function fixPrintBrandingPosition() {
    if (!isInventoryPage()) return;

    var brand = document.querySelector('.unh-inventory-print-brand');

    if (!brand) return;

    document.body.insertBefore(brand, document.body.firstChild);

    brand.style.setProperty('position', 'relative', 'important');
    brand.style.setProperty('display', 'block', 'important');
    brand.style.setProperty('width', '100%', 'important');
    brand.style.setProperty('max-width', 'none', 'important');
    brand.style.setProperty('box-sizing', 'border-box', 'important');
    brand.style.setProperty('margin', '0 0 18px 0', 'important');
  }

  window.addEventListener('beforeprint', fixPrintBrandingPosition);
})();

/* =========================================================
   ICTS OPS PRINT
   APPLY ONLY TO /ops/ PAGES WITH A PRINT BUTTON
   ========================================================= */
(function () {
  if (window.__unhOpsPrintButtonOnlyFix) return;
  window.__unhOpsPrintButtonOnlyFix = true;

  function isOpsPage() {
    return window.location.pathname === '/ops' ||
      window.location.pathname.indexOf('/ops/') === 0;
  }

  function hasPrintButton() {
    return Array.from(
      document.querySelectorAll(
        '.unh-inventory-print-button, .inventory-print-button, .print-button, button, a'
      )
    ).some(function (el) {
      return /print/i.test((el.textContent || '').trim());
    });
  }

  function enableOpsPrintFix() {
    if (isOpsPage() && hasPrintButton()) {
      document.body.classList.add('unh-ops-print-button-page');
    }
  }

  function disableOpsPrintFix() {
    document.body.classList.remove('unh-ops-print-button-page');
  }

  window.addEventListener('beforeprint', enableOpsPrintFix);
  window.addEventListener('afterprint', disableOpsPrintFix);

  document.addEventListener('click', function (event) {
    if (!isOpsPage()) return;

    var control = event.target.closest(
      '.unh-inventory-print-button, .inventory-print-button, .print-button, button, a'
    );

    if (control && /print/i.test((control.textContent || '').trim())) {
      enableOpsPrintFix();
    }
  }, true);
})();

/* =========================================================
   ICTS OPS PRINT
   HIDE COLLAPSE SIDEBAR ON PRINT-BUTTON PAGES
   ========================================================= */
(function () {
  if (window.__unhOpsPrintHideCollapseSidebar) return;
  window.__unhOpsPrintHideCollapseSidebar = true;

  const saved = [];

  function isOpsPage() {
    return window.location.pathname === '/ops' ||
      window.location.pathname.indexOf('/ops/') === 0;
  }

  function hasPrintButton() {
    return Array.from(
      document.querySelectorAll(
        '.unh-inventory-print-button, .inventory-print-button, .print-button, button, a'
      )
    ).some(function (el) {
      return /print/i.test((el.textContent || '').trim());
    });
  }

  function hideCollapseSidebar() {
    if (!isOpsPage() || !hasPrintButton()) return;

    document.querySelectorAll('button, a, [role="button"]').forEach(function (el) {
      const text = (el.textContent || '')
        .replace(/\s+/g, ' ')
        .trim()
        .toLowerCase();

      if (text === 'collapse sidebar') {
        saved.push({
          el: el,
          style: el.getAttribute('style')
        });

        el.style.setProperty('display', 'none', 'important');
      }
    });
  }

  function restoreCollapseSidebar() {
    saved.forEach(function (item) {
      if (item.style === null) {
        item.el.removeAttribute('style');
      } else {
        item.el.setAttribute('style', item.style);
      }
    });

    saved.length = 0;
  }

  window.addEventListener('beforeprint', hideCollapseSidebar);
  window.addEventListener('afterprint', restoreCollapseSidebar);
})();

/* =========================================================
   ICTS OPS PRINT
   PRINT CONTENT ONLY
   ALL /ops/ PAGES WITH A PRINT BUTTON
   ========================================================= */
(function () {
  if (window.__unhOpsPrintContentOnly) return;
  window.__unhOpsPrintContentOnly = true;

  const saved = [];

  function isOpsPage() {
    return window.location.pathname === '/ops' ||
      window.location.pathname.indexOf('/ops/') === 0;
  }

  function hasPrintButton() {
    return Array.from(
      document.querySelectorAll(
        '.unh-inventory-print-button, .inventory-print-button, .print-button, button, a'
      )
    ).some(function (el) {
      return /print/i.test((el.textContent || '').trim());
    });
  }

  function hideElement(el) {
    saved.push({
      el: el,
      style: el.getAttribute('style')
    });

    el.style.setProperty('display', 'none', 'important');
  }

  function prepareOpsPrint() {
    if (!isOpsPage() || !hasPrintButton()) return;

    /* Site chrome */
    document.querySelectorAll(
      '.unh-site-header,' +
      '.unh-navigation,' +
      '.unh-footer,' +
      '.unh-sidebar,' +
      '.sidebar_first,' +
      '.sidebar_second,' +
      'aside,' +
      'nav'
    ).forEach(hideElement);

    /* Print and navigation controls */
    document.querySelectorAll('button, a, [role="button"]').forEach(function (el) {
      const text = (el.textContent || '')
        .replace(/\s+/g, ' ')
        .trim()
        .toLowerCase();

      if (
        text === 'collapse sidebar' ||
        text === 'print' ||
        text === '← back' ||
        text === 'back' ||
        text.indexOf('back to ') === 0
      ) {
        hideElement(el);
      }
    });

    /* Remove common page chrome around the actual module content */
    document.querySelectorAll(
      '.breadcrumb,' +
      '.region-breadcrumb,' +
      '.messages,' +
      '.tabs,' +
      '.action-links'
    ).forEach(hideElement);

    /* Force the remaining main content to full width */
    document.querySelectorAll(
      '.unh-main,' +
      '.unh-content-layout,' +
      '.unh-content,' +
      '.main-content,' +
      '.region-content,' +
      'main'
    ).forEach(function (el) {
      saved.push({
        el: el,
        style: el.getAttribute('style')
      });

      el.style.setProperty('display', 'block', 'important');
      el.style.setProperty('width', '100%', 'important');
      el.style.setProperty('max-width', 'none', 'important');
      el.style.setProperty('min-width', '0', 'important');
      el.style.setProperty('margin', '0', 'important');
      el.style.setProperty('padding', '0', 'important');
      el.style.setProperty('left', 'auto', 'important');
      el.style.setProperty('right', 'auto', 'important');
      el.style.setProperty('transform', 'none', 'important');
      el.style.setProperty('grid-template-columns', 'none', 'important');
      el.style.setProperty('overflow', 'visible', 'important');
    });

    /* Remove Bootstrap/container width restrictions */
    document.querySelectorAll('.container, .container-fluid').forEach(function (el) {
      saved.push({
        el: el,
        style: el.getAttribute('style')
      });

      el.style.setProperty('width', '100%', 'important');
      el.style.setProperty('max-width', 'none', 'important');
      el.style.setProperty('margin-left', '0', 'important');
      el.style.setProperty('margin-right', '0', 'important');
      el.style.setProperty('padding-left', '0', 'important');
      el.style.setProperty('padding-right', '0', 'important');
    });
  }

  function restoreOpsPrint() {
    saved.forEach(function (item) {
      if (item.style === null) {
        item.el.removeAttribute('style');
      } else {
        item.el.setAttribute('style', item.style);
      }
    });

    saved.length = 0;
  }

  window.addEventListener('beforeprint', prepareOpsPrint);
  window.addEventListener('afterprint', restoreOpsPrint);

  document.addEventListener('click', function (event) {
    if (!isOpsPage()) return;

    const control = event.target.closest(
      '.unh-inventory-print-button, .inventory-print-button, .print-button, button, a'
    );

    if (
      control &&
      /print/i.test((control.textContent || '').replace(/\s+/g, ' ').trim())
    ) {
      prepareOpsPrint();
    }
  }, true);
})();

/* =========================================================
   OPS DASHBOARD PRINT
   REMOVE MANAGEMENT HEADER + ACTION BAR
   KEEP ACTUAL DASHBOARD CONTENT
   ========================================================= */
(function () {
  if (window.__unhOpsDashboardPrintContentOnly) return;
  window.__unhOpsDashboardPrintContentOnly = true;

  const saved = [];

  function saveAndHide(el) {
    if (!el || saved.some(function (item) { return item.el === el; })) return;

    saved.push({
      el: el,
      style: el.getAttribute('style')
    });

    el.style.setProperty('display', 'none', 'important');
  }

  function prepareDashboardPrint() {
    if (window.location.pathname !== '/ops/dashboard') return;

    /* Remove the Ops Management heading area */
    document.querySelectorAll('h1, h2, h3').forEach(function (heading) {
      if ((heading.textContent || '').trim() !== 'ICTS Ops Management') return;

      var header =
        heading.closest('.unh-ops-management-header') ||
        heading.parentElement;

      if (header) {
        saveAndHide(header);
      }
    });

    /* Remove the action bar containing Create/View/Print controls */
    var actionTexts = [
      'create project',
      'create task',
      'view projects',
      'view tasks',
      'print'
    ];

    var controls = Array.from(
      document.querySelectorAll('a, button, [role="button"]')
    ).filter(function (el) {
      return actionTexts.includes(
        (el.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase()
      );
    });

    if (controls.length) {
      var common = null;

      for (var i = 0; i < controls.length; i++) {
        var parent = controls[i].parentElement;

        while (parent && parent !== document.body) {
          var matches = actionTexts.filter(function (label) {
            return Array.from(
              parent.querySelectorAll('a, button, [role="button"]')
            ).some(function (el) {
              return (el.textContent || '')
                .replace(/\s+/g, ' ')
                .trim()
                .toLowerCase() === label;
            });
          });

          if (matches.length >= 4) {
            common = parent;
            break;
          }

          parent = parent.parentElement;
        }

        if (common) break;
      }

      if (common) {
        saveAndHide(common);
      } else {
        controls.forEach(function (el) {
          saveAndHide(el);
        });
      }
    }
  }

  function restoreDashboardPrint() {
    saved.forEach(function (item) {
      if (item.style === null) {
        item.el.removeAttribute('style');
      } else {
        item.el.setAttribute('style', item.style);
      }
    });

    saved.length = 0;
  }

  window.addEventListener('beforeprint', prepareDashboardPrint);
  window.addEventListener('afterprint', restoreDashboardPrint);
})();

/* OPS DASHBOARD PRINT HEADER RESTORE */
(function () {
  if (window.__unhOpsDashboardPrintHeaderRestore) return;
  window.__unhOpsDashboardPrintHeaderRestore = true;

  var brand = null;

  function prepareOpsDashboardPrintHeader() {
    if (window.location.pathname !== '/ops/dashboard') return;

    var heading = Array.from(document.querySelectorAll('h1,h2,h3')).find(function (el) {
      return (el.textContent || '').trim() === 'ICTS Ops Management';
    });

    if (!heading) return;

    var header = heading.closest('.unh-ops-management-header');
    if (!header) header = heading.parentElement;
    if (!header) return;

    /* Restore the title section hidden by the earlier print cleanup */
    header.style.setProperty('display', 'block', 'important');
    header.style.setProperty('visibility', 'visible', 'important');
    header.style.setProperty('opacity', '1', 'important');
    header.style.setProperty('height', 'auto', 'important');
    header.style.setProperty('max-height', 'none', 'important');
    header.style.setProperty('overflow', 'visible', 'important');

    /* Add UN-HABITAT above the ICTS Ops Management title */
    var titleArea = heading.parentElement;

    if (titleArea && !titleArea.querySelector('.unh-ops-print-brand')) {
      brand = document.createElement('div');
      brand.className = 'unh-ops-print-brand';
      brand.textContent = 'UN-HABITAT';

      brand.style.setProperty('display', 'block', 'important');
      brand.style.setProperty('font-family', 'Arial, Helvetica, sans-serif', 'important');
      brand.style.setProperty('font-size', '24px', 'important');
      brand.style.setProperty('font-weight', '900', 'important');
      brand.style.setProperty('line-height', '1.1', 'important');
      brand.style.setProperty('color', '#087a4b', 'important');
      brand.style.setProperty('margin', '0 0 8px 0', 'important');
      brand.style.setProperty('padding', '0', 'important');

      titleArea.insertBefore(brand, titleArea.firstChild);
    }

    /* Make sure only the actual dashboard content remains visible */
    document.querySelectorAll('a,button,[role="button"]').forEach(function (el) {
      var text = (el.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();

      if (
        text === 'create project' ||
        text === 'create task' ||
        text === 'view projects' ||
        text === 'view tasks' ||
        text === 'print' ||
        text === 'collapse sidebar'
      ) {
        el.style.setProperty('display', 'none', 'important');
      }
    });
  }

  function cleanupOpsDashboardPrintHeader() {
    if (brand) {
      brand.remove();
      brand = null;
    }
  }

  window.addEventListener('beforeprint', prepareOpsDashboardPrintHeader);
  window.addEventListener('afterprint', cleanupOpsDashboardPrintHeader);

  document.addEventListener('click', function (event) {
    if (window.location.pathname !== '/ops/dashboard') return;

    var control = event.target.closest(
      '.unh-inventory-print-button, .inventory-print-button, .print-button, button, a'
    );

    if (control && /print/i.test((control.textContent || '').trim())) {
      prepareOpsDashboardPrintHeader();
    }
  }, true);
})();

/* OPS PROJECTS PRINT CLEANUP */
(function () {
  if (window.__unhOpsProjectsPrintCleanup) return;
  window.__unhOpsProjectsPrintCleanup = true;

  var brand = null;

  function isProjectsPage() {
    return window.location.pathname === '/ops/projects';
  }

  function hide(el) {
    if (!el) return;
    el.style.setProperty('display', 'none', 'important');
  }

  function prepareProjectsPrint() {
    if (!isProjectsPage()) return;

    /* Remove Back to ICTS Dashboard */
    document.querySelectorAll('a,button,[role="button"]').forEach(function (el) {
      var text = (el.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();

      if (
        text === '← back to icts dashboard' ||
        text === 'back to icts dashboard' ||
        text === 'print'
      ) {
        hide(el);
      }
    });

    /* Remove Edit and Delete controls only */
    var actionControls = [];

    document.querySelectorAll('a,button,[role="button"]').forEach(function (el) {
      var text = (el.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();

      if (text === 'edit' || text === 'delete') {
        actionControls.push(el);
      }
    });

    actionControls.forEach(function (el) {
      var parent = el.parentElement;

      if (parent) {
        var controls = Array.from(
          parent.querySelectorAll('a,button,[role="button"]')
        ).map(function (item) {
          return (item.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
        }).filter(Boolean);

        var onlyActions = controls.length > 0 &&
          controls.every(function (item) {
            return item === 'edit' || item === 'delete';
          });

        if (onlyActions) {
          hide(parent);
          return;
        }
      }

      hide(el);
    });

    /* Keep ICTS Projects title and add UN-HABITAT above it */
    var heading = Array.from(document.querySelectorAll('h1,h2,h3')).find(function (el) {
      return (el.textContent || '').trim() === 'ICTS Projects';
    });

    if (!heading) return;

    var titleArea = heading.parentElement;
    if (!titleArea) return;

    titleArea.style.setProperty('display', 'block', 'important');
    titleArea.style.setProperty('visibility', 'visible', 'important');
    titleArea.style.setProperty('opacity', '1', 'important');

    if (!titleArea.querySelector('.unh-ops-projects-print-brand')) {
      brand = document.createElement('div');
      brand.className = 'unh-ops-projects-print-brand';
      brand.textContent = 'UN-HABITAT';

      brand.style.setProperty('display', 'block', 'important');
      brand.style.setProperty('font-family', 'Arial, Helvetica, sans-serif', 'important');
      brand.style.setProperty('font-size', '24px', 'important');
      brand.style.setProperty('font-weight', '900', 'important');
      brand.style.setProperty('line-height', '1.1', 'important');
      brand.style.setProperty('color', '#087a4b', 'important');
      brand.style.setProperty('margin', '0 0 8px 0', 'important');
      brand.style.setProperty('padding', '0', 'important');

      titleArea.insertBefore(brand, titleArea.firstChild);
    }
  }

  function cleanupProjectsPrint() {
    if (brand) {
      brand.remove();
      brand = null;
    }
  }

  window.addEventListener('beforeprint', prepareProjectsPrint);
  window.addEventListener('afterprint', cleanupProjectsPrint);

  document.addEventListener('click', function (event) {
    if (!isProjectsPage()) return;

    var control = event.target.closest(
      '.unh-inventory-print-button, .inventory-print-button, .print-button, button, a'
    );

    if (control && /print/i.test((control.textContent || '').trim())) {
      prepareProjectsPrint();
    }
  }, true);
})();

/* FINAL OPS PROJECTS PRINT FIX */
(function () {
  if (window.__unhOpsProjectsPrintFinalFix) return;
  window.__unhOpsProjectsPrintFinalFix = true;

  var saved = [];
  var printHeader = null;

  function isProjectsPage() {
    return window.location.pathname === '/ops/projects';
  }

  function save(el) {
    if (!el || saved.some(function (x) { return x.el === el; })) return;
    saved.push({
      el: el,
      style: el.getAttribute('style')
    });
  }

  function hide(el) {
    if (!el) return;
    save(el);
    el.style.setProperty('display', 'none', 'important');
  }

  function prepareProjectsPrint() {
    if (!isProjectsPage()) return;

    /* Remove any old UN-HABITAT print blocks created by earlier scripts */
    document.querySelectorAll('.unh-ops-projects-print-brand, .unh-ops-projects-print-header').forEach(function (el) {
      el.remove();
    });

    /* Remove Back, Print, Edit and Delete controls */
    document.querySelectorAll(
      'a, button, [role="button"], input[type="button"], input[type="submit"]'
    ).forEach(function (el) {
      var text = (
        el.textContent ||
        el.value ||
        ''
      ).replace(/\s+/g, ' ').trim().toLowerCase();

      if (
        text.indexOf('back to icts dashboard') !== -1 ||
        text === 'print' ||
        text.indexOf('print') !== -1 ||
        text === 'edit' ||
        text === 'delete'
      ) {
        hide(el);
      }
    });

    /* Hide the original ICTS Projects heading area.
       A clean copy is placed at the very top instead. */
    var heading = Array.from(document.querySelectorAll('h1,h2,h3')).find(function (el) {
      return (el.textContent || '').replace(/\s+/g, ' ').trim() === 'ICTS Projects';
    });

    if (heading) {
      var description = Array.from(document.querySelectorAll('p')).find(function (el) {
        return (el.textContent || '').replace(/\s+/g, ' ').trim() ===
          'View and manage ICT projects, status, priorities and target completion dates.';
      });

      if (description) {
        hide(heading);
        hide(description);

        var oldWrap = heading.parentElement;
        if (
          oldWrap &&
          oldWrap.textContent &&
          oldWrap.textContent.indexOf('ICTS Projects') !== -1 &&
          oldWrap.textContent.indexOf('View and manage ICT projects') !== -1
        ) {
          hide(oldWrap);
        }
      } else {
        hide(heading);
      }
    }

    /* Create one clean print header at the very top of the main content */
    var target =
      document.querySelector('.unh-main') ||
      document.querySelector('.unh-content-layout') ||
      document.querySelector('main');

    if (!target) return;

    printHeader = document.createElement('div');
    printHeader.className = 'unh-ops-projects-print-header';

    printHeader.innerHTML =
      '<div class="unh-ops-projects-print-brand">UN-HABITAT</div>' +
      '<div class="unh-ops-projects-print-title">ICTS Projects</div>' +
      '<div class="unh-ops-projects-print-description">' +
        'View and manage ICT projects, status, priorities and target completion dates.' +
      '</div>';

    printHeader.style.setProperty('display', 'block', 'important');
    printHeader.style.setProperty('width', '100%', 'important');
    printHeader.style.setProperty('max-width', 'none', 'important');
    printHeader.style.setProperty('box-sizing', 'border-box', 'important');
    printHeader.style.setProperty('margin', '0 0 22px 0', 'important');
    printHeader.style.setProperty('padding', '12px 18px 14px', 'important');
    printHeader.style.setProperty('background', '#ffffff', 'important');
    printHeader.style.setProperty('border-top', '6px solid #087a4b', 'important');
    printHeader.style.setProperty('border-bottom', '4px solid #009edb', 'important');
    printHeader.style.setProperty('font-family', 'Arial, Helvetica, sans-serif', 'important');

    printHeader.querySelector('.unh-ops-projects-print-brand').style.cssText =
      'display:block!important;font-size:24px!important;font-weight:900!important;line-height:1.1!important;color:#087a4b!important;margin:0 0 7px 0!important;';

    printHeader.querySelector('.unh-ops-projects-print-title').style.cssText =
      'display:block!important;font-size:28px!important;font-weight:900!important;line-height:1.15!important;color:#17324d!important;margin:0 0 5px 0!important;';

    printHeader.querySelector('.unh-ops-projects-print-description').style.cssText =
      'display:block!important;font-size:16px!important;font-weight:600!important;line-height:1.4!important;color:#4b5d68!important;margin:0!important;';

    target.insertBefore(printHeader, target.firstChild);
  }

  function restoreProjectsPrint() {
    saved.forEach(function (item) {
      if (item.style === null) {
        item.el.removeAttribute('style');
      } else {
        item.el.setAttribute('style', item.style);
      }
    });

    saved = [];

    document.querySelectorAll(
      '.unh-ops-projects-print-header, .unh-ops-projects-print-brand'
    ).forEach(function (el) {
      el.remove();
    });

    printHeader = null;
  }

  window.addEventListener('beforeprint', prepareProjectsPrint);
  window.addEventListener('afterprint', restoreProjectsPrint);

  document.addEventListener('click', function (event) {
    if (!isProjectsPage()) return;

    var control = event.target.closest(
      '.unh-inventory-print-button, .inventory-print-button, .print-button, a, button'
    );

    if (control && /print/i.test(
      (control.textContent || control.value || '').trim()
    )) {
      prepareProjectsPrint();
    }
  }, true);
})();

/* FINAL OPS ACTIVITY PRINT FIX */
(function () {
  if (window.__unhOpsActivityPrintFinalFix) return;
  window.__unhOpsActivityPrintFinalFix = true;

  var saved = [];
  var printHeader = null;

  function isActivityPage() {
    return /^\/ops\/activity\/\d+\/?$/.test(window.location.pathname);
  }

  function save(el) {
    if (!el || saved.some(function (x) { return x.el === el; })) return;
    saved.push({
      el: el,
      style: el.getAttribute('style')
    });
  }

  function hide(el) {
    if (!el) return;
    save(el);
    el.style.setProperty('display', 'none', 'important');
  }

  function prepareActivityPrint() {
    if (!isActivityPage()) return;

    /* Remove any previous generated header */
    document.querySelectorAll(
      '.unh-ops-activity-print-header, .unh-ops-activity-print-brand'
    ).forEach(function (el) {
      el.remove();
    });

    /* Remove print/back controls */
    document.querySelectorAll(
      'a, button, [role="button"], input[type="button"], input[type="submit"]'
    ).forEach(function (el) {
      var text = (
        el.textContent ||
        el.value ||
        ''
      ).replace(/\s+/g, ' ').trim().toLowerCase();

      if (
        text === 'print' ||
        text.indexOf('print') !== -1 ||
        text.indexOf('back to ') === 0 ||
        text === 'back'
      ) {
        hide(el);
      }
    });

    /* Find the Activity History heading */
    var heading = Array.from(document.querySelectorAll('h1,h2,h3')).find(function (el) {
      return (el.textContent || '').replace(/\s+/g, ' ').trim() ===
        'ICTS Activity History';
    });

    if (!heading) return;

    var description = Array.from(document.querySelectorAll('p')).find(function (el) {
      return (el.textContent || '').replace(/\s+/g, ' ').trim() ===
        'Review the operational activity and change history for ICTS Dashboard Richard test Project.';
    });

    /* Hide original heading area and replace with one clean print header */
    if (description) {
      hide(heading);
      hide(description);

      var oldWrap = heading.parentElement;

      if (
        oldWrap &&
        oldWrap.textContent &&
        oldWrap.textContent.indexOf('ICTS Activity History') !== -1
      ) {
        hide(oldWrap);
      }
    } else {
      hide(heading);
    }

    var target =
      document.querySelector('.unh-main') ||
      document.querySelector('.unh-content-layout') ||
      document.querySelector('main');

    if (!target) return;

    printHeader = document.createElement('div');
    printHeader.className = 'unh-ops-activity-print-header';

    printHeader.innerHTML =
      '<div class="unh-ops-activity-print-brand">UN-HABITAT</div>' +
      '<div class="unh-ops-activity-print-title">ICTS Activity History</div>' +
      '<div class="unh-ops-activity-print-description">' +
        'Review the operational activity and change history for ICTS Dashboard Richard test Project.' +
      '</div>';

    printHeader.style.setProperty('display', 'block', 'important');
    printHeader.style.setProperty('width', '100%', 'important');
    printHeader.style.setProperty('max-width', 'none', 'important');
    printHeader.style.setProperty('box-sizing', 'border-box', 'important');
    printHeader.style.setProperty('margin', '0 0 22px 0', 'important');
    printHeader.style.setProperty('padding', '12px 18px 14px', 'important');
    printHeader.style.setProperty('background', '#ffffff', 'important');
    printHeader.style.setProperty('border-top', '6px solid #087a4b', 'important');
    printHeader.style.setProperty('border-bottom', '4px solid #009edb', 'important');
    printHeader.style.setProperty(
      'font-family',
      'Arial, Helvetica, sans-serif',
      'important'
    );

    printHeader.querySelector(
      '.unh-ops-activity-print-brand'
    ).style.cssText =
      'display:block!important;' +
      'font-size:24px!important;' +
      'font-weight:900!important;' +
      'line-height:1.1!important;' +
      'color:#087a4b!important;' +
      'margin:0 0 7px 0!important;';

    printHeader.querySelector(
      '.unh-ops-activity-print-title'
    ).style.cssText =
      'display:block!important;' +
      'font-size:28px!important;' +
      'font-weight:900!important;' +
      'line-height:1.15!important;' +
      'color:#17324d!important;' +
      'margin:0 0 5px 0!important;';

    printHeader.querySelector(
      '.unh-ops-activity-print-description'
    ).style.cssText =
      'display:block!important;' +
      'font-size:16px!important;' +
      'font-weight:600!important;' +
      'line-height:1.4!important;' +
      'color:#4b5d68!important;' +
      'margin:0!important;';

    target.insertBefore(printHeader, target.firstChild);
  }

  function restoreActivityPrint() {
    saved.forEach(function (item) {
      if (item.style === null) {
        item.el.removeAttribute('style');
      } else {
        item.el.setAttribute('style', item.style);
      }
    });

    saved = [];

    document.querySelectorAll(
      '.unh-ops-activity-print-header, .unh-ops-activity-print-brand'
    ).forEach(function (el) {
      el.remove();
    });

    printHeader = null;
  }

  window.addEventListener('beforeprint', prepareActivityPrint);
  window.addEventListener('afterprint', restoreActivityPrint);

  document.addEventListener('click', function (event) {
    if (!isActivityPage()) return;

    var control = event.target.closest(
      '.unh-inventory-print-button, .inventory-print-button, .print-button, a, button'
    );

    if (
      control &&
      /print/i.test((control.textContent || control.value || '').trim())
    ) {
      prepareActivityPrint();
    }
  }, true);
})();

/* FINAL OPS TASKS PRINT FIX */
(function () {
  if (window.__unhOpsTasksPrintFinalFix) return;
  window.__unhOpsTasksPrintFinalFix = true;

  var saved = [];
  var printHeader = null;

  function isTasksPage() {
    return window.location.pathname === '/ops/tasks';
  }

  function save(el) {
    if (!el || saved.some(function (x) { return x.el === el; })) return;
    saved.push({
      el: el,
      style: el.getAttribute('style')
    });
  }

  function hide(el) {
    if (!el) return;
    save(el);
    el.style.setProperty('display', 'none', 'important');
  }

  function prepareTasksPrint() {
    if (!isTasksPage()) return;

    document.querySelectorAll(
      '.unh-ops-tasks-print-header, .unh-ops-tasks-print-brand'
    ).forEach(function (el) {
      el.remove();
    });

    /* Remove Back, Print, Edit and Delete */
    document.querySelectorAll(
      'a, button, [role="button"], input[type="button"], input[type="submit"]'
    ).forEach(function (el) {
      var text = (
        el.textContent ||
        el.value ||
        ''
      ).replace(/\s+/g, ' ').trim().toLowerCase();

      if (
        text.indexOf('back to icts dashboard') !== -1 ||
        text === 'print' ||
        text.indexOf('print') !== -1 ||
        text === 'edit' ||
        text === 'delete'
      ) {
        hide(el);
      }
    });

    /* Remove the task filters from the printout */
    document.querySelectorAll('form').forEach(function (form) {
      var text = (form.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();

      if (
        text.indexOf('status') !== -1 &&
        text.indexOf('priority') !== -1 &&
        text.indexOf('apply filters') !== -1
      ) {
        hide(form);
      }
    });

    /* Find ICTS Tasks heading */
    var heading = Array.from(document.querySelectorAll('h1,h2,h3')).find(function (el) {
      return (el.textContent || '').replace(/\s+/g, ' ').trim() === 'ICTS Tasks';
    });

    if (!heading) return;

    var description = Array.from(document.querySelectorAll('p')).find(function (el) {
      return (el.textContent || '').replace(/\s+/g, ' ').trim() ===
        'View and manage operational tasks, priorities, assignments and due dates.';
    });

    /* Hide original heading area and replace with one clean print header */
    if (description) {
      hide(heading);
      hide(description);

      var oldWrap = heading.parentElement;

      if (
        oldWrap &&
        oldWrap.textContent &&
        oldWrap.textContent.indexOf('ICTS Tasks') !== -1 &&
        oldWrap.textContent.indexOf('View and manage operational tasks') !== -1
      ) {
        hide(oldWrap);
      }
    } else {
      hide(heading);
    }

    var target =
      document.querySelector('.unh-main') ||
      document.querySelector('.unh-content-layout') ||
      document.querySelector('main');

    if (!target) return;

    printHeader = document.createElement('div');
    printHeader.className = 'unh-ops-tasks-print-header';

    printHeader.innerHTML =
      '<div class="unh-ops-tasks-print-brand">UN-HABITAT</div>' +
      '<div class="unh-ops-tasks-print-title">ICTS Tasks</div>' +
      '<div class="unh-ops-tasks-print-description">' +
        'View and manage operational tasks, priorities, assignments and due dates.' +
      '</div>';

    printHeader.style.setProperty('display', 'block', 'important');
    printHeader.style.setProperty('width', '100%', 'important');
    printHeader.style.setProperty('max-width', 'none', 'important');
    printHeader.style.setProperty('box-sizing', 'border-box', 'important');
    printHeader.style.setProperty('margin', '0 0 22px 0', 'important');
    printHeader.style.setProperty('padding', '12px 18px 14px', 'important');
    printHeader.style.setProperty('background', '#ffffff', 'important');
    printHeader.style.setProperty('border-top', '6px solid #087a4b', 'important');
    printHeader.style.setProperty('border-bottom', '4px solid #009edb', 'important');
    printHeader.style.setProperty(
      'font-family',
      'Arial, Helvetica, sans-serif',
      'important'
    );

    printHeader.querySelector(
      '.unh-ops-tasks-print-brand'
    ).style.cssText =
      'display:block!important;' +
      'font-size:24px!important;' +
      'font-weight:900!important;' +
      'line-height:1.1!important;' +
      'color:#087a4b!important;' +
      'margin:0 0 7px 0!important;';

    printHeader.querySelector(
      '.unh-ops-tasks-print-title'
    ).style.cssText =
      'display:block!important;' +
      'font-size:28px!important;' +
      'font-weight:900!important;' +
      'line-height:1.15!important;' +
      'color:#17324d!important;' +
      'margin:0 0 5px 0!important;';

    printHeader.querySelector(
      '.unh-ops-tasks-print-description'
    ).style.cssText =
      'display:block!important;' +
      'font-size:16px!important;' +
      'font-weight:600!important;' +
      'line-height:1.4!important;' +
      'color:#4b5d68!important;' +
      'margin:0!important;';

    target.insertBefore(printHeader, target.firstChild);
  }

  function restoreTasksPrint() {
    saved.forEach(function (item) {
      if (item.style === null) {
        item.el.removeAttribute('style');
      } else {
        item.el.setAttribute('style', item.style);
      }
    });

    saved = [];

    document.querySelectorAll(
      '.unh-ops-tasks-print-header, .unh-ops-tasks-print-brand'
    ).forEach(function (el) {
      el.remove();
    });

    printHeader = null;
  }

  window.addEventListener('beforeprint', prepareTasksPrint);
  window.addEventListener('afterprint', restoreTasksPrint);

  document.addEventListener('click', function (event) {
    if (!isTasksPage()) return;

    var control = event.target.closest(
      '.unh-inventory-print-button, .inventory-print-button, .print-button, a, button'
    );

    if (
      control &&
      /print/i.test((control.textContent || control.value || '').trim())
    ) {
      prepareTasksPrint();
    }
  }, true);
})();

/* FINAL OPS TASKS PRINT FILTER HIDE */
(function () {
  if (window.__unhOpsTasksPrintFilterFinalFix) return;
  window.__unhOpsTasksPrintFilterFinalFix = true;

  var saved = [];

  function isTasksPage() {
    return window.location.pathname === '/ops/tasks';
  }

  function saveAndHide(el) {
    if (!el || saved.some(function (x) { return x.el === el; })) return;

    saved.push({
      el: el,
      style: el.getAttribute('style')
    });

    el.style.setProperty('display', 'none', 'important');
  }

  function hideTaskFilters() {
    if (!isTasksPage()) return;

    /* Find the actual Apply filters control */
    var applyControls = Array.from(
      document.querySelectorAll(
        'button, input, a, [role="button"]'
      )
    ).filter(function (el) {
      var text = (
        el.textContent ||
        el.value ||
        ''
      ).replace(/\s+/g, ' ').trim().toLowerCase();

      return text === 'apply filters';
    });

    applyControls.forEach(function (control) {
      var wrapper =
        control.closest('form') ||
        control.closest('.views-exposed-form') ||
        control.parentElement;

      if (wrapper) {
        saveAndHide(wrapper);
      } else {
        saveAndHide(control);
      }
    });

    /* Extra protection for the exposed Status / Priority filter area */
    document.querySelectorAll(
      '.views-exposed-form, form'
    ).forEach(function (form) {
      var text = (form.textContent || '')
        .replace(/\s+/g, ' ')
        .trim()
        .toLowerCase();

      if (
        text.indexOf('status') !== -1 &&
        text.indexOf('priority') !== -1 &&
        text.indexOf('apply filters') !== -1
      ) {
        saveAndHide(form);
      }
    });
  }

  function restoreTaskFilters() {
    saved.forEach(function (item) {
      if (item.style === null) {
        item.el.removeAttribute('style');
      } else {
        item.el.setAttribute('style', item.style);
      }
    });

    saved = [];
  }

  window.addEventListener('beforeprint', hideTaskFilters);
  window.addEventListener('afterprint', restoreTaskFilters);

  document.addEventListener('click', function (event) {
    if (!isTasksPage()) return;

    var control = event.target.closest(
      '.unh-inventory-print-button, .inventory-print-button, .print-button, button, a'
    );

    if (
      control &&
      /print/i.test((control.textContent || control.value || '').trim())
    ) {
      hideTaskFilters();
    }
  }, true);
})();

/* OPS DOCUMENTATION VISUAL REFRESH */
(function () {
  if (window.__unhOpsDocumentationVisualRefresh) return;
  window.__unhOpsDocumentationVisualRefresh = true;

  function applyDocumentationVisuals() {
    if (window.location.pathname !== '/ops/documentation') return;

    document.body.classList.add('ops-documentation-visual');

    var heading = Array.from(document.querySelectorAll('h1,h2,h3')).find(function (el) {
      return /ICTS Documentation/i.test((el.textContent || '').trim());
    });

    if (heading) {
      var header =
        heading.closest('.unh-inventory-page-header') ||
        heading.closest('.page-header') ||
        heading.parentElement;

      if (header) {
        header.classList.add('ops-documentation-title-area');
      }
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applyDocumentationVisuals);
  } else {
    applyDocumentationVisuals();
  }
})();

/* OPS DOCUMENTATION: CATEGORY FIELD BY VISIBLE LABEL */
(function () {
  if (window.__unhOpsDocumentationCategoryFinalFix) return;
  window.__unhOpsDocumentationCategoryFinalFix = true;

  function applyCategoryStyle() {
    if (window.location.pathname !== '/ops/documentation') return;

    document.querySelectorAll('.views-field').forEach(function (field) {
      var label =
        field.querySelector('.views-label') ||
        field.querySelector('.field__label');

      if (!label) return;

      if ((label.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase() !== 'category') {
        return;
      }

      field.classList.add('ops-doc-category-field');

      label.classList.add('ops-doc-category-label');

      var value =
        field.querySelector('.field-content') ||
        field.querySelector('.field__item');

      if (value) {
        value.classList.add('ops-doc-category-value');
      } else {
        Array.from(field.children).forEach(function (child) {
          if (child !== label) {
            child.classList.add('ops-doc-category-value');
          }
        });
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applyCategoryStyle);
  } else {
    applyCategoryStyle();
  }
})();

/* OPS DOCUMENTATION: UNIFORM STATUS + LAST UPDATED FIELDS */
(function () {
  if (window.__unhOpsDocumentationMetaFinalFix) return;
  window.__unhOpsDocumentationMetaFinalFix = true;

  function applyDocumentationMetaStyle() {
    if (window.location.pathname !== '/ops/documentation') return;

    document.querySelectorAll('.views-field').forEach(function (field) {
      var label =
        field.querySelector('.views-label') ||
        field.querySelector('.field__label');

      if (!label) return;

      var labelText = (label.textContent || '')
        .replace(/\s+/g, ' ')
        .trim()
        .toLowerCase();

      if (
        labelText !== 'status' &&
        labelText !== 'last updated'
      ) {
        return;
      }

      field.classList.add(
        labelText === 'status'
          ? 'ops-doc-status-field'
          : 'ops-doc-updated-field'
      );

      label.classList.add('ops-doc-meta-label');

      var value =
        field.querySelector('.field-content') ||
        field.querySelector('.field__item');

      if (value) {
        value.classList.add('ops-doc-meta-value');
      } else {
        Array.from(field.children).forEach(function (child) {
          if (child !== label) {
            child.classList.add('ops-doc-meta-value');
          }
        });
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applyDocumentationMetaStyle);
  } else {
    applyDocumentationMetaStyle();
  }
})();

/* OPS DOCUMENTATION: UNIFORM DESCRIPTION FIELD */
(function () {
  if (window.__unhOpsDocumentationDescriptionFinalFix) return;
  window.__unhOpsDocumentationDescriptionFinalFix = true;

  function applyDocumentationDescriptionStyle() {
    if (window.location.pathname !== '/ops/documentation') return;

    document.querySelectorAll('.views-field').forEach(function (field) {
      var label =
        field.querySelector('.views-label') ||
        field.querySelector('.field__label');

      if (!label) return;

      var labelText = (label.textContent || '')
        .replace(/\s+/g, ' ')
        .trim()
        .toLowerCase();

      if (labelText !== 'description') return;

      field.classList.add('ops-doc-description-field');
      label.classList.add('ops-doc-description-label');

      var value =
        field.querySelector('.field-content') ||
        field.querySelector('.field__item');

      if (value) {
        value.classList.add('ops-doc-description-value');
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applyDocumentationDescriptionStyle);
  } else {
    applyDocumentationDescriptionStyle();
  }
})();









/* OPS DOCUMENTATION: SINGLE CLEAN BACK BUTTON */
(function () {
  if (window.__unhOpsDocumentationSingleBackButton) return;
  window.__unhOpsDocumentationSingleBackButton = true;

  function setupDocumentationPage() {
    if (window.location.pathname !== '/ops/documentation') return;

    document.body.classList.add('ops-documentation-final');

    /* Remove every previous Back to Ops Management element */
    Array.from(document.querySelectorAll('a, button, div, span')).forEach(function (el) {
      var text = (el.textContent || '').replace(/\s+/g, ' ').trim();

      if (
        text === 'Back to Ops Management' ||
        text === '← Back to Ops Management'
      ) {
        el.remove();
      }
    });

    /* Find the actual Add Documentation control in the main content */
    var addButton = Array.from(
      document.querySelectorAll('main a, main button')
    ).find(function (el) {
      return (el.textContent || '').replace(/\s+/g, ' ').trim() ===
        '+ Add Documentation';
    });

    if (!addButton) return;

    /* One dedicated row prevents the button from affecting other layout */
    var row = document.createElement('div');
    row.className = 'ops-documentation-final-back-row';

    var button = document.createElement('a');
    button.className = 'ops-documentation-final-back-button';
    button.href = '/';
    button.innerHTML =
      '<span class="ops-documentation-final-back-arrow">←</span>' +
      '<span>Back to Home</span>';

    row.appendChild(button);

    /* Put it directly above Add Documentation */
    addButton.parentNode.insertBefore(row, addButton);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupDocumentationPage);
  } else {
    setupDocumentationPage();
  }
})();

/* OPS INCIDENTS | Back to Ops Management */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.body.classList.contains('page-view-icts-ops-incidents')) {
    return;
  }

  if (document.querySelector('.ops-incidents-back-button')) {
    return;
  }

  var view = document.querySelector('.view-icts-ops-incidents');

  if (!view || !view.parentNode) {
    return;
  }

  var row = document.createElement('div');
  row.className = 'ops-incidents-back-row';

  var link = document.createElement('a');
  link.className = 'ops-incidents-back-button';
  link.href = '/';
  link.innerHTML =
    '<span class="ops-incidents-back-arrow">←</span>' +
    '<span>Back to Home</span>';

  row.appendChild(link);
  view.parentNode.insertBefore(row, view);
});



/* OPS DASHBOARD | Back to Home */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.querySelector('.icts-ops-dashboard')) return;
  if (document.querySelector('.ops-dashboard-back-row')) return;

  var dashboard = document.querySelector('.icts-ops-dashboard');
  if (!dashboard || !dashboard.parentNode) return;

  var row = document.createElement('div');
  row.className = 'ops-dashboard-back-row';

  var link = document.createElement('a');
  link.className = 'ops-dashboard-back-button';
  link.href = '/';
  link.innerHTML =
    '<span class="ops-dashboard-back-arrow">←</span>' +
    '<span>Back to Home</span>';

  row.appendChild(link);
  dashboard.parentNode.insertBefore(row, dashboard);
});

/* =========================================================
   OPS DASHBOARD | RESTORE ACTION BUTTONS AFTER PRINT
   ========================================================= */
(function () {

  function restoreOpsDashboardActions() {
    var dashboard = document.querySelector('.icts-ops-dashboard');
    if (!dashboard) return;

    var actions = dashboard.querySelector('.icts-ops-dashboard-actions');
    if (!actions) return;

    actions.style.display = '';
    actions.style.visibility = '';
    actions.style.opacity = '';

    var buttons = actions.querySelectorAll('.ops-dashboard-action');

    buttons.forEach(function (button) {
      button.style.display = '';
      button.style.visibility = '';
      button.style.opacity = '';
    });
  }

  window.addEventListener('afterprint', function () {
    setTimeout(restoreOpsDashboardActions, 100);
  });

  var printMedia = window.matchMedia('print');

  function handlePrintState(event) {
    if (!event.matches) {
      setTimeout(restoreOpsDashboardActions, 100);
    }
  }

  if (printMedia.addEventListener) {
    printMedia.addEventListener('change', handlePrintState);
  } else if (printMedia.addListener) {
    printMedia.addListener(handlePrintState);
  }

})();

/* Restore KPI View buttons after leaving print preview */
(function () {

  function restoreDashboardLinks() {
    var links = document.querySelectorAll(
      '.icts-ops-dashboard .icts-ops-dashboard-summary .icts-ops-dashboard-card a'
    );

    links.forEach(function (link) {
      link.style.display = 'inline-flex';
      link.style.visibility = 'visible';
      link.style.opacity = '1';
    });
  }

  window.addEventListener('afterprint', function () {
    setTimeout(restoreDashboardLinks, 100);
  });

  var media = window.matchMedia('print');

  function handlePrintChange(event) {
    if (!event.matches) {
      setTimeout(restoreDashboardLinks, 100);
    }
  }

  if (media.addEventListener) {
    media.addEventListener('change', handlePrintChange);
  } else if (media.addListener) {
    media.addListener(handlePrintChange);
  }

})();

/* OPS PROJECTS | Project names are display-only */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.body.classList.contains('page-view-icts-ops-projects')) return;

  document.querySelectorAll(
    '.view-icts-ops-projects .views-field-title h2 a'
  ).forEach(function (link) {
    var text = document.createTextNode(link.textContent);
    link.parentNode.replaceChild(text, link);
  });
});


/* =========================================================
   OPS PROJECTS | VISUAL CARD ORGANIZATION
   ========================================================= */
document.addEventListener('DOMContentLoaded', function () {

  if (!document.body.classList.contains('page-view-icts-ops-projects')) {
    return;
  }

  document.querySelectorAll(
    '.view-icts-ops-projects .icts-ops-project.views-row'
  ).forEach(function (row) {

    if (row.classList.contains('ops-project-card-ready')) {
      return;
    }

    row.classList.add('ops-project-card-ready');

    var title = row.querySelector('.views-field-title');

    /* Keep project names as plain text. */
    var titleLink = row.querySelector('.views-field-title h2 a');

    if (titleLink) {
      titleLink.replaceWith(
        document.createTextNode(titleLink.textContent)
      );
    }

    /* Header */
    var header = document.createElement('div');
    header.className = 'ops-project-card-head';

    if (title) {
      header.appendChild(title);
    }

    row.insertBefore(header, row.firstChild);

    /* Information fields */
    var info = document.createElement('div');
    info.className = 'ops-project-info-grid';

    [
      '.views-field-field-project-status',
      '.views-field-field-project-priority',
      '.views-field-field-project-progress',
      '.views-field-field-project-target-date'
    ].forEach(function (selector) {
      var field = row.querySelector(':scope > ' + selector);

      if (field) {
        info.appendChild(field);
      }
    });

    row.insertBefore(info, header.nextSibling);

    /* Actions */
    var actionFields = row.querySelectorAll(
      ':scope > .views-field-edit-node, ' +
      ':scope > .views-field-delete-node, ' +
      ':scope > .views-field-nothing'
    );

    if (actionFields.length) {
      var actions = document.createElement('div');
      actions.className = 'ops-project-actions';

      actionFields.forEach(function (field) {
        actions.appendChild(field);
      });

      row.appendChild(actions);
    }

    /* Status class */
    var status = row.querySelector(
      '.views-field-field-project-status .field-content'
    );

    if (status) {
      row.classList.add(
        'project-status-' +
        status.textContent.trim()
          .toLowerCase()
          .replace(/[^a-z0-9]+/g, '-')
          .replace(/^-|-$/g, '')
      );
    }

    /* Priority class */
    var priority = row.querySelector(
      '.views-field-field-project-priority .field-content'
    );

    if (priority) {
      row.classList.add(
        'project-priority-' +
        priority.textContent.trim()
          .toLowerCase()
          .replace(/[^a-z0-9]+/g, '-')
          .replace(/^-|-$/g, '')
      );
    }

    /* Progress graphic */
    var progress = row.querySelector(
      '.views-field-field-project-progress .field-content'
    );

    if (progress) {

      var match = progress.textContent.trim().match(/\d+(?:\.\d+)?/);

      if (match) {

        var percent = Math.max(
          0,
          Math.min(100, Number(match[0]))
        );

        progress.innerHTML =
          '<span class="ops-project-progress-number">' +
          percent +
          '%</span>' +
          '<span class="ops-project-progress-track">' +
          '<span class="ops-project-progress-fill" style="width:' +
          percent +
          '%"></span>' +
          '</span>';
      }
    }

  });

});

/* =========================================================
   ICTS ACTIVITY HISTORY | VISUAL ENHANCEMENT
   ========================================================= */
document.addEventListener('DOMContentLoaded', function () {
  if (!window.location.pathname.match(/^\/ops\/activity\/\d+\/?$/)) {
    return;
  }

  document.body.classList.add('ops-activity-page');

  const main = document.querySelector('main');
  if (!main) {
    return;
  }

  /* Sharpen the activity subject/title */
  main.querySelectorAll('h1, h2, h3').forEach(function (heading) {
    const text = heading.textContent.trim();
    if (
      text === 'Meeting Room Technology Assessment' ||
      text === 'ICTS Activity History'
    ) {
      heading.classList.add('ops-activity-title-sharp');
    }
  });

  /* Turn each change record into a visual activity card */
  const changeHeadings = Array.from(main.querySelectorAll('h2, h3, strong, span, div'))
    .filter(function (el) {
      const t = el.textContent.trim();
      return t === 'Status changed' || t === 'Description changed';
    });

  changeHeadings.forEach(function (heading) {
    if (heading.dataset.activityStyled === '1') {
      return;
    }

    let card = heading.parentElement;
    let steps = 0;

    while (card && steps < 6) {
      const text = card.textContent || '';
      if (
        text.includes('Changed by:') &&
        text.includes('From:') &&
        text.includes('To:')
      ) {
        break;
      }
      card = card.parentElement;
      steps++;
    }

    if (!card) {
      return;
    }

    heading.dataset.activityStyled = '1';
    card.classList.add('ops-activity-entry');

    if (heading.textContent.trim() === 'Status changed') {
      card.classList.add('ops-activity-status');
    } else {
      card.classList.add('ops-activity-description');
    }
  });

  /* Style the visible date/time blocks */
  main.querySelectorAll('div, p, span').forEach(function (el) {
    const t = el.textContent.trim();

    if (
      t.startsWith('Day:') &&
      t.includes('Date:') &&
      t.includes('Time:')
    ) {
      el.classList.add('ops-activity-datetime');
    }
  });

  /* Find and style the back-to-tasks link */
  main.querySelectorAll('a').forEach(function (link) {
    if (link.textContent.trim() === '← Back to Tasks') {
      link.classList.add('ops-activity-back');
    }
  });
});

/* =========================================================
   ICTS ACTIVITY HISTORY | VISUAL ENHANCEMENT
   ========================================================= */
document.addEventListener('DOMContentLoaded', function () {
  if (!window.location.pathname.match(/^\/ops\/activity\/\d+\/?$/)) {
    return;
  }

  document.body.classList.add('ops-activity-page');

  const main = document.querySelector('main');
  if (!main) {
    return;
  }

  /* Sharpen the activity subject/title */
  main.querySelectorAll('h1, h2, h3').forEach(function (heading) {
    const text = heading.textContent.trim();
    if (
      text === 'Meeting Room Technology Assessment' ||
      text === 'ICTS Activity History'
    ) {
      heading.classList.add('ops-activity-title-sharp');
    }
  });

  /* Turn each change record into a visual activity card */
  const changeHeadings = Array.from(main.querySelectorAll('h2, h3, strong, span, div'))
    .filter(function (el) {
      const t = el.textContent.trim();
      return t === 'Status changed' || t === 'Description changed';
    });

  changeHeadings.forEach(function (heading) {
    if (heading.dataset.activityStyled === '1') {
      return;
    }

    let card = heading.parentElement;
    let steps = 0;

    while (card && steps < 6) {
      const text = card.textContent || '';
      if (
        text.includes('Changed by:') &&
        text.includes('From:') &&
        text.includes('To:')
      ) {
        break;
      }
      card = card.parentElement;
      steps++;
    }

    if (!card) {
      return;
    }

    heading.dataset.activityStyled = '1';
    card.classList.add('ops-activity-entry');

    if (heading.textContent.trim() === 'Status changed') {
      card.classList.add('ops-activity-status');
    } else {
      card.classList.add('ops-activity-description');
    }
  });

  /* Style the visible date/time blocks */
  main.querySelectorAll('div, p, span').forEach(function (el) {
    const t = el.textContent.trim();

    if (
      t.startsWith('Day:') &&
      t.includes('Date:') &&
      t.includes('Time:')
    ) {
      el.classList.add('ops-activity-datetime');
    }
  });

  /* Find and style the back-to-tasks link */
  main.querySelectorAll('a').forEach(function (link) {
    if (link.textContent.trim() === '← Back to Tasks') {
      link.classList.add('ops-activity-back');
    }
  });
});

document.addEventListener('DOMContentLoaded', function () {
  const header = document.querySelector('.unh-inventory-page-header.ops-activity-page-header');

  if (!header) {
    return;
  }

  const backButton = document.querySelector('.ops-activity-back');
  const activityTitle = document.querySelector('.ops-activity-title');

  if (backButton && activityTitle) {
    activityTitle.parentNode.insertBefore(backButton, activityTitle);
  }

  header.style.setProperty(
    'background',
    'linear-gradient(120deg, #697276 0%, #4f7484 50%, #4b8975 100%)',
    'important'
  );
  header.style.setProperty('background-color', '#4f7484', 'important');
  header.style.setProperty('color', '#ffffff', 'important');
  header.style.setProperty('border', '1px solid #5d6569', 'important');

  header.querySelectorAll('h1, h2, p, span').forEach(function (el) {
    el.style.setProperty('background', 'transparent', 'important');
    el.style.setProperty('color', '#ffffff', 'important');
  });

  const kicker = header.querySelector('.unh-inventory-page-kicker');
  if (kicker) {
    kicker.style.setProperty('color', '#dfe4e6', 'important');
  }

  const printButton = header.querySelector('.ops-activity-print');
  if (printButton) {
    printButton.style.setProperty('background', '#111111', 'important');
    printButton.style.setProperty('background-image', 'none', 'important');
    printButton.style.setProperty('color', '#ffffff', 'important');
    printButton.style.setProperty('border-color', '#111111', 'important');
  }
});


/* =========================================================
   ICTS CHANGES | BACK HOME + PRINT ACTIONS
   ========================================================= */
document.addEventListener('DOMContentLoaded', function () {
  if (window.location.pathname !== '/ops/changes') {
    return;
  }

  const view = document.querySelector('.view-icts-ops-changes');
  if (!view || document.querySelector('.ops-changes-actions')) {
    return;
  }

  const actions = document.createElement('div');
  actions.className = 'ops-changes-actions';

  const back = document.createElement('a');
  back.href = '/';
  back.className = 'ops-changes-back';
  back.textContent = '← Back to Home';

  const print = document.createElement('a');
  print.href = '#';
  print.className = 'ops-changes-print';
  print.innerHTML = '<span class="unh-print-icon">🖨</span><span>Print</span>';
  print.addEventListener('click', function (event) {
    event.preventDefault();

    if (actions.parentNode) {
      actions.remove();
    }

    window.print();
  });

  window.addEventListener('afterprint', function () {
    if (!document.querySelector('.ops-changes-actions')) {
      view.parentNode.insertBefore(actions, view);
    }
  });

  actions.appendChild(back);
  actions.appendChild(print);

  view.parentNode.insertBefore(actions, view);
});

/* =========================================================
   ICTS KNOWLEDGE & TECHNOLOGY HUB
   ========================================================= */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.body.classList.contains('page-view-icts-knowledge-base')) {
    return;
  }

  const view = document.querySelector('.view-icts-knowledge-base');
  if (!view || document.querySelector('.icts-kb-hub')) {
    return;
  }

  const categories = [
    ['AI & Automation', 'AI assistants, APIs, automation and intelligent workflows.', 'AI', 'https://developers.openai.com/'],
    ['Coding & Development', 'Programming, APIs, Git, frameworks and software engineering.', 'DEV', 'https://developer.mozilla.org/en-US/docs/Web'],
    ['Machine Learning & Data', 'ML concepts, model development, data and analytics.', 'ML', 'https://developers.google.com/machine-learning/crash-course'],
    ['Networking', 'Protocols, connectivity, DNS, HTTP and network fundamentals.', 'NET', 'https://www.cisco.com/site/us/en/learn/training-certifications/training/netacad/index.html'],
    ['Network Management', 'Network monitoring, switching, routing, troubleshooting and infrastructure management.', 'NMS', 'https://www.cisco.com/c/en/us/products/cloud-systems-management/index.html'],
    ['CCNA', 'Cisco networking fundamentals, IP connectivity, routing, switching and network access.', 'CCNA', 'https://www.cisco.com/site/us/en/learn/training-certifications/certifications/enterprise/ccna/index.html'],
    ['CCNP', 'Advanced enterprise networking, routing, switching, wireless and network services.', 'CCNP', 'https://www.cisco.com/site/us/en/learn/training-certifications/certifications/enterprise/ccnp-enterprise/index.html'],
    ['Servers & Linux', 'Linux administration, server configuration, virtualization and server operations.', 'SRV', 'https://ubuntu.com/server/docs/'],
    ['Cloud & DevOps', 'Cloud platforms, CI/CD, containers and infrastructure.', 'OPS', 'https://docs.aws.amazon.com/'],
    ['Cybersecurity', 'Security awareness, secure development and risk management.', 'SEC', 'https://www.nist.gov/cyberframework'],
    ['Hardware & End User', 'Devices, operating systems, drivers and endpoint support.', 'HW', 'https://www.intel.com/content/www/us/en/download-center/home.html'],
    ['Enterprise & Microsoft', 'Windows, Microsoft 365, Azure, identity and administration.', 'MS', 'https://learn.microsoft.com/training'],
    ['Web & Drupal', 'Web standards, Drupal, APIs, accessibility and site building.', 'WEB', 'https://www.drupal.org/documentation'],
    ['Databases & Platforms', 'SQL, application platforms, storage and data services.', 'DB', 'https://www.postgresql.org/docs/'],
    ['Collaboration & Productivity', 'Digital workplace tools, documentation and productivity.', 'COL', 'https://learn.microsoft.com/training'],
  ];

  const resources = [
    [
      'OpenAI Developers',
      'AI APIs, models, agents and application development.',
      'AI',
      'https://' + 'developers.openai.com/'
    ],
    [
      'MDN Web Docs',
      'HTML, CSS, JavaScript, Web APIs and modern web development.',
      'WEB',
      'https://' + 'developer.mozilla.org/en-US/docs/Web'
    ],
    [
      'GitHub Docs',
      'Git, repositories, collaboration, automation and developer workflows.',
      'GIT',
      'https://' + 'docs.github.com/en/get-started'
    ],
    [
      'Python Documentation',
      'Python language, standard library, tutorials and developer reference.',
      'PY',
      'https://' + 'docs.python.org/3/'
    ],
    [
      'Google Machine Learning',
      'Machine learning concepts, courses and practical learning material.',
      'ML',
      'https://' + 'developers.google.com/machine-learning/crash-course'
    ],
    [
      'TensorFlow Learn',
      'Machine learning and deep learning resources from TensorFlow.',
      'TF',
      'https://' + 'www.tensorflow.org/learn'
    ],
    [
      'NIST Cybersecurity',
      'Cybersecurity Framework and practical risk-management resources.',
      'NIST',
      'https://' + 'www.nist.gov/cyberframework'
    ],
    [
      'OWASP Top 10',
      'Web application security risks and secure development awareness.',
      'OWASP',
      'https://' + 'owasp.org/projects/top-ten'
    ],
    [
      'Ubuntu Server',
      'Linux server administration, security and networking documentation.',
      'LINUX',
      'https://' + 'ubuntu.com/server/docs'
    ],
    [
      'Docker Docs',
      'Containers, images, application packaging and deployment.',
      'DOCKER',
      'https://' + 'docs.docker.com/get-started/'
    ],
    [
      'Kubernetes Docs',
      'Container orchestration, workloads, clusters and operations.',
      'K8S',
      'https://' + 'kubernetes.io/docs/home/'
    ],
    [
      'AWS Documentation',
      'Cloud services, architecture, operations and platform reference.',
      'AWS',
      'https://' + 'aws.amazon.com/documentation-overview/'
    ],
    [
      'Microsoft Learn',
      'Windows, Azure, Microsoft 365, security and enterprise technology.',
      'MS',
      'https://' + 'learn.microsoft.com/training'
    ],
    [
      'Drupal Documentation',
      'Drupal administration, development, APIs, security and site building.',
      'DRUPAL',
      'https://' + 'www.drupal.org/documentation'
    ],
  ];

  const hub = document.createElement('section');
  hub.className = 'icts-kb-hub';

  hub.innerHTML = `
    <a class="icts-kb-back-home" href="/">← Back to Home</a>
    <div class="icts-kb-hero">
      <div class="icts-kb-hero-content">
        <span class="icts-kb-kicker">UN-HABITAT ICTS</span>
        <h1>Knowledge &amp; Technology Hub</h1>
        <p>
          A central ICT knowledge space for internal guidance, technical
          documentation, learning resources and trusted technology references.
        </p>
        <div class="icts-kb-hero-pills">
          <span>Knowledge</span>
          <span>Technology</span>
          <span>Security</span>
          <span>Innovation</span>
        </div>
      </div>
      <div class="icts-kb-hero-graphic" aria-hidden="true">
        <div class="icts-kb-orbit orbit-one"></div>
        <div class="icts-kb-orbit orbit-two"></div>
        <div class="icts-kb-orbit orbit-three"></div>
        <div class="icts-kb-core">ICTS</div>
      </div>
    </div>

    <div class="icts-kb-section">
      <div class="icts-kb-section-heading">
        <div>
          <span class="icts-kb-section-kicker">EXPLORE</span>
          <h2>Technology Areas</h2>
        </div>
        <p>Navigate the major technical areas covered by ICTS.</p>
      </div>

      <div class="icts-kb-category-grid">
        ${categories.map((item, index) => `
          <a class="icts-kb-category-card category-${index + 1}"
             href="${item[3]}"
             target="_blank"
             rel="noopener noreferrer"
             data-kb-category="${item[0]}">
            <span class="icts-kb-category-icon">${item[2]}</span>
            <span class="icts-kb-category-name">${item[0]}</span>
            <span class="icts-kb-category-description">${item[1]}</span>
            <span class="icts-kb-category-arrow">Open resource ↗</span>
          </a>
        `).join('')}
      </div>
    </div>

    <div class="icts-kb-online" id="icts-kb-online">
      <div class="icts-kb-section-heading">
        <div>
          <span class="icts-kb-section-kicker">TRUSTED RESOURCES</span>
          <h2>Online Technology Resources</h2>
        </div>
        <p>Curated links to official documentation and learning resources.</p>
      </div>

      <div class="icts-kb-resource-grid">
        ${resources.map((item, index) => `
          <a class="icts-kb-resource-card resource-${index + 1}"
             href="${item[3]}"
             target="_blank"
             rel="noopener noreferrer">
            <span class="icts-kb-resource-icon">${item[2]}</span>
            <span class="icts-kb-resource-name">${item[0]}</span>
            <span class="icts-kb-resource-description">${item[1]}</span>
            <span class="icts-kb-resource-link">Open resource ↗</span>
          </a>
        `).join('')}
      </div>
    </div>
  `;

  view.parentNode.insertBefore(hub, view);

  const internalHeading = document.createElement('div');
  internalHeading.className = 'icts-kb-internal-heading';
  internalHeading.id = 'icts-kb-internal';
  internalHeading.innerHTML = `
    <span class="icts-kb-section-kicker">ICTS INTERNAL</span>
    <h2>Internal Knowledge Base</h2>
    <p>Search ICTS documents, procedures, guides and internal references.</p>
  `;

  view.parentNode.insertBefore(internalHeading, view);
  view.classList.add('icts-kb-internal-view');

});

/* =========================================================
   ICTS KNOWLEDGE HUB | CONTENT-SPECIFIC GRAPHICS
   ========================================================= */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.body.classList.contains('page-view-icts-knowledge-base')) {
    return;
  }

  const visuals = {
    'AI & Automation': `
      <svg viewBox="0 0 120 90" aria-hidden="true">
        <circle cx="60" cy="45" r="24" fill="none" stroke="currentColor" stroke-width="4"/>
        <path d="M45 46c0-9 7-16 15-16s15 7 15 16-7 16-15 16-15-7-15-16Z"
              fill="none" stroke="currentColor" stroke-width="3"/>
        <circle cx="38" cy="30" r="5" fill="currentColor"/>
        <circle cx="82" cy="30" r="5" fill="currentColor"/>
        <circle cx="38" cy="60" r="5" fill="currentColor"/>
        <circle cx="82" cy="60" r="5" fill="currentColor"/>
        <path d="M42 33 52 39M78 33 68 39M42 57 52 51M78 57 68 51"
              stroke="currentColor" stroke-width="3"/>
      </svg>`,

    'Coding & Development': `
      <svg viewBox="0 0 120 90" aria-hidden="true">
        <rect x="17" y="15" width="86" height="58" rx="7"
              fill="none" stroke="currentColor" stroke-width="4"/>
        <path d="m38 39-12 8 12 8M82 39l12 8-12 8M70 32 51 59"
              fill="none" stroke="currentColor" stroke-width="4"
              stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="27" cy="25" r="3" fill="currentColor"/>
        <circle cx="37" cy="25" r="3" fill="currentColor"/>
        <circle cx="47" cy="25" r="3" fill="currentColor"/>
      </svg>`,

    'Machine Learning & Data': `
      <svg viewBox="0 0 120 90" aria-hidden="true">
        <path d="M20 70 43 51 59 58 80 30 100 37"
              fill="none" stroke="currentColor" stroke-width="4"
              stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="20" cy="70" r="6" fill="currentColor"/>
        <circle cx="43" cy="51" r="6" fill="currentColor"/>
        <circle cx="59" cy="58" r="6" fill="currentColor"/>
        <circle cx="80" cy="30" r="6" fill="currentColor"/>
        <circle cx="100" cy="37" r="6" fill="currentColor"/>
        <path d="M23 18h74M23 18v52"
              stroke="currentColor" stroke-width="3" opacity=".45"/>
      </svg>`,

    'Cybersecurity': `
      <svg viewBox="0 0 120 90" aria-hidden="true">
        <path d="M60 12 94 25v22c0 18-14 26-34 32C40 73 26 65 26 47V25Z"
              fill="none" stroke="currentColor" stroke-width="4"
              stroke-linejoin="round"/>
        <rect x="45" y="39" width="30" height="22" rx="4"
              fill="none" stroke="currentColor" stroke-width="4"/>
        <path d="M51 39v-6c0-6 4-11 9-11s9 5 9 11v6"
              fill="none" stroke="currentColor" stroke-width="4"/>
        <circle cx="60" cy="49" r="3" fill="currentColor"/>
      </svg>`,

    'Networking': `
      <svg viewBox="0 0 120 90" aria-hidden="true">
        <circle cx="60" cy="20" r="10" fill="none" stroke="currentColor" stroke-width="4"/>
        <circle cx="28" cy="65" r="10" fill="none" stroke="currentColor" stroke-width="4"/>
        <circle cx="92" cy="65" r="10" fill="none" stroke="currentColor" stroke-width="4"/>
        <path d="M54 28 34 57M66 28l20 29M38 65h44"
              stroke="currentColor" stroke-width="4"/>
        <rect x="49" y="42" width="22" height="14" rx="3"
              fill="currentColor"/>
        <circle cx="55" cy="49" r="2" fill="#fff"/>
        <circle cx="61" cy="49" r="2" fill="#fff"/>
        <circle cx="67" cy="49" r="2" fill="#fff"/>
      </svg>`,

    'Servers & Linux': `
      <svg viewBox="0 0 120 90" aria-hidden="true">
        <rect x="22" y="15" width="76" height="18" rx="4"
              fill="none" stroke="currentColor" stroke-width="4"/>
        <rect x="22" y="36" width="76" height="18" rx="4"
              fill="none" stroke="currentColor" stroke-width="4"/>
        <rect x="22" y="57" width="76" height="18" rx="4"
              fill="none" stroke="currentColor" stroke-width="4"/>
        <circle cx="34" cy="24" r="3" fill="currentColor"/>
        <circle cx="34" cy="45" r="3" fill="currentColor"/>
        <circle cx="34" cy="66" r="3" fill="currentColor"/>
        <path d="M49 25h35M49 46h35M49 67h35"
              stroke="currentColor" stroke-width="3"/>
      </svg>`,

    'Cloud & DevOps': `
      <svg viewBox="0 0 120 90" aria-hidden="true">
        <path d="M38 59h48c10 0 18-7 18-16s-8-16-18-16c-2 0-4 0-6 1C77 18 69 12 58 12c-13 0-23 9-26 21C21 34 14 43 14 52c0 4 2 7 4 7Z"
              fill="none" stroke="currentColor" stroke-width="4"/>
        <circle cx="39" cy="71" r="5" fill="currentColor"/>
        <circle cx="60" cy="71" r="5" fill="currentColor"/>
        <circle cx="81" cy="71" r="5" fill="currentColor"/>
        <path d="M44 71h11M65 71h11"
              stroke="currentColor" stroke-width="3"/>
      </svg>`,

    'Hardware & End User': `
      <svg viewBox="0 0 120 90" aria-hidden="true">
        <rect x="20" y="14" width="80" height="50" rx="6"
              fill="none" stroke="currentColor" stroke-width="4"/>
        <path d="M10 75h100"
              stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
        <rect x="48" y="25" width="24" height="24" rx="3"
              fill="none" stroke="currentColor" stroke-width="3"/>
        <path d="M42 31h6M42 39h6M72 31h6M72 39h6M54 19v6M66 19v6M54 49v6M66 49v6"
              stroke="currentColor" stroke-width="3"/>
      </svg>`,

    'Enterprise & Microsoft': `
      <svg viewBox="0 0 120 90" aria-hidden="true">
        <rect x="20" y="16" width="34" height="28" fill="none" stroke="currentColor" stroke-width="4"/>
        <rect x="58" y="16" width="42" height="28" fill="none" stroke="currentColor" stroke-width="4"/>
        <rect x="20" y="48" width="34" height="28" fill="none" stroke="currentColor" stroke-width="4"/>
        <rect x="58" y="48" width="42" height="28" fill="none" stroke="currentColor" stroke-width="4"/>
        <path d="M60 20v20M82 20v20M60 52v20M82 52v20"
              stroke="currentColor" stroke-width="3" opacity=".45"/>
      </svg>`,

    'Web & Drupal': `
      <svg viewBox="0 0 120 90" aria-hidden="true">
        <rect x="15" y="14" width="90" height="60" rx="7"
              fill="none" stroke="currentColor" stroke-width="4"/>
        <path d="M15 30h90"
              stroke="currentColor" stroke-width="4"/>
        <circle cx="27" cy="22" r="3" fill="currentColor"/>
        <circle cx="37" cy="22" r="3" fill="currentColor"/>
        <circle cx="47" cy="22" r="3" fill="currentColor"/>
        <path d="M39 48h17M39 56h30"
              stroke="currentColor" stroke-width="4"
              stroke-linecap="round"/>
        <circle cx="84" cy="52" r="10" fill="none" stroke="currentColor" stroke-width="4"/>
        <path d="M84 42c-4 4-4 15 0 20M74 52h20"
              stroke="currentColor" stroke-width="2"/>
      </svg>`,

    'Databases & Platforms': `
      <svg viewBox="0 0 120 90" aria-hidden="true">
        <ellipse cx="60" cy="22" rx="31" ry="10"
                 fill="none" stroke="currentColor" stroke-width="4"/>
        <path d="M29 22v20c0 6 14 10 31 10s31-4 31-10V22"
              fill="none" stroke="currentColor" stroke-width="4"/>
        <path d="M29 42v20c0 6 14 10 31 10s31-4 31-10V42"
              fill="none" stroke="currentColor" stroke-width="4"/>
        <path d="M40 30h40"
              stroke="currentColor" stroke-width="3"/>
      </svg>`,

    'Collaboration & Productivity': `
      <svg viewBox="0 0 120 90" aria-hidden="true">
        <circle cx="39" cy="31" r="10" fill="none" stroke="currentColor" stroke-width="4"/>
        <circle cx="81" cy="31" r="10" fill="none" stroke="currentColor" stroke-width="4"/>
        <path d="M22 67c2-14 10-22 17-22s15 8 17 22M64 67c2-14 10-22 17-22s15 8 17 22"
              fill="none" stroke="currentColor" stroke-width="4"
              stroke-linecap="round"/>
        <rect x="39" y="12" width="42" height="17" rx="8"
              fill="currentColor" opacity=".18"/>
        <path d="M52 20h16"
              stroke="currentColor" stroke-width="3"
              stroke-linecap="round"/>
      </svg>`
  };

  document.querySelectorAll('.icts-kb-category-card').forEach(function (card) {
    const name = card.getAttribute('data-kb-category');
    const visualBox = card.querySelector('.icts-kb-category-icon');

    if (!visualBox || !visuals[name]) {
      return;
    }

    visualBox.innerHTML = visuals[name];
    visualBox.classList.add(
      'icts-kb-category-visual',
      'kb-visual-' +
      name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')
    );
  });
});

/* =========================================================
   ICTS KNOWLEDGE HUB | LIVE RESOURCE VISUAL STATUS
   ========================================================= */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.body.classList.contains('page-view-icts-knowledge-base')) {
    return;
  }

  document.querySelectorAll('.icts-kb-category-card').forEach(function (card) {
    if (!card.querySelector('.icts-kb-live-badge')) {
      const badge = document.createElement('span');
      badge.className = 'icts-kb-live-badge';
      badge.innerHTML = '<span class="icts-kb-live-dot"></span> LIVE WEB RESOURCE';
      card.appendChild(badge);
    }
  });

  document.querySelectorAll('.icts-kb-resource-card').forEach(function (card) {
    if (!card.querySelector('.icts-kb-resource-live')) {
      const badge = document.createElement('span');
      badge.className = 'icts-kb-resource-live';
      badge.innerHTML = '<span class="icts-kb-live-dot"></span> ONLINE';
      card.appendChild(badge);
    }
  });
});

/* =========================================================
   ICTS KNOWLEDGE HUB | ANIMATED LEARNING ROADMAP
   ========================================================= */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.body.classList.contains('page-view-icts-knowledge-base')) {
    return;
  }

  const section = document.querySelector('.icts-kb-section');
  if (!section || document.querySelector('.icts-kb-roadmap')) {
    return;
  }

  const roadmap = document.createElement('div');
  roadmap.className = 'icts-kb-roadmap';

  roadmap.innerHTML = `
    <div class="icts-kb-roadmap-header">
      <div>
        <span>ICTS LEARNING PATH</span>
        <h3>Explore. Build. Secure. Operate.</h3>
        <p>Follow technology from fundamentals to real-world ICT operations.</p>
      </div>
      <div class="icts-kb-roadmap-live">
        <i></i> LIVE LEARNING MAP
      </div>
    </div>

    <div class="icts-kb-roadmap-track">

      <div class="icts-kb-roadmap-line">
        <span class="icts-kb-roadmap-signal"></span>
      </div>

      <a class="icts-kb-roadmap-node node-ai"
         href="https://developers.openai.com/"
         target="_blank"
         rel="noopener noreferrer">
        <strong>AI</strong>
        <small>Intelligence</small>
      </a>

      <a class="icts-kb-roadmap-node node-dev"
         href="https://github.com/skills"
         target="_blank"
         rel="noopener noreferrer">
        <strong>DEV</strong>
        <small>Build</small>
      </a>

      <a class="icts-kb-roadmap-node node-data"
         href="https://developers.google.com/machine-learning"
         target="_blank"
         rel="noopener noreferrer">
        <strong>DATA</strong>
        <small>Learn</small>
      </a>

      <a class="icts-kb-roadmap-node node-sec"
         href="https://owasp.org/"
         target="_blank"
         rel="noopener noreferrer">
        <strong>SEC</strong>
        <small>Protect</small>
      </a>

      <a class="icts-kb-roadmap-node node-cloud"
         href="https://aws.amazon.com/training/"
         target="_blank"
         rel="noopener noreferrer">
        <strong>CLOUD</strong>
        <small>Operate</small>
      </a>

      <a class="icts-kb-roadmap-node node-web"
         href="https://developer.mozilla.org/"
         target="_blank"
         rel="noopener noreferrer">
        <strong>WEB</strong>
        <small>Deliver</small>
      </a>

    </div>

    <div class="icts-kb-roadmap-stages">
      <span>FOUNDATIONS</span>
      <span>DEVELOPMENT</span>
      <span>SECURITY</span>
      <span>OPERATIONS</span>
    </div>
  `;

  section.insertBefore(roadmap, section.querySelector('.icts-kb-category-grid'));
});

/* =========================================================
   ICTS LEARNING ROADMAP | LIVE NETWORK ANIMATION
   ========================================================= */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.body.classList.contains('page-view-icts-knowledge-base')) {
    return;
  }

  const roadmap = document.querySelector('.icts-kb-roadmap');
  const track = roadmap?.querySelector('.icts-kb-roadmap-track');
  const line = roadmap?.querySelector('.icts-kb-roadmap-line');
  const nodes = roadmap
    ? Array.from(roadmap.querySelectorAll('.icts-kb-roadmap-node'))
    : [];

  if (!roadmap || !track || !line || nodes.length < 2) {
    return;
  }

  if (roadmap.dataset.liveAnimation === '1') {
    return;
  }

  roadmap.dataset.liveAnimation = '1';

  /* Remove the old CSS signal animation */
  const oldSignal = line.querySelector('.icts-kb-roadmap-signal');

  if (oldSignal) {
    oldSignal.style.animation = 'none';
    oldSignal.style.left = '0%';
  }

  /* Main travelling signal */
  const signal = oldSignal || document.createElement('span');

  signal.className = 'icts-kb-roadmap-signal icts-kb-live-signal';

  if (!signal.parentNode) {
    line.appendChild(signal);
  }

  /* Small travelling particles */
  const particles = [];

  for (let i = 0; i < 5; i++) {
    const particle = document.createElement('span');

    particle.className = 'icts-kb-data-particle';
    particle.dataset.offset = String(i * 0.17);

    line.appendChild(particle);
    particles.push(particle);
  }

  let start = null;
  const duration = 8500;

  function animate(timestamp) {
    if (!start) {
      start = timestamp;
    }

    const elapsed = timestamp - start;
    const rawProgress = (elapsed % duration) / duration;

    /* Smooth forward movement */
    const progress =
      rawProgress < 0.5
        ? 2 * rawProgress * rawProgress
        : 1 - Math.pow(-2 * rawProgress + 2, 2) / 2;

    /* Main signal position */
    signal.style.left = (progress * 92 + 4) + '%';

    /* Determine which technology node is active */
    const activeIndex = Math.min(
      nodes.length - 1,
      Math.floor(progress * nodes.length)
    );

    nodes.forEach(function (node, index) {
      const distance = Math.abs(index - activeIndex);

      node.classList.toggle(
        'roadmap-node-live',
        distance === 0
      );

      node.classList.toggle(
        'roadmap-node-near',
        distance === 1
      );
    });

    /* Travelling data particles */
    particles.forEach(function (particle, index) {
      let particleProgress =
        progress - Number(particle.dataset.offset);

      if (particleProgress < 0) {
        particleProgress += 1;
      }

      particle.style.left =
        (particleProgress * 92 + 4) + '%';

      const size =
        index === 0 ? 11 :
        index === 1 ? 8 :
        index === 2 ? 7 :
        index === 3 ? 6 : 5;

      particle.style.width = size + 'px';
      particle.style.height = size + 'px';
    });

    requestAnimationFrame(animate);
  }

  requestAnimationFrame(animate);
});

/* =========================================================
   ICTS KNOWLEDGE BASE | PROCESSOR SIGNAL WAVES
   ========================================================= */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.body.classList.contains('page-view-icts-knowledge-base')) {
    return;
  }

  const roadmap = document.querySelector('.icts-kb-roadmap');

  if (!roadmap || roadmap.querySelector('.icts-kb-processor-waves')) {
    return;
  }

  const style = document.createElement('style');
  style.textContent = `
    .icts-kb-processor-waves {
      position: absolute !important;
      inset: 0 !important;
      width: 100% !important;
      height: 100% !important;
      z-index: 1 !important;
      pointer-events: none !important;
      overflow: hidden !important;
      opacity: .92 !important;
    }

    .icts-kb-processor-waves svg {
      display: block !important;
      width: 100% !important;
      height: 100% !important;
      overflow: visible !important;
    }

    .icts-kb-wave-base {
      fill: none !important;
      stroke-width: 1.2 !important;
      opacity: .20 !important;
      vector-effect: non-scaling-stroke !important;
    }

    .icts-kb-wave-live {
      fill: none !important;
      stroke-width: 2.4 !important;
      stroke-linecap: round !important;
      stroke-linejoin: round !important;
      stroke-dasharray: 5 26 2 90 !important;
      vector-effect: non-scaling-stroke !important;
      filter: drop-shadow(0 0 5px currentColor)
              drop-shadow(0 0 12px currentColor) !important;
      animation:
        ictsProcessorWaveFlow
        var(--wave-speed)
        linear
        infinite !important;
    }

    .icts-kb-wave-core {
      fill: none !important;
      stroke-width: 4 !important;
      stroke-linecap: round !important;
      opacity: .16 !important;
      stroke-dasharray: 2 140 !important;
      vector-effect: non-scaling-stroke !important;
      filter: drop-shadow(0 0 7px currentColor) !important;
      animation:
        ictsProcessorCoreFlow
        var(--wave-speed)
        linear
        infinite !important;
    }

    .icts-kb-wave-cyan {
      color: #45efff !important;
      stroke: #45efff !important;
    }

    .icts-kb-wave-blue {
      color: #7187ff !important;
      stroke: #7187ff !important;
    }

    .icts-kb-wave-green {
      color: #42f5b5 !important;
      stroke: #42f5b5 !important;
    }

    .icts-kb-wave-white {
      color: #b9fbff !important;
      stroke: #b9fbff !important;
    }

    .icts-kb-wave-group {
      transform-origin: center !important;
    }

    @keyframes ictsProcessorWaveFlow {
      from {
        stroke-dashoffset: 0;
      }

      to {
        stroke-dashoffset: -360;
      }
    }

    @keyframes ictsProcessorCoreFlow {
      from {
        stroke-dashoffset: 0;
      }

      to {
        stroke-dashoffset: -420;
      }
    }

    /* Small processor pulse points riding on the waves */
    .icts-kb-wave-pulse {
      fill: currentColor !important;
      filter:
        drop-shadow(0 0 5px currentColor)
        drop-shadow(0 0 13px currentColor) !important;
      animation:
        ictsProcessorPulse
        var(--pulse-speed)
        ease-in-out
        infinite !important;
      transform-box: fill-box !important;
      transform-origin: center !important;
    }

    @keyframes ictsProcessorPulse {
      0%,100% {
        opacity: .20;
        transform: scale(.55);
      }

      45% {
        opacity: 1;
        transform: scale(1.25);
      }

      65% {
        opacity: .55;
        transform: scale(.85);
      }
    }
  `;

  document.head.appendChild(style);

  const wrapper = document.createElement('div');
  wrapper.className = 'icts-kb-processor-waves';
  wrapper.setAttribute('aria-hidden', 'true');

  wrapper.innerHTML = `
    <svg viewBox="0 0 1200 420"
         preserveAspectRatio="none"
         xmlns="http://www.w3.org/2000/svg">

      <!-- WAVE 1 -->
      <g class="icts-kb-wave-group">
        <path
          class="icts-kb-wave-base icts-kb-wave-cyan"
          d="M0 92
             C70 30 120 30 190 92
             S310 154 380 92
             S500 30 570 92
             S690 154 760 92
             S880 30 950 92
             S1070 154 1140 92
             S1180 60 1200 92" />

        <path
          class="icts-kb-wave-live icts-kb-wave-cyan"
          style="--wave-speed: 4.8s"
          d="M0 92
             C70 30 120 30 190 92
             S310 154 380 92
             S500 30 570 92
             S690 154 760 92
             S880 30 950 92
             S1070 154 1140 92
             S1180 60 1200 92" />

        <path
          class="icts-kb-wave-core icts-kb-wave-cyan"
          style="--wave-speed: 3.1s"
          d="M0 92
             C70 30 120 30 190 92
             S310 154 380 92
             S500 30 570 92
             S690 154 760 92
             S880 30 950 92
             S1070 154 1140 92
             S1180 60 1200 92" />
      </g>

      <!-- WAVE 2 -->
      <g class="icts-kb-wave-group">
        <path
          class="icts-kb-wave-base icts-kb-wave-blue"
          d="M0 168
             C85 228 125 228 210 168
             S335 108 420 168
             S545 228 630 168
             S755 108 840 168
             S965 228 1050 168
             S1160 110 1200 168" />

        <path
          class="icts-kb-wave-live icts-kb-wave-blue"
          style="--wave-speed: 6.2s"
          d="M0 168
             C85 228 125 228 210 168
             S335 108 420 168
             S545 228 630 168
             S755 108 840 168
             S965 228 1050 168
             S1160 110 1200 168" />
      </g>

      <!-- WAVE 3 -->
      <g class="icts-kb-wave-group">
        <path
          class="icts-kb-wave-base icts-kb-wave-green"
          d="M0 248
             C60 190 125 190 185 248
             S310 306 375 248
             S500 190 565 248
             S690 306 755 248
             S880 190 945 248
             S1070 306 1135 248
             S1170 220 1200 248" />

        <path
          class="icts-kb-wave-live icts-kb-wave-green"
          style="--wave-speed: 5.4s"
          d="M0 248
             C60 190 125 190 185 248
             S310 306 375 248
             S500 190 565 248
             S690 306 755 248
             S880 190 945 248
             S1070 306 1135 248
             S1170 220 1200 248" />

        <path
          class="icts-kb-wave-core icts-kb-wave-green"
          style="--wave-speed: 3.8s"
          d="M0 248
             C60 190 125 190 185 248
             S310 306 375 248
             S500 190 565 248
             S690 306 755 248
             S880 190 945 248
             S1070 306 1135 248
             S1170 220 1200 248" />
      </g>

      <!-- WAVE 4 -->
      <g class="icts-kb-wave-group">
        <path
          class="icts-kb-wave-base icts-kb-wave-white"
          d="M0 330
             C80 282 140 282 220 330
             S360 378 440 330
             S580 282 660 330
             S800 378 880 330
             S1020 282 1100 330
             S1170 360 1200 330" />

        <path
          class="icts-kb-wave-live icts-kb-wave-white"
          style="--wave-speed: 7.1s"
          d="M0 330
             C80 282 140 282 220 330
             S360 378 440 330
             S580 282 660 330
             S800 378 880 330
             S1020 282 1100 330
             S1170 360 1200 330" />
      </g>

      <!-- ACTIVE PROCESSOR PULSES -->
      <g class="icts-kb-wave-cyan">
        <circle
          class="icts-kb-wave-pulse"
          cx="190"
          cy="92"
          r="4"
          style="--pulse-speed: 1.8s" />

        <circle
          class="icts-kb-wave-pulse"
          cx="570"
          cy="92"
          r="4"
          style="--pulse-speed: 2.4s" />
      </g>

      <g class="icts-kb-wave-blue">
        <circle
          class="icts-kb-wave-pulse"
          cx="420"
          cy="168"
          r="4"
          style="--pulse-speed: 2.1s" />

        <circle
          class="icts-kb-wave-pulse"
          cx="840"
          cy="168"
          r="4"
          style="--pulse-speed: 2.8s" />
      </g>

      <g class="icts-kb-wave-green">
        <circle
          class="icts-kb-wave-pulse"
          cx="375"
          cy="248"
          r="4"
          style="--pulse-speed: 2s" />

        <circle
          class="icts-kb-wave-pulse"
          cx="945"
          cy="248"
          r="4"
          style="--pulse-speed: 2.6s" />
      </g>

    </svg>
  `;

  roadmap.prepend(wrapper);
});


/* HOMEPAGE KNOWLEDGE BASE PLATFORM BUTTON */
document.addEventListener('DOMContentLoaded', function () {
  if (window.location.pathname !== '/') {
    return;
  }

  const form = document.querySelector(
    '#views-exposed-form-icts-knowledge-base-block-1'
  );

  if (!form) {
    return;
  }

  const category = form.querySelector('.form-item-tid');
  if (category) {
    category.remove();
  }

  const submit = form.querySelector(
    '#edit-submit-icts-knowledge-base'
  );

  if (submit) {
    submit.value = 'Open Knowledge Platform';
  }
});
