// @vitest-environment jsdom

import { expect, it } from 'vitest'
import { emptyValueForSaveAs, render, valueForSaveAs } from './image.js'

const attachment = {
  id: 42,
  url: 'https://example.test/wp-content/uploads/logo.png',
  filename: 'logo.png',
}

it('writes the attachment id, not its url, under save_as=id, the default', () => {
  expect(valueForSaveAs(attachment, 'id')).toBe(42)
  expect(valueForSaveAs(attachment, undefined)).toBe(42)
  expect(valueForSaveAs(attachment, 'anything-else')).toBe(42)
})

it('writes the url under save_as=url', () => {
  expect(valueForSaveAs(attachment, 'url')).toBe(attachment.url)
})

it('writes the {id, url, filename} triple under save_as=array', () => {
  expect(valueForSaveAs(attachment, 'array')).toEqual({
    id: 42,
    url: attachment.url,
    filename: 'logo.png',
  })
})

it('empties to whatever the matching sanitizer branch produces for no input', () => {
  expect(emptyValueForSaveAs('id')).toBe(0)
  expect(emptyValueForSaveAs(undefined)).toBe(0)
  expect(emptyValueForSaveAs('url')).toBe('')
  expect(emptyValueForSaveAs('array')).toEqual({ id: '', url: '', filename: '' })
})

it('tolerates an attachment with no filename', () => {
  expect(valueForSaveAs({ id: 7, url: 'https://example.test/a.png' }, 'array')).toEqual({
    id: 7,
    url: 'https://example.test/a.png',
    filename: '',
  })
})

function buildImageWrapperFixture () {
  document.body.innerHTML = `
    <div class="wrapper">
      <div class="wpc-builder-image__preview"></div>
      <button class="wpc-builder-image__select"></button>
      <button class="wpc-builder-image__remove"></button>
    </div>
  `

  return document.querySelector('.wrapper')
}

it('shows a thumbnail and "Change image" when a url is set', () => {
  const wrapper = buildImageWrapperFixture()

  render(wrapper, 'https://example.test/logo.png')

  const img = wrapper.querySelector('img')

  expect(img.src).toBe('https://example.test/logo.png')
  expect(img.className).toBe('wpc-builder-image__thumbnail')
  expect(wrapper.querySelector('.wpc-builder-image__select').textContent).toBe('Change image')
  expect(wrapper.querySelector('.wpc-builder-image__remove').hidden).toBe(false)
})

it('shows the placeholder and "Select image" when no url is set', () => {
  const wrapper = buildImageWrapperFixture()

  render(wrapper, '')

  expect(wrapper.querySelector('.wpc-builder-image__preview').textContent).toBe('No image selected')
  expect(wrapper.querySelector('.wpc-builder-image__select').textContent).toBe('Select image')
  expect(wrapper.querySelector('.wpc-builder-image__remove').hidden).toBe(true)
})
