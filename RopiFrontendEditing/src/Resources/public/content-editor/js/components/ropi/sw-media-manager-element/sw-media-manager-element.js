import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

import UUID from '../uuid/uuid.js?v=1637255330';
import TranslateElement from '../translate-element/translate-element.js?v=1637255330';
import TypeUtil from '../type-util/type-util.js?v=1637255330';
import DOMTemplate from '../dom-template/dom-template.js?v=1637255330';
import HttpRequest from '../http-message/http-request.js?v=1637255330';
import HttpClient from '../http-client/http-client.js?v=1637255330';
import HttpError from '../http-message/http-error.js?v=1637255330';
import Environment from '../frontend-editing/environment.js?v=1637255330';

import '../touchable-element/touchable-element.js?v=1637255330';
import '../button-element/button-element.js?v=1637255330';
import '../image-box-element/image-box-element.js?v=1637255330';
import '../material-icon-element/material-icon-element.js?v=1637255330';
import '../if-element/if-element.js?v=1637255330';
import '../textfield-element/textfield-element.js?v=1637255330';
import '../toast-element/toast-element.js?v=1637255330';

TranslateElement.registerSnippets({
  ropiSwMediaManager: {
    back: 'Back',
    searchMedia: 'Search media',
    refresh: 'Refresh',
    uploadMedia: 'Upload media',
    loadMoreMedia: 'Load more media'
  }
});

let albumTemplate = html`
  <div class="album-wrap">
    <ropi-touchable data-id="{{album.id}}" class="album">
      <div>
        <ropi-material-icon class="album-icon">folder</ropi-material-icon>
      </div>
    </ropi-touchable>
    <div class="toolbar subbar">
      <div title="{{album.name}}" class="album-label">
        <span data-album="name"></span>
      </div>
    </div>
  </div>
`;

let mediaTemplate = html`
  <div class="media-wrap">
    <ropi-touchable data-id="{{media.id}}" class="media">
      <div>
        <ropi-if class="media-icon" condition="'{{media.mediaType.name}}' == 'IMAGE'">
          <ropi-image-box slot="then" class="media-icon media-thumbnail" src="{{media.url}}"></ropi-image-box>
          <ropi-material-icon slot="else" class="media-icon">insert_drive_file</ropi-material-icon>
        </ropi-if>
      </div>
    </ropi-touchable>
    <div class="toolbar subbar">
      <ropi-if condition="'{{media.fileExtension}}'">
        <div slot="then" class="media-label" title="{{media.fileName}}.{{media.fileExtension}}">
            <span data-media="fileName"></span>.<span data-media="fileExtension"></span>
        </div>
        <div slot="else" class="media-label" data-media="fileName" title="{{media.fileName}}"></div>
      </ropi-if>
      <a class="right" href="{{media.url}}" target="_blank">
        <ropi-touchable>
          <ropi-material-icon>open_in_new</ropi-material-icon>
        </ropi-touchable>
      </a>
    </div>
  </div>
`;

export default class RopiSwMediaManagerElement extends RopiHTMLElement {

  static get observedAttributes() {
    return [
      'accept'
    ];
  }

  attributeChangedCallback(name, valueBefore, value) {
    if (name === 'accept') {
      if (this.hasAttribute('accept')) {
        this._uploadInput.setAttribute('accept', value);
      } else {
        this._uploadInput.removeAttribute('accept');
      }

      return;
    }
  }

