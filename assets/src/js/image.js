import { compileTemplate } from './template.js'
import { translate } from './i18n.js'

const PREVIEW_TEMPLATE = compileTemplate(
  '<# if (data.url) { #><img class="wpc-builder-image__thumbnail" src="{{ data.url }}" alt=""><# } else { #><span class="wpc-builder-image__placeholder">{{ data.emptyLabel }}</span><# } #>',
)

export function valueForSaveAs (attachment, saveAs) {
  if ('url' === saveAs) {
    return attachment.url ?? ''
  }

  if ('array' === saveAs) {
    return {
      id: attachment.id,
      url: attachment.url ?? '',
      filename: attachment.filename ?? '',
    }
  }

  return attachment.id
}

export function emptyValueForSaveAs (saveAs) {
  if ('url' === saveAs) {
    return ''
  }

  if ('array' === saveAs) {
    return { id: '', url: '', filename: '' }
  }

  return 0
}

function wrapperFor (element) {
  return element?.closest?.('[data-wpc-builder-image-setting]') ?? null
}

function settingFor (wrapper) {
  if (!wrapper || !window.wp?.customize) {
    return null
  }

  const settingId = wrapper.dataset.wpcBuilderImageSetting ?? null

  return window.wp.customize(settingId) ?? null
}

export function render (wrapper, url) {
  const preview = wrapper.querySelector('.wpc-builder-image__preview')
  const selectButton = wrapper.querySelector('.wpc-builder-image__select')
  const removeButton = wrapper.querySelector('.wpc-builder-image__remove')

  if (preview) {
    preview.innerHTML = PREVIEW_TEMPLATE({ url, emptyLabel: translate('No image selected') })
  }

  if (selectButton) {
    selectButton.textContent = url ? translate('Change image') : translate('Select image')
  }

  if (removeButton) {
    removeButton.hidden = !url
  }
}

function openMediaFrame (onSelect) {
  if (!window.wp?.media) {
    return
  }

  const frame = window.wp.media({ multiple: false })

  frame.on('select', () => {
    onSelect(frame.state().get('selection').first().toJSON())
  })

  frame.open()
}

export function handleClick (event) {
  const target = event.target

  if (!target?.classList) {
    return
  }

  const isSelect = target.classList.contains('wpc-builder-image__select')
  const isRemove = target.classList.contains('wpc-builder-image__remove')

  if (!isSelect && !isRemove) {
    return
  }

  const wrapper = wrapperFor(target)
  const setting = settingFor(wrapper)

  if (!setting) {
    return
  }

  event.preventDefault()

  const saveAs = wrapper.dataset.wpcBuilderImageSaveAs ?? 'id'

  if (isRemove) {
    setting.set(emptyValueForSaveAs(saveAs))
    render(wrapper, '')

    return
  }

  openMediaFrame((attachment) => {
    setting.set(valueForSaveAs(attachment, saveAs))
    render(wrapper, attachment.url ?? '')
  })
}

if ('undefined' !== typeof document) {
  document.addEventListener('click', handleClick)
}
