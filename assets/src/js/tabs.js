const HIDDEN_CLASS = 'wpc-builder-tabs__hidden'
const ACTIVE_CLASS = 'wpc-builder-tabs__item--active'

function applyActiveTab (container, assignments, activeTab) {
  container.querySelectorAll('.wpc-builder-tabs__item').forEach((button) => {
    button.classList.toggle(ACTIVE_CLASS, button.dataset.tabId === activeTab)
  })

  for (const controlId of Object.keys(assignments)) {
    window.wp.customize.control(controlId, (control) => {
      const controlContainer = control.container && control.container[0]

      if (!controlContainer) {
        return
      }

      controlContainer.classList.toggle(HIDDEN_CLASS, assignments[controlId] !== activeTab)
    })
  }
}

function bindWhenEmbedded (control) {
  if (control.params.type !== 'wpc-builder-tabs') {
    return
  }

  if (control._wpcBuilderTabsInit) {
    return
  }

  control._wpcBuilderTabsInit = true

  control.deferred.embedded.done(() => {
    const container = control.container && control.container[0]

    if (!container) {
      return
    }

    const field = control.params.field || {}
    const tabs = field.tabs || {}
    const assignments = field.assignments || {}
    const tabIds = Object.keys(tabs)

    if (tabIds.length === 0) {
      return
    }

    let activeTab = tabIds[0]

    container.querySelectorAll('.wpc-builder-tabs__item').forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault()
        activeTab = button.dataset.tabId
        applyActiveTab(container, assignments, activeTab)
      })
    })

    applyActiveTab(container, assignments, activeTab)
  })
}

function init () {
  if (!window.wp?.customize) {
    return
  }

  window.wp.customize.control.each(bindWhenEmbedded)

  window.wp.customize.control.bind('add', bindWhenEmbedded)
}

if (typeof window !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init)
  } else {
    init()
  }
}