  constructor() {
    super();

    this._numElementsPerPage = 50;
    this._selectedMedia = null;
    this._currentPage = 1;
    this._numMediaLoaded = 0;

    this._backHistory = [];
    this._elementGrid = this.shadowRoot.getElementById('element-grid');
    this._backButton = this.shadowRoot.getElementById('back-button');
    this._mainPanel = this.shadowRoot.getElementById('main-panel');
    this._searchButton = this.shadowRoot.getElementById('search-button');
    this._searchCloseButton = this.shadowRoot.getElementById('search-close-button');
    this._searchPanel = this.shadowRoot.getElementById('search-panel');
    this._searchInput = this.shadowRoot.getElementById('search-input');
    this._title = this.shadowRoot.getElementById('title');
    this._reloadButton = this.shadowRoot.getElementById('reload-button');
    this._uploadButton = this.shadowRoot.getElementById('upload-button');
    this._uploadInput = this.shadowRoot.getElementById('upload-input');
    this._loadingBar = this.shadowRoot.getElementById('loading-bar');
    this._loadMoreButton = this.shadowRoot.getElementById('load-more-button');
    this._httpClient = new HttpClient();

    this._backButtonClickHandler = () => {
      if (this._backHistory.length <= 1 || this._backButton.hasAttribute('disabled')) {
        return;
      }

      this._backHistory.pop();

      let entry = this._backHistory[this._backHistory.length - 1];
      this._openAlbum(entry.album, true);
    };

    this._reloadButtonClickHandler = () => {
      this.reload();
    };

    this._uploadButtonClickHandler = () => {
      if (this._uploadButton.hasAttribute('disabled') || this._uploadButton.classList.contains('uploading')) {
        return;
      }

      this._uploadInput.click();
    };

    this._uploadInputChangeHandler = () => {
      this._uploadFile();
    };

    this._loadMoreButtonClickHandler = () => {
      if (this._loadMoreButton.hasAttribute('disabled')) {
        return;
      }

      this._loadMoreButton.setAttribute('disabled', '');

      this._currentPage++;

      let currentHistoryEntry = this._backHistory[this._backHistory.length - 1];
      this._loadAlbumMedia(currentHistoryEntry.album);
    };

    this._searchButtonClickHandler = () => {
      this._mainPanel.classList.add('hidden');
      this._searchPanel.classList.add('open');
      this._searchInput.focus();

      delete this._searchValueBefore;
    };

    this._searchCloseButtonClickHandler = () => {
      this._closeSearch(true);
    };

    this._searchInputKeyUpHandler = () => {
      if (this._searchValueBefore !== this._searchInput.value) {
        this._searchValueBefore = this._searchInput.value;
        this._currentPage = 1;

        this.reload();
      }
    };

    this._updateSnippetHandler = () => {
      this._backButton.setAttribute('title', TranslateElement.translate('ropiSwMediaManager.back'));
      this._searchButton.setAttribute('title', TranslateElement.translate('ropiSwMediaManager.searchMedia'));
      this._reloadButton.setAttribute('title', TranslateElement.translate('ropiSwMediaManager.refresh'));
      this._uploadButton.setAttribute('title', TranslateElement.translate('ropiSwMediaManager.uploadMedia'));
    }
  }

  connectedCallback() {
    this._updateSnippetHandler();
    TranslateElement.bind(this._updateSnippetHandler);

    this._backButton.addEventListener('click', this._backButtonClickHandler);
    this._reloadButton.addEventListener('click', this._reloadButtonClickHandler);
    this._uploadButton.addEventListener('click', this._uploadButtonClickHandler);
    this._uploadInput.addEventListener('change', this._uploadInputChangeHandler);
    this._loadMoreButton.addEventListener('click', this._loadMoreButtonClickHandler);
    this._searchButton.addEventListener('click', this._searchButtonClickHandler);
    this._searchInput.addEventListener('keyup', this._searchInputKeyUpHandler);
    this._searchCloseButton.addEventListener('click', this._searchCloseButtonClickHandler);
  }

  disconnectedCallback() {
    TranslateElement.unbind(this._updateSnippetHandler);
  }

  _uploadFile() {
    let file = this._uploadInput.files[0];
    let fileNameSegments = file.name.split('.');
    let fileExtension = fileNameSegments.pop();
    let fileName = fileNameSegments.join('.');

    let uuid = UUID.v4().replace(/-/g, '');
    let upsertRequest = Environment.createAuthenticatedRequest(this.getAttribute('apibaseuri') + '_action/sync')
        .setMethod(HttpRequest.METHOD_POST)
        .setBody(JSON.stringify([
          {
            action: "upsert",
            entity: "media",
            payload: [
              {
                id: uuid,
                mediaFolderId: this.currentAlbumId
              }
            ]
          }
        ]));

    this._uploadButton.classList.add('uploading');

    let fileReader = new FileReader();

    return this._httpClient.send(upsertRequest)
        .then(() => {
          return new Promise((resolve) => {
            fileReader.onload = () => {
              resolve();
            };
            fileReader.readAsBinaryString(file);
          });
        })
        .then(() => {
          let uploadRequest = Environment.createAuthenticatedRequest(
                this.getAttribute('apibaseuri')
                + '_action/media/'
                + uuid
                + '/upload?extension=' + encodeURIComponent(fileExtension) + '&fileName=' + encodeURIComponent(fileName)
            )
            .setMethod(HttpRequest.METHOD_POST)
            .setHeader('Content-Type', file.type || 'application/octet-stream')
            .setBody(file);

          return this._httpClient.send(uploadRequest);
        })
        .then(() => {
          this._uploadInput.value = '';
          this._uploadButton.classList.remove('uploading');
          this.reload();
        })
        .catch((error) => {
          if (error instanceof HttpError) {
            let rename = false;

            try {
              let errorObject = JSON.parse(error.getHttpResponse().getBody());
              if (errorObject.errors[0].code === 'CONTENT__MEDIA_DUPLICATED_FILE_NAME') {
                rename = true;
              }
            } catch (e) {
              // Silent fail
            }

            if (rename) {
              let provideNameRequest = Environment.createAuthenticatedRequest(
                    this.getAttribute('apibaseuri') + '_action/media/provide-name?fileName=' + encodeURIComponent(fileName)
                    + '&extension=' + encodeURIComponent(fileExtension)
                  )
                  .setMethod(HttpRequest.METHOD_GET);

              this._httpClient.send(provideNameRequest)
                  .then(JSON.parse)
                  .then((responseObject) => {
                    let uploadRequest = Environment.createAuthenticatedRequest(
                        this.getAttribute('apibaseuri')
                        + '_action/media/'
                        + uuid
                        + '/upload?extension=' + encodeURIComponent(fileExtension) + '&fileName=' + encodeURIComponent(responseObject.fileName)
                    )
                    .setMethod(HttpRequest.METHOD_POST)
                    .setHeader('Content-Type', file.type || 'application/octet-stream')
                    .setBody(file);

                    return this._httpClient.send(uploadRequest);
                  })
                  .then(() => {
                    this._uploadInput.value = '';
                    this._uploadButton.classList.remove('uploading');
                    this.reload();
                  })
                  .catch((error) => {
                    this._uploadButton.classList.remove('uploading');

                    let toast = document.createElement('ropi-toast');
                    toast.setAttribute('severity', 'error');
                    toast.innerText = 'Upload failed: ' + error;
                    this.shadowRoot.appendChild(toast);
                  });

              return;
            }
          }

          this._uploadButton.classList.remove('uploading');

          let toast = document.createElement('ropi-toast');
          toast.setAttribute('severity', 'error');
          toast.innerText = 'Upload failed: ' + error;
          this.shadowRoot.appendChild(toast);
        });
  }

  _abortPendingRequest() {
    if (this._pendingRequest) {
      this._httpClient.abort(this._pendingRequest);
      this._pendingRequest = null;
    }
  }

  reload() {
    if (this._backHistory.length <= 1) {
      this.load();
      return;
    }

    let entry = this._backHistory[this._backHistory.length - 1];
    this._openAlbum(entry.album, true);
  }

  reset() {
    this._closeSearch();
    this._closeViews();
  }

  load() {
    if (!this.hasAttribute('apibaseuri')) {
      return;
    }

    this._backHistory = [];

    this._abortPendingRequest();

    this._loadAlbum(null);
  };

  _loadAlbum(albumId) {
    albumId = this._normalizeAlbumId(albumId);

    this._pendingRequest = Environment.createAuthenticatedRequest(this.getAttribute('apibaseuri') + 'search/media-folder')
        .setMethod(HttpRequest.METHOD_POST)
        .setBody(JSON.stringify({
          page: this._currentPage,
          limit: 500,
          term: '',
          sort: "name",
          filter: [
            {
              type: "equals",
              field: "parentId",
              value: albumId
            }
          ]
        }));

    this._loadingBar.classList.add('visible');

    this._httpClient.send(this._pendingRequest)
      .then(JSON.parse)
      .then((responseObject) => {
        this._loadingBar.classList.remove('visible');

        let albums = [];

        if (responseObject.data && TypeUtil.isArray(responseObject.data)) {
          responseObject.data.forEach((album) => {
            albums.push(album);
          });
        }

        this._openAlbum({
          id: albumId,
          data: albums
        });
      });
  }

  _normalizeAlbumId(id) {
    return (id && id !== 'root') ? id : null;
  }

  get currentAlbumId() {
    if (!this._backHistory.length) {
      return null;
    }

    let currentAlbum = this._backHistory[this._backHistory.length - 1].album;
    return this._normalizeAlbumId(currentAlbum.id);
  }

  _openAlbum(album, noHistory) {
    this._openView('album-content');
    this._elementGrid.innerHTML = '';
    this._elementGrid.scrollTop = 0;

    this._currentPage = 1;
    this._numMediaLoaded = 0;

    this._loadMoreButton.classList.remove('visible');
    this._loadMoreButton.removeAttribute('disabled');

    if (album.data) {
      for (let childAlbum of album.data) {
        let albumElement = albumTemplate.content.cloneNode(true).firstElementChild;

        DOMTemplate.process(albumElement, {
          album: childAlbum
        });

        let touchable = albumElement.querySelector('ropi-touchable');
        touchable.onclick = (event) => {
          this.reset();

          for (let childAlbum of album.data) {
            if (event.currentTarget.getAttribute('data-id') == childAlbum.id) {
              this._loadAlbum(childAlbum.id);
              return;
            }
          }
        };

        this._elementGrid.appendChild(albumElement);
      }
    }

    if (album.id === 'root') {
      this._backButton.setAttribute('disabled', '');
      this._backHistory = [];

      this._title.textContent = '';
      this._title.setAttribute('title', '');
    } else {
      this._backButton.removeAttribute('disabled');

      this._title.textContent = album.name;
      this._title.setAttribute('title', album.name);
    }

    this._abortPendingRequest();
    this._loadingBar.classList.add('visible');
    this._loadAlbumMedia(album);

    if (!noHistory || album.id === 'root') {
      this._backHistory.push({
        album: album
      });
    }
  }

  get isSearch() {
    return this._searchInput.value.trim() !== '';
  }

  _loadAlbumMedia(album) {
    this._abortPendingRequest();

    let filter = [
      {
        type: "equals",
        field: "mediaFolderId",
        value: this._normalizeAlbumId(album.id)
      }
    ];

    if (this.hasAttribute('accept')) {
      let mimeTypes = this.getAttribute('accept').split(',');
      let mimeTypeFilter = [];
      for (let mimeType of mimeTypes) {
        mimeType = mimeType.trim();
        if (mimeType) {
          mimeTypeFilter.push({
            type: "equals",
            field: "mimeType",
            value: mimeType
          });
        }
      }

      if (mimeTypeFilter.length > 0) {
        filter.push({
          type: "multi",
          operator: "OR",
          queries: mimeTypeFilter
        });
      }
    }

    if (this._searchInput.value.trim() !== '') {
      this._elementGrid.classList.add('search-results');
    } else {
      this._elementGrid.classList.remove('search-results');
    }

    this._pendingRequest = Environment.createAuthenticatedRequest(this.getAttribute('apibaseuri') + 'search/media')
                              .setMethod(HttpRequest.METHOD_POST)
                              .setBody(JSON.stringify({
                                page: this._currentPage,
                                limit: this._numElementsPerPage,
                                sort: "fileName",
                                term: this._searchInput.value,
                                filter: filter
                              }));

    this._httpClient.send(this._pendingRequest)
      .then(JSON.parse)
      .then((responseObject) => {
        this._pendingRequest = null;
        this._loadingBar.classList.remove('visible');

        for (let media of responseObject.data) {
          let mediaElement = mediaTemplate.content.cloneNode(true).firstElementChild;

          DOMTemplate.process(mediaElement, {
            media: media
          });

          let touchable = mediaElement.querySelector('ropi-touchable');
          touchable.onclick = (event) => {
            this._selectedMedia = media;

            this.reset();

            this.dispatchEvent(new CustomEvent('select', {
              detail: {
                media: this._selectedMedia
              },
              bubbles: false
            }));
          };

          this._elementGrid.appendChild(mediaElement);
        }

        this._loadMoreButton.removeAttribute('disabled');

        this._numMediaLoaded += responseObject.data.length;

        if (this._numMediaLoaded >= responseObject.total) {
          this._loadMoreButton.classList.remove('visible');
        } else {
          this._loadMoreButton.classList.add('visible');
        }
      });
  }

  _openView(viewId) {
    this._closeViews();

    this.shadowRoot.getElementById(viewId).classList.add('open');
  }

  _closeViews() {
    let views = this.shadowRoot.querySelectorAll('.view.open');

    for (let view of views) {
      view.classList.remove('open');
    }
  }

  _closeSearch(refresh) {
    this._mainPanel.classList.remove('hidden');
    this._searchPanel.classList.remove('open');
    this._elementGrid.classList.remove('search-results');

    if (this._searchInput.value) {
      this._searchInput.value = '';
      if (refresh) {
        this.reload();
      }
    }
  }

}

RopiSwMediaManagerElement._template = html`
<style>
  @keyframes ropi-sw-media-manager-element-loading {
    0% {
      transform: rotateZ(0);
    }
    50% {
      transform: rotateZ(180deg) scale(0.5);
    }
    100% {
      transform: rotateZ(360deg);
    }
  }

  @keyframes ropi-sw-media-manager-element-buffering {
    0% {
      transform: translateX(-100%);
    }
    100% {
      transform: translateX(100%);
    }
  }

  :host {
    display: block;
    position: absolute;
    height: 100%;
    width: 100%;
  }

  .element-grid {
    display: grid;
    grid-template-columns: repeat( auto-fill, minmax(12rem, 1fr) );
    text-align: center;
    grid-row-gap: 1rem;
    column-gap: 1rem;
  }

  .element-grid.search-results .album-wrap {
    display: none;
  }

  .album,
  .media {
    padding: 1rem;
    position: relative;
    background-color: var(--ropi-color-material-25, grey);
  }

  .subbar {
    background-color: var(--ropi-color-material-50, darkgrey);
  }

  .album-label,
  .media-label {
    font-size: var(--ropi-font-size-s, 0.75rem);
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;
    position: absolute;
    left: 1rem;
    top: 0;
    right: 1rem;
  }

  .media-label {
    right: 3rem;
  }

  .album-icon,
  .media-icon {
    width: 4rem;
    height: 4rem;
    position: relative;
    color: var(--ropi-color-interactive, blue);
  }

  .media-thumbnail {
    margin: 0 auto;
  }

  .view {
    display: none;
    position: absolute;
    top: 3rem;
    bottom: 0;
    left: 0;
    right: 0;
    overflow: auto;
  }

  .view.open {
    display: block;
  }

  .toolbar {
    position: relative;
    height: 3rem;
    line-height: 3rem;
  }

  .toolbar ropi-touchable {
    width: 3rem;
    height: 3rem;
    line-height: 3rem;
    text-align: center;
  }

  .toolbar .left,
  .toolbar .right {
    position: absolute;
    left: 0;
    top: 0;
    display: flex;
  }

  .toolbar .right {
    left: auto;
    right: 0;
  }

  #action-panel {
    position: absolute;
    left: 0;
    right: 0;
    top: 0;
    z-index: 10;
    background-color: var(--ropi-color-base, white);
    overflow: hidden;
  }

  #title {
    position: absolute;
    top: 0;
    height: 3rem;
    left: 4rem;
    right: 10rem;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .media-open-in-new {
    position: absolute;
    top: 0;
    right: 0;
  }

  ropi-touchable[disabled] {
    opacity: var(--ropi-disabled-opacity, 0.33);
  }

  a {
    color: inherit;
    text-decoration: none;
  }

  #upload-button.uploading {
    animation: ropi-sw-media-manager-element-loading 1.5s ease infinite;
    transform-origin: 50% 50% 0;
    --ropi-touchable-glow-color: transparent;
  }

  #loading-bar {
    display: none;
    pointer-events: none;
    position: absolute;
    left: 0;
    bottom: 0;
    width: 100%;
    height: 0.0625rem;
    background-color: var(--ropi-color-interactive, blue);
    animation: ropi-sw-media-manager-element-buffering 1.5s ease infinite;
  }

  #loading-bar.visible {
    display: block;
  }

  #load-more-button {
    margin: 1rem 1rem 0 1rem;
    display: none;
  }

  #load-more-button.visible {
    display: block;
  }

  #main-panel.hidden {
    display: none;
  }

  #search-panel {
    pointer-events: none;
    position: absolute;
    left: 0;
    top: 0;
    right: 0;
    bottom: 0;
    transform: scaleX(0);
    transition: transform var(--ropi-animation-duration, 301ms) ease;
    transform-origin: right center;
    background-color: var(--ropi-color-base, white);
  }

  #search-close-button {
    display: none;
  }

  #search-panel.open {
    pointer-events: auto;
    transform: scaleX(1);
  }

  #search-panel.open #search-close-button {
    display: block;
  }

  #search-input {
    padding: 0;
    --ropi-textfield-height: 2rem;
    --ropi-textfield-padding: 0 0.5rem;
    --ropi-textfield-label-left: 0.5rem;
    margin: 0.5rem 1rem 0 3.5rem;
  }
</style>
<div id="action-panel" class="toolbar">
  <div id="main-panel">
    <div class="left">
      <ropi-touchable id="back-button" disabled>
        <ropi-material-icon>arrow_back</ropi-material-icon>
      </ropi-touchable>
    </div>
    <div id="title"></div>
    <div class="right">
      <ropi-touchable id="search-button">
        <ropi-material-icon>search</ropi-material-icon>
      </ropi-touchable>
      <ropi-touchable id="reload-button">
        <ropi-material-icon>refresh</ropi-material-icon>
      </ropi-touchable>
      <ropi-touchable id="upload-button">
        <ropi-material-icon>add_a_photo</ropi-material-icon>
        <input id="upload-input" type="file" style="display:none" />
      </ropi-touchable>
    </div>
  </div>
  <div id="search-panel">
    <div class="left">
      <ropi-touchable id="search-close-button">
        <ropi-material-icon>close</ropi-material-icon>
      </ropi-touchable>
    </div>
    <ropi-textfield id="search-input">
      <div slot="placeholder"><ropi-translate>ropiSwMediaManager.searchMedia</ropi-translate></div>
    </ropi-textfield>
  </div>
  <div id="loading-bar"></div>
</div>
<div class="view open" id="album-content">
  <div class="element-grid" id="element-grid">
  </div>
  <ropi-button id="load-more-button">
    <ropi-translate>ropiSwMediaManager.loadMoreMedia</ropi-translate>
  </ropi-button>
</div>
`;

customElements.define('ropi-sw-media-manager', RopiSwMediaManagerElement);
